<?php

namespace Commerce\Carts;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\CartStore;

class StoreCart extends SimpleCart implements Cart
{
    protected $store;
    protected $instance;

    public function __construct(CartStore $store, $instance = 'products')
    {
        $this->instance = $instance;
        $this->setStore($store);
    }

    public function setItems(array $items)
    {
        parent::setItems($items);
        $this->store->save($items);
    }

    public function add(array $item, $isMultiple = false)
    {
        $row = parent::add($item);
        $this->store->save($this->items);

        return $row;
    }

    public function update($row, array $attributes = [], $isAdded = false)
    {
        if ($result = parent::update($row, $attributes, $isAdded)) {
            $this->store->save($this->items);
        }

        return $result;
    }

    public function remove($row)
    {
        if ($result = parent::remove($row)) {
            $this->store->save($this->items);
        }

        return $result;
    }

    public function removeById($row)
    {
        if ($result = parent::removeById($row)) {
            $this->store->save($this->items);
        }

        return $result;
    }

    public function clean()
    {
        $result = parent::clean();
        $this->store->save($this->items);
        return $result;
    }

    public function setCurrency($code)
    {
        parent::setCurrency($code);
        $this->store->save($this->items);
    }

    public function setStore(CartStore $store)
    {
        $this->store = $store;
        $items = $store->load($this->instance);

        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as $row => $item) {
            if (is_numeric($item)) {
                $item = $this->prepareItem([
                    'id' => $item,
                ]);
                $item['hash'] = $this->makeHash($item);
                $item['row'] = $row;
                $items[$row] = $item;
            }
        }

        if (method_exists($this, 'validateItem')) {
            $items = array_filter($items, function($item) {
                return $this->validateItem($item);
            });
        }

        $this->items = $items;
    }
}
