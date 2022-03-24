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

    public function has($product_id)
    {
        foreach ($this->items as $item) {
            if ($item['id'] == $product_id) {
                return true;
            }
        }

        return false;
    }

    public function getTotal()
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item['price'] * $item['count'];
        }

        return $total;
    }

    public function getItemsCount()
    {
        $count = 0;

        foreach ($this->items as $item) {
            $count += $item['count'];
        }

        return $count;
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
        $item = array_merge($this->defaults, $item);
        $item = array_filter($item, function ($key) use ($item) {
            $result = isset($this->defaults[$key]);
            if ($key === 'options' || $key === 'meta') {
                $result = $result && is_array($item[$key]);
            }

            return $result;
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

    protected function beforeItemUpdating(array &$item, &$row, $isAdded = false)
    {
        return true;
    }

    public function makeHash(array $item)
    {
        $price = (float) ci()->currency->convertToDefault($item['price']);
        return md5(serialize([$item['id'], $item['name'], $price, $item['options'], $item['meta']]));
    }

    public function add(array $item, $isMultiple = false)
    {
        $new = $this->prepareItem($item);

        if ($this->validateItem($new) && $this->beforeItemAdding($new)) {
            $new['hash'] = $this->makeHash($new);

            foreach ($this->items as $row => $item) {
                if ($item['hash'] == $new['hash']) {
                    $this->update($row, ['count' => $item['count'] + (!empty($new['count']) ? $new['count'] : 1)],
                        true);
                    return $row;
                }
            }

            $row = ci()->commerce->generateRandomString(16);
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
            $result[] = $this->add($item, true);
        }

        return $result;
    }

    public function update($row, array $attributes = [], $isAdded = false)
    {
        if (isset($this->items[$row])) {
            $new = array_merge($this->items[$row], array_filter($attributes, function ($key) use ($attributes) {
                $result = isset($this->defaults[$key]);
                if ($key === 'options' || $key === 'meta') {
                    $result = $result && is_array($attributes[$key]);
                }

                return $result;
            }, ARRAY_FILTER_USE_KEY));

            if ($this->validateItem($new) && $this->beforeItemUpdating($new, $row, $isAdded)) {
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

        foreach ($this->items as $row => $item) {
            if (!empty($item['id']) && $item['id'] == $id) {
                unset($this->items[$row]);
                $result = true;
            }
        }

        return $result;
    }

    public function clean()
    {
        $this->items = [];
        return true;
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
