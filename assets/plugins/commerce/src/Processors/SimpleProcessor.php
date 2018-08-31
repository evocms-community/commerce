<?php

namespace Commerce\Processors;

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
    
    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->tableOrders   = $modx->getFullTablename($this->tableOrders);
        $this->tableProducts = $modx->getFullTablename($this->tableProducts);
        $this->tableStatuses = $modx->getFullTablename($this->tableStatuses);
        $this->tableHistory  = $modx->getFullTablename($this->tableHistory);
    }

    public function create(array $items, array $fields)
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

        $values = [];

        foreach (['name', 'email', 'phone'] as $field) {
            if (isset($fields[$field])) {
                $values[$field] = $fields[$field];
                unset($fields[$field]);
            }
        }

        $values['amount'] = (int)$total;
        $values['fields'] = $this->modx->db->escape(json_encode($fields, JSON_UNESCAPED_UNICODE));

        $this->modx->invokeEvent('OnBeforeOrderSaving', [
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
            'total'     => &$total,
        ]);

        $this->checkTables();

        $order_id = $this->modx->db->insert($values, $this->tableOrders);
        $this->order_id = $order_id;

        $position = 1;

        foreach ($items as $item) {
            $this->modx->db->insert([
                'order_id'   => $order_id,
                'product_id' => (int)$item['id'],
                'title'      => $this->modx->db->escape($item['title']),
                'price'      => (float)$item['price'],
                'count'      => (float)$item['count'],
                'options'    => !empty($item['options']) ? $this->modx->db->escape(json_encode($item['options'], JSON_UNESCAPED_UNICODE)) : null,
                'meta'       => !empty($item['meta']) ? $this->modx->db->escape(json_encode($item['meta'], JSON_UNESCAPED_UNICODE)) : null,
                'position'   => $position++,
            ], $this->tableProducts);
        }

        foreach ($subtotals as $item) {
            $this->modx->db->insert([
                'order_id' => $order_id,
                'title'    => $this->modx->db->escape($item['title']),
                'price'    => (float)$item['price'],
                'position' => $position++,
            ], $this->tableProducts);
        }

        $this->updateHistory($this->modx->commerce->getSetting('default_order_status', 1));

        $this->modx->invokeEvent('OnOrderSaved', [
            'values'    => &$values,
            'items'     => &$items,
            'fields'    => &$fields,
            'subtotals' => &$subtotals,
            'total'     => &$total,
        ]);

        $this->load($order_id);
        $this->getCart();
    }

    public function updateHistory($status_id, $comment = '', $notify = false)
    {
        if (!empty($this->order_id)) {
            $this->modx->db->insert([
                'order_id'  => (int)$this->order_id,
                'status_id' => (int)$status_id,
                'comment'   => $this->modx->db->escape($comment),
                'notify'    => 1 * !!$notify,
            ], $this->tableHistory);

            if ($notify) {
                // TODO: notify customer
            }
        }
    }

    public function get()
    {
        if (!empty($this->order) && !empty($this->order_id)) {
            $this->load($this->order_id);
        }

        if (!empty($this->order)) {
            return $this->order;
        }

        return null;
    }

    public function load($order_id)
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
            $this->cart = new \Commerce\Carts\DocListerOrderCart($modx);
            \Commerce\CartsManager::getManager()->addCart('order', $this->cart);

            if (!empty($this->order_id)) {
                $query = $this->modx->db->select('*', $this->tableProducts, "`order_id` = '{$this->order_id}'");
                $items = [];
                $subtotals = [];

                while ($item = $this->modx->db->getRow($query)) {
                    $item['options'] = json_decode($item['options'], true);
                    $item['meta'] = json_decode($item['meta'], true);
                    
                    if (!is_null($item['product_id'])) {
                        $items[] = $item;
                    } else {
                        $subtotals[] = $item;
                    }
                }

                $this->cart->setItems($items);
                $this->cart->setSubtotals($subtotals);
            }
        }

        return $this->cart;
    }

    public function processPayment()
    {
        $order = $this->get();

        if (!empty($order['fields']['payment_method'])) {
            $payment = $this->modx->commerce->getPayment($order['fields']['payment_method']);

            $link = $payment['processor']->getPaymentLink();
var_dump($link);
            if ($link !== false) {
                $this->setConfig(['redirectTo' => $link]);
            } else {
                $markup = $payment['processor']->getPaymentMarkup();
                $this->setconfig(['successTpl' => $markup]);
            }
        }
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

    protected function checkTables()
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
                    `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");

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

        if (!$this->isTableExists($this->tableStatuses)) {
            $this->modx->db->query("
                CREATE TABLE IF NOT EXISTS {$this->tableStatuses} (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `title` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            ");

            $lang = $this->modx->commerce->getUserLanguage('order');

            $this->modx->db->insert(['title' => $lang['order.status.new']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.processing']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.paid']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.shipped']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.canceled']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.complete']], $this->tableStatuses);
            $this->modx->db->insert(['title' => $lang['order.status.pending']], $this->tableStatuses);
        }
    }
}
