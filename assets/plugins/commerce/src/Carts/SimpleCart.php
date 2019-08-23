<?php

namespace Commerce\Carts;

class SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $items = [];

    protected $currency;

    protected $defaults = [
        'id'      => 0,
        'name'    => 'Item',
        'count'   => 1,
        'price'   => 0,
        'options' => [],
        'meta'    => [],
    ];

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

    public function prepareItem(array $item)
    {
        $item = array_filter(array_merge($this->defaults, $item), function($key) {
            return isset($this->defaults[$key]);
        }, ARRAY_FILTER_USE_KEY);

        return $item;
    }

    protected function validateItem(array $item)
    {
        return true;
    }

    protected function beforeItemAdding(array &$item)
    {
        return true;
    }

    protected function makeHash(array $item)
    {
        $price = (float) ci()->currency->convertToDefault($item['price']);
        return md5(serialize([$item['id'], $item['name'], $price, $item['options'], $item['meta']]));
    }

    public function add(array $item)
    {
        $new = $this->prepareItem($item);

        if ($this->validateItem($new) && $this->beforeItemAdding($new)) {
            $new['hash'] = $this->makeHash($new);

            foreach ($this->items as $row => $item) {
                if ($item['hash'] == $new['hash']) {
                    $this->update($row, ['count' => $item['count'] + (!empty($new['count']) ? $new['count'] : 1)]);
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

    public function removeById($id)
    {
        $result = false;

        if (!empty($this->items)) {
            foreach ($this->items as $row => $item) {
                if (!empty($item['id']) && $item['id'] == $id) {
                    unset($this->items[$row]);
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function clean()
    {
        $this->items = [];
    }

    public function setCurrency($code)
    {
        if (!is_null($this->currency) && !empty($this->items)) {
            $currency = ci()->currency;

            foreach ($this->items as &$item) {
                $item['price'] = $currency->convert($item['price'], $this->currency, $code);
            }

            unset($item);
        }

        $this->currency = $code;
    }
}
