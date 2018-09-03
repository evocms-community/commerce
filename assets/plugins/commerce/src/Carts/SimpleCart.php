<?php

namespace Commerce\Carts;

class SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $items = [];
    protected $instance;

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
        $this->items = $items;
    }

    public function add($id, $name, $count = 1, $price = 0, $options = [], $meta = null)
    {
        $new = compact('id', 'name', 'count', 'price', 'options', 'meta');
        $new['hash'] = md5(serialize([$new['id'], $new['name'], $new['price'], $new['options'], $new['meta']]));

        foreach ($this->items as $row => $item) {
            if ($item['hash'] == $new['hash']) {
                $this->items[$row]['count'] += $count;
                return $row;
            }
        }

        $row = uniqid();
        $new['row'] = $row;
        $this->items[$row] = $new;

        return $row;
    }

    public function addMultiple(array $items = [])
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = call_user_method_array('add', $this, $item);
        }

        return $result;
    }

    public function update($row, array $attributes = [])
    {
        if (isset($this->items[$row])) {
            $this->items[$row] = array_merge($this->items[$row], $attributes);
            return true;
        }

        return false;
    }

    public function updateById($id, array $attributes = [])
    {
        foreach ($this->items as $row => $item) {
            if ($item['id'] == $id) {
                return $this->update($row, $attributes);
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
