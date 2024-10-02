<?php

namespace Commerce\Processors;

use Commerce\Carts\OrderCart;
use Exception;

class OrdersProcessor implements \Commerce\Interfaces\Processor
{
    protected $modx;

    protected $cart;
    protected $order_id;
    protected $order = [];

    protected $tableOrders   = 'commerce_orders';
    protected $tableStatuses = 'commerce_order_statuses';
    protected $tableProducts = 'commerce_order_products';
    protected $tableHistory  = 'commerce_order_history';
    protected $tablePayments = 'commerce_order_payments';

    protected $sessionKey = 'commerce.order';

    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->tableOrders   = $modx->getFullTablename($this->tableOrders);
        $this->tableProducts = $modx->getFullTablename($this->tableProducts);
        $this->tableStatuses = $modx->getFullTablename($this->tableStatuses);
        $this->tableHistory  = $modx->getFullTablename($this->tableHistory);
        $this->tablePayments = $modx->getFullTablename($this->tablePayments);
    }

    protected function prepareOrderValues(array &$items, array &$fields, array &$subtotals, $invokeEvents = true)
    {
        $total = 0;
        $db = $this->modx->db;

        foreach ($items as $item) {
            $total += (float)$item['price'] * (float)$item['count'];
        }

        $this->modx->invokeEvent('OnCollectSubtotals', [
            'rows'     => &$subtotals,
            'total'    => &$total,
            'realonly' => true,
        ]);

        $values = [];

        foreach (['name', 'email', 'phone'] as $field) {
            if (isset($fields[$field])) {
                $values[$field] = $fields[$field];
                unset($fields[$field]);
            }
        }

        $values['amount']      = $this->normalizePrice($total);
        $values['currency']    = ci()->currency->getCurrencyCode();
        $values['lang']        = $this->modx->commerce->getCurrentLang();
        $values['created_at']  = date('Y-m-d H:i:s');
        $values['customer_id'] = (int)$this->modx->getLoginUserID('web');
        $values['hash']        = $this->modx->commerce->generateRandomString();
        $values['fields']      = &$fields;

        if ($invokeEvents) {
            $this->modx->invokeEvent('OnBeforeOrderSaving', [
                'order_id'  => null,
                'values'    => &$values,
                'fields'    => &$fields,
                'items'     => &$items,
                'subtotals' => &$subtotals,
            ]);
        }

        $values['fields'] = json_encode($fields, JSON_UNESCAPED_UNICODE);

        return $values;
    }

    protected function prepareOrderProduct($order_id, $position, $item)
    {
        $db = $this->modx->db;

        $fields = [
            'order_id'   => $order_id,
            'product_id' => (int)$item['id'],
            'title'      => trim(isset($item['title']) ? $item['title'] : $item['name']),
            'price'      => $this->normalizePrice($item['price']),
            'count'      => $this->normalizePrice($item['count']),
            'position'   => $position,
        ];

        if (isset($item['options']) && is_array($item['options'])) {
            $fields['options'] = json_encode($item['options'], JSON_UNESCAPED_UNICODE);
        }

        if (isset($item['meta']) && is_array($item['meta'])) {
            $fields['meta'] = json_encode($item['meta'], JSON_UNESCAPED_UNICODE);
        }

        return $fields;
    }

    protected function prepareOrderSubtotal($order_id, $position, $item)
    {
        return [
            'order_id' => $order_id,
            'title'    => $item['title'],
            'price'    => $this->normalizePrice($item['price']),
            'position' => $position,
            'meta'     => json_encode(isset($item['meta']) && is_array($item['meta']) ? $item['meta'] : [], JSON_UNESCAPED_UNICODE)
        ];
    }

    public function createOrder(array $items, array $fields, $invokeEvents = true)
    {
        $db = $this->modx->db;
        $subtotals = [];
        $values = $this->prepareOrderValues($items, $fields, $subtotals, $invokeEvents);
        $_fields = $values['fields'];
        unset($values['fields']);
        $fields = $values['fields'] = $_fields;
        $_values = $values;
        unset($values);
        $values = $_values;
        unset($_fields, $_values);
        
        try {
            $defaultStatus = ci()->statuses->getDefaultStatus();
            $values['status_id'] = $defaultStatus;

            if ($db->begin(0, 'Commerce') === false) {
                throw new Exception("Cannot begin transaction!");
            }

            $order_id = $db->insert($db->escape($values), $this->tableOrders);

            if (!$order_id) {
                throw new Exception("Cannot insert order record!\n" . print_r($values, true));
            }

            $this->order_id = $order_id;

            $position = 1;

            foreach ($items as $item) {
                $itemValues = $this->prepareOrderProduct($order_id, $position++, $item);

                if (!$db->insert($db->escape($itemValues), $this->tableProducts)) {
                    throw new Exception("Cannot insert order product!\n" . print_r($itemValues, true));
                }
            }

            foreach ($subtotals as $item) {
                $itemValues = $this->prepareOrderSubtotal($order_id, $position++, $item);

                if (!$db->insert($db->escape($itemValues), $this->tableProducts)) {
                    throw new Exception("Cannot insert order subtotal!\n" . print_r($itemValues, true));
                }
            }

            if ($db->commit() === false) {
                throw new Exception("Cannot commit transaction!");
            }
        } catch (Exception $e) {
            $db->rollback();
            $this->modx->logEvent(0, 3, 'Cannot create order:<br><pre>' . $e->getMessage() . '</pre>', 'Commerce');
            return false;
        }

        $this->addOrderHistory($order_id, $defaultStatus);

        if($invokeEvents) {
            $this->modx->invokeEvent('OnOrderSaved', [
                'mode'      => 'new',
                'order_id'  => $order_id,
                'values'    => &$values,
                'items'     => &$items,
                'fields'    => &$fields,
                'subtotals' => &$subtotals,
            ]);
        }

        $order = $this->loadOrder($order_id);
        $this->getCart();

        return $order;
    }

    public function addOrderHistoryText($order_id, $comment, $notify = false)
    {
        $order = $this->loadOrder($order_id);

        if (empty($order)) {
            return false;
        }

        return $this->addOrderHistory($order_id, $order['status_id'], $comment, $notify);
    }

    public function addOrderHistory($order_id, &$status_id, &$comment = '', &$notify = false)
    {
        $order = $this->loadOrder($order_id);

        if (empty($order)) {
            return false;
        }

        $preventChange = false;

        $this->modx->invokeEvent('OnBeforeOrderHistoryUpdate', [
            'order'     => $order,
            'order_id'  => $order_id,
            'status_id' => &$status_id,
            'comment'   => &$comment,
            'notify'    => &$notify,
            'prevent'   => &$preventChange,
        ]);

        if (!$preventChange) {
            $db = $this->modx->db;

            try {
                if ($db->begin(0, 'Commerce') === false) {
                    throw new Exception("Cannot begin transaction!");
                }

                if ($order['status_id'] != $status_id) {
                    $result = $db->update(['status_id' => $status_id], $this->tableOrders, "`id` = '$order_id'");

                    if (!$result) {
                        throw new Exception("Cannot update status [" . print_r($status_id, true) . "] for order [" . print_r($order_id, true) . "]!");
                    } else {
                        $this->order['status_id'] = $status_id;
                    }
                }

                $fields = [
                    'order_id'   => (int)$order_id,
                    'status_id'  => (int)$status_id,
                    'comment'    => $db->escape($comment),
                    'notify'     => !empty($notify) ? 1 : 0,
                    'user_id'    => (int)$this->modx->getLoginUserID('mgr'),
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                $result = $db->insert($fields, $this->tableHistory);

                if (!$result) {
                    throw new Exception("Cannot insert history record!\n" . print_r($fields, true));
                }

                if ($db->commit() === false) {
                    throw new Exception("Cannot commit transaction!");
                }
            } catch (Exception $e) {
                $db->rollback();
                $this->modx->logEvent(0, 3, 'Cannot update order history:<br><pre>' . $e->getMessage() . '<br>' . htmlentities(print_r($order, true)) . '</pre>', 'Commerce');
                return false;
            }
        }

        return true;
    }

    public function changeStatus($order_id, $status_id, $comment = '', $notify = false, $template = null)
    {
        $order = $this->loadOrder($order_id);

        if (empty($order)) {
            return false;
        }

        if (!$this->addOrderHistory($order_id, $status_id, $comment, $notify)) {
            return false;
        }

        if ($notify && !empty($order['email'])) {
            $tpl      = ci()->tpl;
            $commerce = ci()->commerce;

            if (!empty($order['lang'])) {
                $prevLangCode = $commerce->setLang($order['lang']);
            }

            $lang = $commerce->getUserLanguage('order');
            $status = ci()->statuses->getStatus($status_id);

            $statusText = $commerce->getUserLexicon($status['alias'], $status['title']);

            if (is_null($template)) {
                $template = $commerce->getUserLanguageTemplate('status_notification');
                $template = $commerce->getSetting('status_notification', $template);
            }

            $subjectTpl = $lang['order.subject_status_changed'];
            $preventSending = false;

            $templateData = [
                'order_id' => $order_id,
                'order'    => $order,
                'status'   => $statusText,
                'comment'  => $comment,
            ];

            $this->modx->invokeEvent('OnBeforeCustomerNotifySending', [
                'reason'     => 'status_changed',
                'status_id'  => $status_id,
                'order'      => &$order,
                'subject'    => &$subjectTpl,
                'body'       => &$template,
                'data'       => &$templateData,
                'prevent'    => &$preventSending,
            ]);

            $mailResult = true;
            if (!$preventSending) {
                $body    = $tpl->parseChunk($template, $templateData, true);
                $subject = $tpl->parseChunk($subjectTpl, $templateData, true);

                $mailer = new \Helpers\Mailer($this->modx, [
                    'to'      => $order['email'],
                    'subject' => $subject,
                ]);

                $mailResult = $mailer->send($body);
            }

            if (!empty($prevLangCode)) {
                $commerce->setLang($prevLangCode);
            }

            return $mailResult;
        }

        return true;
    }

    public function updateOrder($order_id, $data = [], $invokeEvents = true)
    {
        $params = [
            'order_id' => $order_id,
        ];

        foreach (['values', 'items', 'subtotals'] as $field) {
            if (isset($data[$field])) {
                $params[$field] = &$data[$field];
            }
        }

        if($invokeEvents) {
            $this->modx->invokeEvent('OnBeforeOrderSaving', $params);
        }

        $db = $this->modx->db;

        try {
            if ($db->begin(0, 'Commerce') === false) {
                throw new Exception("Cannot begin transaction!");
            }

            $position = 1;
            $totalPrice = 0;

            if (isset($params['items'])) {
                $exists = [];

                foreach ($params['items'] as $item) {
                    $row_id = !empty($item['order_row_id']) ? $item['order_row_id'] : 0;
                    $item = $this->prepareOrderProduct($order_id, $position++, $item);

                    if (!empty($row_id)) {
                        if (!$db->update($db->escape($item), $this->tableProducts, "id = '$row_id'")) {
                            throw new Exception("Cannot update order product [" . print_r($row_id, true) . "]!\n" . print_r($item, true));
                        }

                        $exists[] = $row_id;
                    } else {
                        $row_id = $db->insert($db->escape($item), $this->tableProducts);

                        if (!$row_id) {
                            throw new Exception("Cannot insert new order product!\n" . print_r($item, true));
                        }

                        $exists[] = $row_id;
                    }

                    $totalPrice += $item['price'] * $item['count'];
                }

                if (!$db->delete($this->tableProducts, "`order_id` = '$order_id' AND `product_id` IS NOT NULL AND `id` NOT IN ('" . implode("', '", $exists) . "')")) {
                    throw new Exception("Cannot delete order products [" . implode(', ', $exists) . "]!\n");
                }
            }

            if (isset($params['subtotals'])) {
                $exists = [];

                foreach ($params['subtotals'] as $item) {
                    $row_id = !empty($item['id']) ? $item['id'] : 0;
                    $item = $this->prepareOrderSubtotal($order_id, $position++, $item);

                    if (!empty($row_id)) {
                        if (!$db->update($db->escape($item), $this->tableProducts, "id = '$row_id'")) {
                            throw new Exception("Cannot update order subtotal [" . print_r($row_id, true) . "]!\n" . print_r($item, true));
                        }

                        $exists[] = $row_id;
                    } else {
                        $row_id = $db->insert($db->escape($item), $this->tableProducts);

                        if (!$row_id) {
                            throw new Exception("Cannot insert new order subtotal!\n" . print_r($item, true));
                        }

                        $exists[] = $row_id;
                    }

                    $totalPrice += $item['price'];
                }

                if (!$db->delete($this->tableProducts, "`order_id` = '$order_id' AND `product_id` IS NULL AND `id` NOT IN ('" . implode("', '", $exists) . "')")) {
                    throw new Exception("Cannot delete order subtotals [" . implode(', ', $exists) . "]!\n");
                }
            }

            if (!empty($params['values'])) {
                $order = $this->loadOrder($order_id);
                $params['values'] = array_replace_recursive($order, $params['values']);
                $params['values']['fields'] = json_encode($params['values']['fields'], JSON_UNESCAPED_UNICODE);
                unset($params['values']['created_at']);
                unset($params['values']['updated_at']);

                if (isset($params['items']) || isset($params['subtotals'])) {
                    $params['values']['amount'] = $totalPrice;
                }

                if (!$db->update($db->escape($params['values']), $this->tableOrders, "id = '" . $order['id'] . "'")) {
                    throw new Exception("Cannot update order [" . print_r($order['id'], true) . "]!\n" . print_r($params['values'], true));
                }
            }

            if ($db->commit() === false) {
                throw new Exception("Cannot commit transaction!");
            }
        } catch (Exception $e) {
            $db->rollback();
            $this->modx->logEvent(0, 3, 'Cannot update order:<br><pre>' . $e->getMessage() . '<br>' . htmlentities(print_r($order, true)) . '</pre>', 'Commerce');
            return false;
        }
        
        $params['mode'] = 'upd';
        
        if($invokeEvents) {
            $this->modx->invokeEvent('OnOrderSaved', $params);
        }

        return true;
    }

    public function deleteOrder($order_id, $invokeEvents = true) {
        $order_id = (int)$order_id;
        $params = [
            'order_id' => $order_id,
        ];
        
        if($invokeEvents) {
            $this->modx->invokeEvent('OnBeforeOrderDeleting', $params);
        }

        if ($this->modx->db->delete($this->tableOrders, "`id` = {$order_id}")) {
            if($invokeEvents) {
                $this->modx->invokeEvent('OnOrderDeleted', $params);
            }
            
            return true;
        }

        return false;
    }

    public function getOrder()
    {
        if (empty($this->order) && !empty($this->order_id)) {
            $this->loadOrder($this->order_id);
        }

        if (!empty($this->order)) {
            return $this->order;
        }

        return null;
    }

    public function loadOrder($order_id, $force = false)
    {
        if (!empty($this->order) && $this->order['id'] == $order_id && !$force) {
            return $this->order;
        }

        $query = $this->modx->db->select('*', $this->tableOrders, "`id` = '" . (int)$order_id . "'");

        if ($this->modx->db->getRecordCount($query)) {
            $this->order = $this->modx->db->getRow($query);
            $this->order['fields'] = json_decode($this->order['fields'], true);
            $this->order_id = $this->order['id'];
            $this->cart = null;

            return $this->order;
        }

        return null;
    }

    public function loadOrderByHash($order_hash)
    {
        $db = $this->modx->db;

        $query = $db->select('*', $this->tableOrders, "`hash` = '" . $db->escape((string)$order_hash) . "'");

        if ($db->getRecordCount($query)) {
            $this->order = $db->getRow($query);
            $this->order['fields'] = json_decode($this->order['fields'], true);
            $this->order_id = $this->order['id'];
            $this->cart = null;
            
            return $this->order;
        }

        return null;
    }

    public function getCart()
    {
        $order = $this->getOrder();

        if (is_null($this->cart) && !is_null($order)) {
            if (!ci()->carts->has('order')) {
                $cart = new OrderCart($this->modx);
                ci()->carts->addCart('order', $cart);
            }
            $this->cart = ci()->carts->getCart('order');
            $this->cart->setCurrency($order['currency']);
            $this->cart->clean();
            
            $query = $this->modx->db->select('*', $this->tableProducts, "`order_id` = '{$this->order_id}'", "`position`");
            $items = [];
            $subtotals = [];

            while ($item = $this->modx->db->getRow($query)) {
                $item['options'] = !empty($item['options']) ? json_decode($item['options'], true) : [];
                $item['meta'] = !empty($item['meta']) ? json_decode($item['meta'], true) : [];

                if (!is_null($item['product_id'])) {
                    $this->cart->add([
                        'id'      => $item['product_id'],
                        'order_row_id' => $item['id'],
                        'name'    => $item['title'],
                        'count'   => $item['count'],
                        'price'   => $item['price'],
                        'options' => $item['options'],
                        'meta'    => $item['meta'],
                    ]);
                } else {
                    $subtotals[] = $item;
                }
            }

            $this->cart->setSubtotals($subtotals);
            $this->cart->setTotal($order['amount']);
        }

        return $this->cart;
    }

    public function postProcessForm($FL)
    {
        $order = $this->getOrder();
        if (!empty($order['fields']['payment_method']) && ci()->statuses->canBePaid($order['status_id'])) {
            $payment = $this->modx->commerce->getPayment($order['fields']['payment_method']);

            $redirectText     = '';
            $instantRedirect  = $this->modx->commerce->getSetting('instant_redirect_to_payment');
            $redirectTemplate = $this->modx->commerce->getSetting('redirect_to_payment_tpl', $this->modx->commerce->getUserLanguageTemplate('redirect_to_payment'));

            $this->modx->invokeEvent('OnBeforePaymentProcess', [
                'FL'                => $FL,
                'order'             => &$order,
                'payment'           => $payment,
                'instant_redirect'  => &$instantRedirect,
                'redirect_text'     => &$redirectText,
                'redirect_template' => &$redirectTemplate,
            ]);

            ci()->flash->set('last_order_id', $order['id']);
            $redirect = $payment['processor']->createPaymentRedirect();

            if ($redirect) {
                if (empty($redirectText)) {
                    $lang = $this->modx->commerce->getUserLanguage('order');
                    $redirectText = $lang['order.redirecting_to_payment'];
                }

                if ($instantRedirect) {
                    $successTpl = $redirectText;

                    if (!empty($redirect['link'])) {
                        $FL->config->setConfig(['redirectTo' => [
                            'page'   => $redirect['link'],
                            'header' => 'HTTP/1.1 303 See Other',
                        ]]);
                    } else {
                        $successTpl .= $redirect['markup'];
                        $FL->config->setConfig(['redirectTo' => '']);
                    }
                } else {
                    $params = [
                        'order'           => $order,
                        'payment'         => $payment,
                        'redirect_text'   => $redirectText,
                        'redirect_link'   => '',
                        'redirect_markup' => '',
                    ];

                    if (!empty($redirect['link'])) {
                        $params['redirect_link'] = $redirect['link'];
                    } else {
                        $params['redirect_markup'] = $redirect['markup'];
                    }

                    $template = $redirectTemplate;
                    $successTpl = ci()->tpl->parseChunk($template, $params, true);
                }

                $FL->config->setConfig(['successTpl' => '@CODE:' . $successTpl]);
            }
        }

        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }
    }

    /**
     * Оплата заказа по его хэшу.
     * Создается новый платеж и пользователь перенаправляется на оплату.
     *
     * @param string $hash Хэш заказа
     */
    public function payOrderByHash($hash)
    {
        $db = ci()->db;
        $order = $db->getRow($db->select('*', $this->tableOrders, "`hash` = '" . $db->escape($hash) . "'"));

        if (!empty($order)) {
            $statusCanBePaid = ci()->statuses->canBePaid($order['status_id']);

            if (!$statusCanBePaid) {
                return false;
            }

            $amount = $this->getOrderPaymentsAmount($order['id']);

            // Если сумма предыдущих оплат больше суммы заказа, отменяем оплату
            if ($amount >= $order['amount'] || abs($amount - $order['amount']) < 0.01) {
                return false;
            }

            // Если оплат еще не было, проверяем,чтобы срок создания оплаты
            // был не больше указанного в настройках плагина
            if (!$amount) {
                $hours = 24 * $this->modx->commerce->getSetting('payment_wait_time', 3);
                $diff  = (new \DateTime())->diff(new \DateTime($order['updated_at'] ?? $order['created_at']));

                if ($diff->days * 24 + $diff->h > $hours) {
                    return false;
                }
            }

            $order = $this->loadOrder($order['id']);

            if (!empty($order['fields']['payment_method'])) {
                $payment = $this->modx->commerce->getPayment($order['fields']['payment_method']);

                $this->modx->invokeEvent('OnBeforePaymentProcess', [
                    'order'   => &$order,
                    'payment' => $payment,
                ]);

                ci()->flash->set('last_order_id', $order['id']);

                $redirect = $payment['processor']->createPaymentRedirect();

                if ($redirect) {
                    if (!empty($redirect['link'])) {
                        $this->modx->sendRedirect($redirect['link']);
                    } else {
                        echo $redirect['markup'];
                    }

                    return true;
                }
            }
        }

        return false;
    }

    protected function preparePayment($payment)
    {
        if (!empty($payment)) {
            $payment['meta'] = json_decode($payment['meta'], true);

            if (empty($payment['meta'])) {
                $payment['meta'] = [];
            }

            return $payment;
        }

        return null;
    }

    public function loadPayment($payment_id)
    {
        $db = ci()->db;
        return $this->preparePayment($db->getRow($db->select('*', $this->tablePayments, "`id` = '" . intval($payment_id) . "'")));
    }

    public function loadPaymentByHash($hash)
    {
        $db = ci()->db;
        return $this->preparePayment($db->getRow($db->select('*', $this->tablePayments, "`hash` = '" . $db->escape($hash) . "'")));
    }

    public function createPayment($order_id, $amount)
    {
        $db = ci()->db;
        $hash = ci()->commerce->generateRandomString(16);

        $paid = $this->getOrderPaymentsAmount($order_id);
        $diff = number_format($amount - $paid, 2, '.', '');
        $meta = [];

        $this->modx->invokeEvent('OnBeforePaymentCreate', [
            'order_id'     => $order_id,
            'order_amount' => $amount,
            'amount'       => &$diff,
            'hash'         => &$hash,
            'meta'         => &$meta,
        ]);

        $order = $this->loadOrder($order_id);

        $payment = [
            'order_id'       => $order_id,
            'amount'         => $diff,
            'hash'           => $hash,
            'payment_method' => $order['fields']['payment_method'],
            'meta'           => $meta,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $payment['id'] = $this->savePayment($payment);
        return $payment;
    }

    public function savePayment($payment)
    {
        $db = ci()->db;
        $values = [
            'order_id'       => $payment['order_id'],
            'amount'         => (float) $payment['amount'],
            'hash'           => $db->escape($payment['hash']),
            'payment_method' => $db->escape($payment['payment_method']),
            'meta'           => !empty($payment['meta']) ? $db->escape(json_encode($payment['meta'], JSON_UNESCAPED_UNICODE)) : '',
            'created_at'     => $payment['created_at'],
        ];

        $table = $this->modx->getFullTablename('commerce_order_payments');

        if (isset($payment['id'])) {
            $db->update($values, $table, "`id` = '" . intval($payment['id']) . "'");
        } else {
            $payment['id'] = $db->insert($values, $table);
        }

        return $payment['id'];
    }

    public function getOrderPaymentsAmount($order_id)
    {
        $db = ci()->db;
        $payments = $db->select('*', $this->tablePayments, "`order_id` = '" . intval($order_id) . "' AND `paid` = 1");
        $amount = 0;

        while ($payment = $db->getRow($payments)) {
            $amount += (float)$payment['amount'];
        }

        return $amount;
    }

    public function processPayment($payment_id, $amount, $status = null)
    {
        $db = ci()->db;
        $commerce = ci()->commerce;

        if (is_array($payment_id) && !empty($payment_id['id']) && !empty($payment_id['order_id'])) {
            $payment    = $payment_id;
            $payment_id = $payment['id'];
        } else {
            $payment = $this->loadPayment($payment_id);
        }

        if (empty($payment)) {
            throw new Exception('Payment ' . print_r($payment_id, true) . ' not found!');
        }

        if (!empty($payment['paid'])) {
            throw new Exception('Payment ' . print_r($payment_id, true) . ' already paid!');
        }

        $order_id = $payment['order_id'];
        $order = $this->loadOrder($order_id);

        if (is_null($order)) {
            throw new Exception('Order ' . print_r($order_id, true) . ' not found!');
        }

        $statusCanBePaid = ci()->statuses->canBePaid($order['status_id']);

        if (!$statusCanBePaid) {
            throw new Exception('Order ' . print_r($order_id, true) . ' cannot be paid by status restriction!');
        }

        $db->update(['paid' => 1], $this->tablePayments, "`id` = '" . intval($payment_id) . "'");
        $total = $this->getOrderPaymentsAmount($order_id);
        $fullyPaid = $total >= $order['amount'] || abs($total - $order['amount']) < 0.01;

        $tpl = ci()->tpl;

        if (!empty($order['lang'])) {
            $prevLangCode = $commerce->setLang($order['lang']);
        }

        $lang = $commerce->getUserLanguage('order');

        if ($fullyPaid) {
            $history = $lang['order.order_full_paid'];
        } else {
            $history = $lang['order.order_paid'];
        }

        $comment = $tpl->parseChunk($history, [
            'order'  => $order,
            'amount' => ci()->currency->format($amount),
        ]);

        if (!empty($prevLangCode)) {
            $commerce->setLang($prevLangCode);
        }

        if (is_null($status)) {
            $status = $commerce->getSetting('status_id_after_payment', 3);
        }

        $this->changeStatus($order_id, $status, $comment, true);

        $notify = true;
        $this->modx->invokeEvent('OnOrderPaid', [
            'order_id'   => $order_id,
            'order'      => $order,
            'status_id'  => $status,
            'payment'    => $payment,
            'total'      => $total,
            'fully_paid' => $fullyPaid,
            'notify'     => &$notify
        ]);
        if($notify) {
            $this->getCart();
            $template = $commerce->getSetting('order_paid', $commerce->getUserLanguageTemplate('order_paid'));

            $body = $tpl->parseChunk($template, [
                'payment' => $payment,
                'amount'  => $amount,
                'order'   => $order,
            ], true);

            $subject = $tpl->parseChunk($lang['order.subject_order_paid'], [
                'order' => $order,
            ], true);

            $mailer = new \Helpers\Mailer($this->modx, [
                'to'      => $commerce->getSetting('email', $this->modx->getConfig('emailsender')),
                'subject' => $subject,
            ]);

            $mailer->send($body);
        }

        return true;
    }

    public function startOrder()
    {
        if (!$this->isOrderStarted()) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    public function isOrderStarted()
    {
        return isset($_SESSION[$this->sessionKey]);
    }

    public function updateRawData($data)
    {
        foreach (['formid', 'hashes'] as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        $this->modx->invokeEvent('OnOrderRawDataChanged', ['data' => &$data]);
        $_SESSION[$this->sessionKey] = $data;
    }

    public function getRawData()
    {
        if (isset($_SESSION[$this->sessionKey])) {
            return $_SESSION[$this->sessionKey];
        }

        return [];
    }

    public function populateOrderPlaceholders($order_id)
    {
        $order = $this->loadOrder($order_id);

        if (!empty($order)) {
            $placeholder = ['commerce_order' => $order];
            $this->modx->setPlaceholder('commerce_order', $order);
            $this->modx->toPlaceholders($placeholder);
        }

        $this->modx->invokeEvent('OnOrderPlaceholdersPopulated', [
            'order' => &$order,
        ]);
    }

    public function populatePaymentPlaceholders($payment_id)
    {
        $payment = $this->loadPayment($payment_id);

        if (!empty($payment) && $payment['paid'] == 1) {
            $placeholder = ['commerce_payment' => $payment];
            $this->modx->setPlaceholder('commerce_payment', $payment);
            $this->modx->toPlaceholders($placeholder);
        }
    }

    public function populateOrderPaymentLink($tpl = null)
    {
        $order = $this->getOrder();

        if ($order) {
            if ($tpl == null) {
                $lang = $this->modx->commerce->getUserLanguage('order');
                $tpl = $lang['order.order_payment_link'];
            }

            return ci()->tpl->parseChunk($tpl, [
                'order' => $order,
                'link'  => $this->modx->getConfig('site_url') . 'commerce/payorder?hash=' . $order['hash'],
            ], true);
        }

        return '';
    }

    public function getCurrentDelivery()
    {
        if (isset($_SESSION[$this->sessionKey]['delivery_method'])) {
            return $_SESSION[$this->sessionKey]['delivery_method'];
        }

        $order = $this->getOrder();

        if (isset($order['fields']['delivery_method'])) {
            return $order['fields']['delivery_method'];
        }

        $code = $this->modx->commerce->getSetting('default_delivery');

        if (!empty($code)) {
            return $code;
        }

        $deliveries = $this->modx->commerce->getDeliveries();

        if (count($deliveries)) {
            return key($deliveries);
        }

        return null;
    }

    public function setCurrentDelivery($delivery)
    {
        $this->startOrder();
        $old = $_SESSION[$this->sessionKey]['delivery_method'] ?? null;
        $_SESSION[$this->sessionKey]['delivery_method'] = $delivery;
        return $old;
    }

    public function getCurrentPayment()
    {
        if (isset($_SESSION[$this->sessionKey]['payment_method'])) {
            return $_SESSION[$this->sessionKey]['payment_method'];
        }

        $order = $this->getOrder();

        if (isset($order['fields']['payment_method'])) {
            return $order['fields']['payment_method'];
        }

        $code = $this->modx->commerce->getSetting('default_payment');

        if (!empty($code)) {
            return $code;
        }

        $payments = $this->modx->commerce->getPayments();

        if (count($payments)) {
            return key($payments);
        }

        return null;
    }

    public function setCurrentPayment($payment)
    {
        $this->startOrder();
        $old = $_SESSION[$this->sessionKey]['payment_method'] ?? null;
        $_SESSION[$this->sessionKey]['payment_method'] = $payment;
        return $old;
    }

    private function normalizePrice($price)
    {
        $price = str_replace(',', '.', $price);
        return number_format((float)$price, 6, '.', '');
    }
}
