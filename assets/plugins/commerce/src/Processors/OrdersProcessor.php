<?php

namespace Commerce\Processors;

use Commerce\Carts\OrderCart;

class OrdersProcessor implements \Commerce\Interfaces\Processor
{
    private $modx;

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

    protected function prepareOrderProduct($order_id, $position, $item)
    {
        return [
            'order_id'   => $order_id,
            'product_id' => (int)$item['id'],
            'title'      => $this->modx->db->escape(isset($item['title']) ? $item['title'] : $item['name']),
            'price'      => number_format((float)$item['price'], 6, '.', ''),
            'count'      => number_format((float)$item['count'], 6, '.', ''),
            'options'    => !empty($item['options']) ? $this->modx->db->escape(json_encode($item['options'], JSON_UNESCAPED_UNICODE)) : null,
            'meta'       => !empty($item['meta']) ? $this->modx->db->escape(json_encode($item['meta'], JSON_UNESCAPED_UNICODE)) : null,
            'position'   => $position,
        ];
    }

    protected function prepareOrderSubtotal($order_id, $position, $item)
    {
        return [
            'order_id' => $order_id,
            'title'    => $this->modx->db->escape($item['title']),
            'price'    => number_format((float)$item['price'], 6, '.', ''),
            'position' => $position,
        ];
    }

    protected function prepareOrderValues($fields)
    {
        $values = [];

        foreach (['name', 'email', 'phone'] as $field) {
            if (isset($fields[$field])) {
                $values[$field] = $fields[$field];
                unset($fields[$field]);
            }
        }

        $values['fields'] = $this->modx->db->escape(json_encode($fields, JSON_UNESCAPED_UNICODE));
        return $values;
    }

    public function createOrder(array $items, array $fields)
    {
        $total = 0;

        foreach ($items as $item) {
            $total += (float)$item['price'] * (float)$item['count'];
        }

        $subtotals = [];
        $this->modx->invokeEvent('OnCollectSubtotals', [
            'rows'     => &$subtotals,
            'total'    => &$total,
            'realonly' => true,
        ]);

        $values = $this->prepareOrderValues($fields);
        $values['amount']     = number_format((float)$total, 6, '.', '');
        $values['currency']   = ci()->currency->getCurrencyCode();
        $values['created_at'] = date('Y-m-d H:i:s');
        $values['hash']       = $this->modx->commerce->generateRandomString();

        $this->modx->invokeEvent('OnBeforeOrderSaving', [
            'order_id'  => null,
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
        ]);

        $order_id = $this->modx->db->insert($values, $this->tableOrders);
        $this->order_id = $order_id;

        $position = 1;

        foreach ($items as $item) {
            $this->modx->db->insert($this->prepareOrderProduct($order_id, $position++, $item), $this->tableProducts);
        }

        foreach ($subtotals as $item) {
            $this->modx->db->insert($this->prepareOrderSubtotal($order_id, $position++, $item), $this->tableProducts);
        }

        $defaultStatus = ci()->cache->getOrCreate('default_status', function() {
            $db = ci()->db;
            $query = $db->select('id', $this->modx->getFullTablename('commerce_order_statuses'), "`default` = 1");

            if (!$db->getRecordCount($query)) {
                throw new \Exception('Default status not found');
            }

            return $db->getValue($query);
        });

        $this->changeStatus($order_id, $defaultStatus);

        $this->modx->invokeEvent('OnOrderSaved', [
            'order_id'  => $order_id,
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
        ]);

        $order = $this->loadOrder($order_id);
        $this->getCart();

        return $order;
    }

    public function changeStatus($order_id, $status_id, $comment = '', $notify = false, $template = null)
    {
        $order = $this->loadOrder($order_id);

        if (empty($order)) {
            return false;
        }

        if (is_null($template)) {
            $template = $this->modx->commerce->getSetting('status_notification', $this->modx->commerce->getUserLanguageTemplate('status_notification'));
        }

        $this->modx->invokeEvent('OnBeforeOrderHistoryUpdate', [
            'order_id'  => $order_id,
            'status_id' => &$status_id,
            'comment'   => &$comment,
            'notify'    => &$notify,
            'template'  => &$template,
        ]);

        $this->modx->db->update(['status_id' => $status_id], $this->tableOrders, "`id` = '$order_id'");

        $this->modx->db->insert([
            'order_id'  => (int)$order_id,
            'status_id' => (int)$status_id,
            'comment'   => $this->modx->db->escape($comment),
            'notify'    => 1 * !!$notify,
            'user_id'   => $this->modx->getLoginUserID('mgr'),
        ], $this->tableHistory);

        if ($notify && !empty($order['email'])) {
            $tpl    = ci()->tpl;
            $lang   = $this->modx->commerce->getUserLanguage('order');
            $status = $this->modx->db->getValue($this->modx->db->select('title', $this->modx->getFullTablename('commerce_order_statuses'), "`id` = '" . intval($status_id) . "'"));

            $body = $tpl->parseChunk($template, [
                'order_id' => $order_id,
                'order'    => $order,
                'status'   => $status,
                'comment'  => $comment,
            ], true);

            $subject = $tpl->parseChunk($lang['order.subject_status_changed'], [
                'order_id' => $order_id,
            ], true);

            $mailer = new \Helpers\Mailer($this->modx, [
                'to'      => $order['email'],
                'subject' => $subject,
            ]);

            return $mailer->send($body);
        }

        return true;
    }

