<?php

namespace Commerce\Carts;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\CartStore;

class StoreCart extends SimpleCart implements Cart
{
    protected $store;

    public function __construct(CartStore $store, $instance = 'cart')
    {
        $this->store = $store;
        $this->items = $store->load($instance);
    }

    public function setItems(array $items)
    {
        parent::setItems($items);
        $this->store->save($items);
    }

    public function add(array $item)
    {
        $row = parent::add($item);
        $this->store->save($this->items);

        return $row;
    }

    public function update($row, array $attributes = [])
    {
        if ($result = parent::update($row, $attributes)) {
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

    public function clean()
    {
        parent::clean();
        $this->store->save($this->items);
    }
}
