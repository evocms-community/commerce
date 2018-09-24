<?php

namespace Commerce\Carts;

use Commerce\Interfaces\CartStore;

class ProductsCart extends StoreCart implements \Commerce\Interfaces\Cart
{
    protected $modx;
    protected $titleField = 'pagetitle';
    protected $priceField = 'price';
    
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

    public function __construct($modx, $instance = 'cart')
    {
        $this->modx = $modx;
        parent::__construct(new SessionCartStore(), $instance);
    }

    public function add(array $item)
    {
        $this->modx->invokeEvent('OnBeforeCartItemAdding', [
            'instance' => $this->instance,
            'item'     => &$item,
        ]);

        return parent::add($item);
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
        if (is_string($field) && preg_match('/^[a-z_]+$/', $field)) {
            $this->titleField = $field;
        } else {
            throw new \Exception('Name "' . print_r($field, true) . '" must be valid field name!');
        }
    }

    public function setPriceField($field)
    {
        if (is_string($field) && preg_match('/^[a-z_]+$/', $field)) {
            $this->priceField = $field;
        } else {
            throw new \Exception('Name "' . print_r($field, true) . '" must be valid field name!');
        }
    }

    protected function getItemName($id)
    {
        if (is_numeric($id) && $id > 0) {
            $doc = $this->modx->getDocument($id);

            if (!empty($doc)) {
                return $doc[$this->titleField];
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

    protected function prepareItem(array $item)
    {
        if (!empty($item['id'])) {
            $item['name']  = $this->getItemName($item['id']);
            $item['price'] = $this->getItemPrice($item['id']);
        }

        return parent::prepareItem($item);
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