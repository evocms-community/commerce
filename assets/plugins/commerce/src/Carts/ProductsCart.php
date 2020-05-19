<?php

namespace Commerce\Carts;

use Commerce\Interfaces\CartStore;

class ProductsCart extends StoreCart implements \Commerce\Interfaces\Cart
{
    protected $modx;
    protected $titleField = 'pagetitle';
    protected $priceField = 'price';

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

    public function __construct($modx, $instance = 'products')
    {
        $this->modx = $modx;
        $this->instance = $instance;
        parent::__construct(new SessionCartStore(), $instance);
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
        parent::clean();

        $this->modx->invokeEvent('OnCartChanged', [
            'instance' => $this->instance,
        ]);
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

    public function setTitleField($field)
    {
        if (is_string($field) && preg_match('/^[A-Za-z0-9_]+$/', $field)) {
            $this->titleField = $field;
        } else {
            throw new \Exception('Name "' . print_r($field, true) . '" must be valid field name!');
        }
    }

    public function setPriceField($field)
    {
        if (is_string($field) && preg_match('/^[A-Za-z0-9_]+$/', $field)) {
            $this->priceField = $field;
        } else {
            throw new \Exception('Name "' . print_r($field, true) . '" must be valid field name!');
        }
    }

    protected function getItemName($id)
    {
        if (is_numeric($id) && $id > 0) {
            if (in_array($this->titleField, ['pagetitle', 'longtitle', 'description', 'introtext', 'menutitle'])) {
                $doc = $this->modx->getDocument($id);

                if (!empty($doc)) {
                    return $doc[$this->titleField];
                }
            } else {
                $tv = $this->modx->getTemplateVar($this->titleField, '*', $id);

                if (!empty($tv)) {
                    return $tv['value'];
                }
            }
        }

        return $this->defaults['name'];
    }

    protected function getItemPrice($id)
    {
        if (is_numeric($id) && $id > 0) {
            $tv = $this->modx->getTemplateVar($this->priceField, '*', $id);

            if (!empty($tv)) {
                return ci()->currency->convertFromDefault($tv['value']);
            }
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