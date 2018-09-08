<?php

namespace Commerce\Carts;

class SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $items = [];
    protected $instance;

    protected $rules = [
        'id' => [
            'numeric' => 'Item identificator should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Item identificator should be greater than 0',
            ]
        ],
        'name' => [
            'alpha' => 'Item name should be a string',
            'lengthBetween' => [
                'params'  => [1, 255],
                'message' => 'Item name length sould be between 1 and 255',
            ]
        ],
        'count' => [
            'decimal' => 'Count should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Count should be greater than 0',
            ]
        ],
        'price' => [
            'decimal' => 'Price should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Price should be greater than 0',
            ]
        ],
    ];

    protected $defaults = [
        'id'      => 0,
        'name'    => 'Item',
        'count'   => 1,
        'price'   => 0,
        'options' => [],
        'meta'    => [],
    ];

    protected $titleField = 'pagetitle';

    public function getTotal()
    {
        $total = 0;

        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $total += $item['price'] * $item['count'];
            }
        }

        return $total;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function get($row)
    {
        if (isset($this->items[$row])) {
            return $this->items[$row];
        }

        return null;
    }

    public function setItems(array $items)
    {
        foreach ($items as $item) {
            if (!$this->validateItem($item)) {
                return false;
            }
        }

        $this->items = $items;
        return true;
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

    protected function prepareItem(array $item)
    {
        if (empty($item['name']) && !empty($item['id'])) {
            $item['name'] = $this->getItemName($item['id']);
        }

        $item = array_filter(array_merge($this->defaults, $item), function($key) {
            return isset($this->defaults[$key]);
        }, ARRAY_FILTER_USE_KEY);

// TODO: sanitar options and meta?
        return $item;
    }

    protected function validateItem(array $item)
    {
        $formlister = new \FormLister\Form($this->modx);
        $validator  = new \FormLister\Validator;

        $result = $formlister->validate($validator, $this->rules, $item);

        if ($result !== true && !empty($result)) {
            $this->modx->logEvent(0, 3, 'Item not added, validation fails.<br><pre>' . htmlentities(print_r($item, true)) . '<br>' . htmlentities(print_r($result, true)) . '</pre>', 'Commerce Cart');
            return false;
        }

        return true;
    }

    protected function makeHash(array $item)
    {
        return md5(serialize([$item['id'], $item['name'], $item['price'], $item['options'], $item['meta']]));
    }

    public function add(array $item)
    {
        $new = $this->prepareItem($item);

        if ($this->validateItem($new)) {
            $new['hash'] = $this->makeHash($new);

            foreach ($this->items as $row => $item) {
                if ($item['hash'] == $new['hash']) {
                    $this->update($row, ['count' => $item['count'] + 1]);
                    return $row;
                }
            }

            $row = uniqid();
            $new['row'] = $row;
            $this->items[$row] = $new;

            return $row;
        }

        return false;
    }

    public function addMultiple(array $items = [])
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = call_user_method('add', $this, $item);
        }

        return $result;
    }

    public function update($row, array $attributes = [])
    {
        if (isset($this->items[$row])) {
            $new = array_merge($this->items[$row], array_filter($attributes, function($key) {
                return isset($this->defaults[$key]);
            }, ARRAY_FILTER_USE_KEY));

            if ($this->validateItem($new)) {
                $this->items[$row] = $new;
                return true;
            }
        }

        return false;
    }

    public function remove($row)
    {
        if (isset($this->items[$row])) {
            unset($this->items[$row]);
            return true;
        }

        return false;
    }

    public function clean()
    {
        $this->items = [];
    }
}
