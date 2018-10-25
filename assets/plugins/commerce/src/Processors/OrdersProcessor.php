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

    protected $sessionKey = 'commerce.order';

    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->tableOrders   = $modx->getFullTablename($this->tableOrders);
        $this->tableProducts = $modx->getFullTablename($this->tableProducts);
        $this->tableStatuses = $modx->getFullTablename($this->tableStatuses);
        $this->tableHistory  = $modx->getFullTablename($this->tableHistory);
    }

    protected function prepareItem($order_id, $position, $item)
    {
        return [
            'order_id'   => $order_id,
            'product_id' => (int)$item['id'],
            'title'      => $this->modx->db->escape($item['name']),
            'price'      => number_format((float)$item['price'], 6),
            'count'      => number_format((float)$item['count'], 6),
            'options'    => !empty($item['options']) ? $this->modx->db->escape(json_encode($item['options'], JSON_UNESCAPED_UNICODE)) : null,
            'meta'       => !empty($item['meta']) ? $this->modx->db->escape(json_encode($item['meta'], JSON_UNESCAPED_UNICODE)) : null,
            'position'   => $position,
        ];
    }

    protected function prepareSubtotal($order_id, $position, $item)
    {
        return [
            'order_id' => $order_id,
            'title'    => $this->modx->db->escape($item['title']),
            'price'    => number_format((float)$item['price'], 6),
            'position' => $position,
        ];
    }

    protected function prepareValues($fields)
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
            $total += $item['price'] * $item['count'];
        }

        $subtotals = [];
        $this->modx->invokeEvent('OnCollectSubtotals', [
            'rows'  => &$subtotals,
            'total' => &$total,
        ]);

        $values = $this->prepareValues($fields);
        $values['amount']   = number_format((float)$total, 6);
        $values['currency'] = ci()->currency->getCurrencyCode();

        $this->modx->invokeEvent('OnBeforeOrderSaving', [
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
        ]);

        $order_id = $this->modx->db->insert($values, $this->tableOrders);
        $this->order_id = $order_id;

        $position = 1;

        foreach ($items as $item) {
            $this->modx->db->insert($this->prepareItem($order_id, $position++, $item), $this->tableProducts);
        }

        foreach ($subtotals as $item) {
            $this->modx->db->insert($this->prepareSubtotal($order_id, $position++, $item), $this->tableProducts);
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
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
        ]);

        $this->loadOrder($order_id);
        $this->getCart();
    }

    public function changeStatus($order_id, $status_id, $comment = '', $notify = false, $template = 'order_notify')
    {
        $order = $this->loadOrder($order_id);

        if (empty($order) || empty($order['email'])) {
            return false;
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

        if ($notify) {
            $parser = \DLTemplate::getInstance($this->modx);
            $lang   = $this->modx->commerce->getUserLanguage('order');
            $status = $this->modx->db->getValue($this->modx->db->select('title', $this->modx->getFullTablename('commerce_order_statuses'), "`id` = '" . intval($status_id) . "'"));

            $body = $parser->parseChunk($template, [
                'order_id' => $order_id,
                'status'   => $status,
                'comment'  => $comment,
            ], true);

            $subject = $parser->parseChunk($lang['order.subject_status_changed'], [
                'order_id' => $order_id,
            ], true);

            $mailer = new \Helpers\Mailer($this->modx, [
                'to'      => $order['email'],
                'subject' => $subject,
            ]);

            $mailer->send($body);
        }

        return true;
    }

    public function getOrder()
    {
        if (!empty($this->order) && !empty($this->order_id)) {
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

            $this->modx->invokeEvent('OnBeforePaymentProcess', [
                'order'   => &$order,
                'payment' => $payment,
            ]);

            $link = $payment['processor']->getPaymentLink();

            if (!empty($link)) {
                $FL->config->setConfig(['redirectTo' => [
                    'page'   => $link,
                    'header' => 'HTTP/1.1 301 Moved Permanently',
                ]]);
            } else {
                $markup = $payment['processor']->getPaymentMarkup();

                if (!empty($markup)) {
                    $FL->config->setConfig(['successTpl' => $markup]);
                }
            }
        }
    }

    public function processPayment($order_id, $amount)
    {
        $order = $this->loadOrder($order_id);

        if (!is_null($order) && $order['amount'] == $amount) {
            $status = $this->modx->commerce->getSetting('status_id_after_payment', 3);
            $lang = $this->modx->commerce->getUserLanguage('order');
            $comment = \DLTemplate::getInstance($this->modx)->parseChunk($lang['order.order_paid'], [
                'order_id' => $order_id,
            ]);
            $this->changeStatus($order_id, $status, $comment, true);
            return true;
        }

        return false;
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
