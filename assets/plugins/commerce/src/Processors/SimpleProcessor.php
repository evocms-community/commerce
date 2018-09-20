<?php

namespace Commerce\Processors;

use Commerce\Carts\OrderCart;
use Commerce\CartsManager;

class SimpleProcessor implements \Commerce\Interfaces\Processor
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
            'price'      => (float)$item['price'],
            'count'      => (float)$item['count'],
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
            'price'    => (float)$item['price'],
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
        $values['amount'] = (int)$total;

        $this->modx->invokeEvent('OnBeforeOrderSaving', [
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
        ]);

        $this->checkTables();

        $order_id = $this->modx->db->insert($values, $this->tableOrders);
        $this->order_id = $order_id;

        $position = 1;

        foreach ($items as $item) {
            $this->modx->db->insert($this->prepareItem($order_id, $position++, $item), $this->tableProducts);
        }

        foreach ($subtotals as $item) {
            $this->modx->db->insert($this->prepareSubtotal($order_id, $position++, $item), $this->tableProducts);
        }

        $this->changeStatus($order_id, $this->modx->commerce->getSetting('default_order_status', 1));

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
        if (is_null($this->cart)) {
            $this->cart = new OrderCart($this->modx);
            CartsManager::getManager()->addCart('order', $this->cart);

            if (!empty($this->order_id)) {
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

    private function isTableExists($table)
    {
        try {
            $query = $this->modx->db->query("SHOW FIELDS FROM " . $table, false);
        } catch (\Exception $e) {
            return false;
        }

        return $this->modx->db->getRecordCount($query) > 0;
    }

    protected function checkTableOrders()
    {
        if (!$this->isTableExists($this->tableOrders)) {
            $this->modx->db->query("
                CREATE TABLE IF NOT EXISTS {$this->tableOrders} (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) NULL,
                    `phone` varchar(255) NULL,
                    `email` varchar(255) NULL,
                    `amount` float NOT NULL DEFAULT '0',
                    `fields` text,
                    `status_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");
        }
    }

    protected function checkTableProducts()
    {
        if (!$this->isTableExists($this->tableProducts)) {
            $this->modx->db->query("
                CREATE TABLE IF NOT EXISTS {$this->tableProducts} (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(10) unsigned NOT NULL,
                    `product_id` int(10) unsigned NULL,
                    `title` varchar(255) NOT NULL,
                    `price` float NOT NULL,
                    `count` float unsigned NOT NULL DEFAULT '1',
                    `options` text NULL,
                    `meta` text NULL,
                    `position` tinyint(3) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`,`product_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");
        }
    }

    protected function checkTableHistory()
    {
        if (!$this->isTableExists($this->tableHistory)) {
            $this->modx->db->query("
                CREATE TABLE IF NOT EXISTS {$this->tableHistory} (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(10) unsigned NOT NULL,
                    `status_id` int(10) unsigned NOT NULL,
                    `comment` text NOT NULL,
                    `notify` tinyint(1) unsigned NOT NULL DEFAULT '1',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `order_id` (`order_id`,`status_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");
        }
    }

    protected function checkTableStatuses()
    {
        if (!$this->isTableExists($this->tableStatuses)) {
            $this->modx->db->query("
                CREATE TABLE IF NOT EXISTS {$this->tableStatuses} (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `title` varchar(255) NOT NULL,
                    `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");

            $lang = $this->modx->commerce->getUserLanguage('order');

            $this->modx->db->insert(['title' => $lang['order.status.new']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.processing']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.paid'], 'notify' => 1], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.shipped']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.canceled'], 'notify' => 1], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.complete']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.pending']], $this->tableStatuses);
        }
    }

    protected function checkTables()
    {
        $this->checkTableOrders();
        $this->checkTableProducts();
        $this->checkTableHistory();
        $this->checkTableStatuses();
    }
}