    public function updateOrder($order_id, $data = [])
    {
        $params = [
            'order_id' => $order_id,
        ];

        foreach (['values', 'items', 'subtotals'] as $field) {
            if (isset($data[$field])) {
                $params[$field] = &$data[$field];
            }
        }

        $this->modx->invokeEvent('OnBeforeOrderSaving', $params);

        $db = $this->modx->db;

        $db->query('START TRANSACTION;');

        try {
            $position = 1;
            $totalPrice = 0;

            if (isset($params['items'])) {
                $exists = [];

                foreach ($params['items'] as $item) {
                    $row_id = !empty($item['order_row_id']) ? $item['order_row_id'] : 0;
                    $item = $this->prepareOrderProduct($order_id, $position++, $item);

                    if (!empty($row_id)) {
                        $db->update($item, $this->tableProducts, "id = '$row_id'");
                        $exists[] = $row_id;
                    } else {
                        $exists[] = $db->insert($item, $this->tableProducts);
                    }

                    $totalPrice += $item['price'] * $item['count'];
                }

                $db->delete($this->tableProducts, "`order_id` = '$order_id' AND `product_id` IS NOT NULL AND `id` NOT IN ('" . implode("', '", $exists) . "')");
            }

            if (isset($params['subtotals'])) {
                $exists = [];

                foreach ($params['subtotals'] as $item) {
                    $row_id = !empty($item['id']) ? $item['id'] : 0;
                    $item = $this->prepareOrderSubtotal($order_id, $position++, $item);

                    if (!empty($row_id)) {
                        $db->update($item, $this->tableProducts, "id = '$row_id'");
                        $exists[] = $row_id;
                    } else {
                        $exists[] = $db->insert($item, $this->tableProducts);
                    }

                    $totalPrice += $item['price'];
                }

                $db->delete($this->tableProducts, "`order_id` = '$order_id' AND `product_id` IS NULL AND `id` NOT IN ('" . implode("', '", $exists) . "')");
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

                $db->update($params['values'], $this->tableOrders, "id = '" . $order['id'] . "'");
            }
        } catch (\Exception $e) {
            $db->query('ROLLBACK;');
            return false;
        }

        $db->query('COMMIT;');

        $this->modx->invokeEvent('OnOrderSaved', $params);

        return true;
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

    public function loadOrder($order_id)
    {
        $query = $this->modx->db->select('*', $this->tableOrders, "`id` = '" . (int)$order_id . "'");

        if ($this->modx->db->getRecordCount($query)) {
            $this->order = $this->modx->db->getRow($query);
            $this->order['fields'] = json_decode($this->order['fields'], true);
            $this->order_id = $this->order['id'];
            return $this->order;
        }

        return null;
    }

    public function loadOrderByHash($order_hash)
    {
        $query = $this->modx->db->select('*', $this->tableOrders, "`hash` = '" . $db->escape((string)$order_hash) . "'");

        if ($this->modx->db->getRecordCount($query)) {
            $this->order = $this->modx->db->getRow($query);
            $this->order['fields'] = json_decode($this->order['fields'], true);
            $this->order_id = $this->order['id'];
            return $this->order;
        }

        return null;
    }

    public function getCart()
    {
        $order = $this->getOrder();

        if (is_null($this->cart) && !is_null($order)) {
            $this->cart = new OrderCart($this->modx);
            $this->cart->setCurrency($order['currency']);

            ci()->carts->addCart('order', $this->cart);

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

        if (isset($_SESSION[$this->sessionKey])) {
            unset($_SESSION[$this->sessionKey]);
        }

        if (!empty($order['fields']['payment_method'])) {
            $payment = $this->modx->commerce->getPayment($order['fields']['payment_method']);

            $redirectText = '';

            $this->modx->invokeEvent('OnBeforePaymentProcess', [
                'FL'      => $FL,
                'order'   => &$order,
                'payment' => $payment,
                'redirect_text' => &$redirectText,
            ]);

            ci()->flash->set('last_order_id', $order['id']);
            $redirect = $payment['processor']->createPaymentRedirect();

            if ($redirect) {
                if (empty($redirectText)) {
                    $lang = $this->modx->commerce->getUserLanguage('order');
                    $redirectText = $lang['order.redirecting_to_payment'];
                }

                $successTpl = '@CODE:' . $redirectText;
    
                if (!empty($redirect['link'])) {
                    $FL->config->setConfig(['redirectTo' => [
                        'page'   => $redirect['link'],
                        'header' => 'HTTP/1.1 301 Moved Permanently',
                    ]]);
                } else {
                    $successTpl .= $redirect['markup'];
                }

                $FL->config->setConfig(['successTpl' => $successTpl]);
            }
        }
    }

    public function payOrderByHash($hash)
    {
        $db = ci()->db;
        $order = $db->getRow($db->select('*', $this->tableOrders, "`hash` = '" . $db->escape($hash) . "'"));

        if (!empty($order)) {
            $payments = $db->select('*', $this->tablePayments, "`order_id` = '" . $order['id'] . "' AND `paid` = 1");
            $amount = 0;

            while ($payment = $db->getRow($payments)) {
                $amount += $payment['amount'];
            }

            if ($amount >= $order['amount']) {
                return false;
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

    public function loadPayment($payment_id)
    {
        $db = ci()->db;
        $payment = $db->getRow($db->select('*', $this->tablePayments, "`id` = '" . intval($payment_id) . "'"));

        if (!empty($payment)) {
            return $payment;
        }

        return null;
    }

    public function loadPaymentByHash($hash)
    {
        $db = ci()->db;
        $payment = $db->getRow($db->select('*', $this->tablePayments, "`hash` = '" . $db->escape($hash) . "'"));

        if (!empty($payment)) {
            return $payment;
        }

        return null;
    }

    public function processPayment($payment_id, $amount)
    {
        $db = ci()->db;
        $payment = $this->loadPayment($payment_id);

        if (empty($payment) || !empty($payment['paid'])) {
            return false;
        }

        $order_id = $payment['order_id'];

        $order = $this->loadOrder($order_id);

        if (is_null($order)) {
            return false;
        }

        $db->update(['paid' => 1], $this->tablePayments, "`id` = '" . intval($payment_id) . "'");

        $total = 0;
        $query = $db->select('*', $this->tablePayments, "`order_id` = '$order_id' AND `paid` > '0'");

        while ($row = $db->getRow($query)) {
            $total += (float)$row['amount'];
        }

        if ($order['amount'] >= $amount) {
            $tpl = ci()->tpl;
            $status = $this->modx->commerce->getSetting('status_id_after_payment', 3);
            $lang = $this->modx->commerce->getUserLanguage('order');
            $comment = $tpl->parseChunk($lang['order.order_paid'], [
                'order_id' => $order_id,
            ]);
            $this->changeStatus($order_id, $status, $comment, true);

            $this->getCart();
            $template = $this->modx->commerce->getSetting('order_paid', $this->modx->commerce->getUserLanguageTemplate('order_paid'));

            $body = $tpl->parseChunk($template, [
                'payment' => $payment,
                'amount'  => $amount,
                'order'   => $order,
            ], true);

            $subject = $tpl->parseChunk($lang['order.subject_order_paid'], [
                'order_id' => $order_id,
            ], true);

            $mailer = new \Helpers\Mailer($this->modx, [
                'to'      => $this->modx->commerce->getSetting('email', $this->modx->getConfig('emailsender')),
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
        $this->modx->invokeEvent('OnOrderRawDataChanged', ['data' => $data]);
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
            $this->modx->setPlaceholder('commerce_order', $order);

            foreach ($order as $key => $value) {
                $this->modx->setPlaceholder('commerce_order.' . $key, $value);
            }
        }
    }

    public function populatePaymentPlaceholders($payment_id)
    {
        $payment = $this->loadPayment($payment_id);

        if (!empty($payment) && $payment['paid'] == 1) {
            $this->modx->setPlaceholder('commerce_payment', $payment);

            foreach ($payment as $key => $value) {
                $this->modx->setPlaceholder('commerce_payment.' . $key, $value);
            }
        }
    }

    public function populateOrderPaymentLink()
    {
        $order = $this->getOrder();

        if ($order) {
            $lang = $this->modx->commerce->getUserLanguage('order');
            return ci()->tpl->parseChunk($lang['order.order_payment_link'], [
                'order' => $order,
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
}
