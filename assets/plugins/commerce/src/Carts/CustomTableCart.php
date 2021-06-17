<?php

namespace Commerce\Carts;

use Commerce\Interfaces\Cart;

class CustomTableCart extends StoreCart implements Cart
{
    protected $modx;
    protected $titleField = 'pagetitle';
    protected $priceField = 'price';

    protected $table;
    protected $tableKey = 'id';
    protected $dbCache  = [];

    protected $instance;

    protected $rules = [
        'id' => [
            'numeric' => 'Item identificator should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Item identificator should be greater than 0',
            ]
        ],
        'count' => [
            'decimal' => 'Count should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Count should be greater than 0',
            ]
        ],
    ];

    public function __construct($modx, $instance = 'products', $table = 'site_content', $store = 'session')
    {
        $this->modx = $modx;
        $this->instance = $instance;

        $this->setTableName($table);

        parent::__construct(ci()->carts->getStore($store), $instance);
    }

    public function setTableName($table)
    {
        $this->table = $this->modx->getFullTablename($table);
    }

    protected function validateItem(array $item)
    {
        $result = $this->modx->commerce->validate($item, $this->rules);

        if ($result !== true && !empty($result)) {
            $this->modx->logEvent(0, 3, 'Item not added, validation fails.<br><pre>' . htmlentities(print_r($item, true)) . '<br>' . htmlentities(print_r($result, true)) . '</pre>', 'Commerce Cart');
            return false;
        }

        return true;
    }

    public function prepareItem(array $item)
    {
        $item = parent::prepareItem($item);

        if (!empty($item['id'])) {
            $item['name']  = $this->getItemName($item['id']);
            $item['price'] = $this->getItemPrice($item['id']);
        }

        return $item;
    }

    protected function beforeItemAdding(array &$item)
    {
        $isPrevented = false;

        $this->modx->invokeEvent('OnBeforeCartItemAdding', [
            'instance' => $this->instance,
            'item'     => &$item,
            'prevent'  => &$isPrevented,
        ]);

        return $isPrevented !== true;
    }

    protected function beforeItemUpdating(array &$item, &$row, $isAdded = false)
    {
        $isPrevented = false;

        $this->modx->invokeEvent('OnBeforeCartItemUpdating', [
            'instance' => $this->instance,
            'row'      => &$row,
            'item'     => &$item,
            'wasadded' => $isAdded,
            'prevent'  => &$isPrevented,
        ]);

        return $isPrevented !== true;
    }

    public function add(array $item, $isMultiple = false)
    {
        $result = parent::add($item);

        if ($result && !$isMultiple) {
            $this->modx->invokeEvent('OnCartChanged', [
                'instance' => $this->instance,
            ]);
        }

        return $result;
    }

    public function addMultiple(array $items = [])
    {
        $result = parent::addMultiple($items);

        foreach ($result as $isAdded) {
            if ($isAdded) {
                $this->modx->invokeEvent('OnCartChanged', [
                    'instance' => $this->instance,
                ]);

                break;
            }
        }

        return $result;
    }

    public function update($row, array $attributes = [], $isAdded = false)
    {
        $result = parent::update($row, $attributes, $isAdded);

        if ($result && !$isAdded) {
            $this->modx->invokeEvent('OnCartChanged', [
                'instance' => $this->instance,
            ]);
        }

        return $result;
    }

    public function remove($row)
    {
        $isPrevented = false;

        $this->modx->invokeEvent('OnBeforeCartItemRemoving', [
            'by'      => 'row',
            'row'     => &$row,
            'prevent' => &$isPrevented,
        ]);

        if ($isPrevented) {
            return false;
        }

        $result = parent::remove($row);

        if ($result) {
            $this->modx->invokeEvent('OnCartChanged', [
                'instance' => $this->instance,
            ]);
        }

        return $result;
    }

    public function removeById($id)
    {
        $isPrevented = false;

        $this->modx->invokeEvent('OnBeforeCartItemRemoving', [
            'by'      => 'id',
            'id'      => &$id,
            'prevent' => &$isPrevented,
        ]);

        if ($isPrevented) {
            return false;
        }

        $result = parent::removeById($id);

        if ($result) {
            $this->modx->invokeEvent('OnCartChanged', [
                'instance' => $this->instance,
            ]);
        }

        return $result;
    }

    public function clean()
    {
        $isPrevented = false;

        $this->modx->invokeEvent('OnBeforeCartCleaning', [
            'instance' => $this->instance,
            'prevent'  => &$isPrevented,
        ]);

        if ($isPrevented) {
            return false;
        }

        $result = parent::clean();

        $this->modx->invokeEvent('OnCartChanged', [
            'instance' => $this->instance,
        ]);

        return $result;
    }

    public function setTitleField($field)
    {
        if (is_string($field) && preg_match('/^[A-Za-z0-9_-]+$/', $field)) {
            $this->titleField = $field;
        } else {
            $this->modx->logEvent(0, 3, 'Name "' . print_r($field, true) . '" must be valid field name!', 'Commerce Cart - cannot set title field');
        }
    }

    public function setPriceField($field)
    {
        if (is_string($field) && preg_match('/^[A-Za-z0-9_-]+$/', $field)) {
            $this->priceField = $field;
        } else {
            $this->modx->logEvent(0, 3, 'Name "' . print_r($field, true) . '" must be valid field name!', 'Commerce Cart - cannot set price field');
        }
    }

    protected function getTableRow($id)
    {
        if (!isset($this->dbCache[$id])) {
            if (is_numeric($id) && $id > 0) {
                $db = $this->modx->db;
                $this->dbCache[$id] = $db->getRow($db->select('*', $this->table, "`{$this->tableKey}` = '" . $db->escape($id) . "'"));
            } else {
                $this->dbCache[$id] = null;
            }
        }

        return $this->dbCache[$id];
    }

    protected function getItemName($id)
    {
        if (is_scalar($id) && $row = $this->getTableRow($id)) {
            return $row[$this->titleField];
        }

        return $this->defaults['name'];
    }

    protected function getItemPrice($id)
    {
        if (is_scalar($id) && $row = $this->getTableRow($id)) {
            return ci()->currency->convertFromDefault($row[$this->priceField]);
        }

        return $this->defaults['price'];
    }

    public function getSubtotals(array &$rows, &$total)
    {
        $this->modx->invokeEvent('OnCollectSubtotals', [
            'rows'     => &$rows,
            'total'    => &$total,
            'instance' => $this->instance,
        ]);
    }
}