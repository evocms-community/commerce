<?php

namespace Commerce\Carts;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\CartStore;

class StoreCart extends SimpleCart implements Cart
{
    protected $store;
    protected $instance;

    public function __construct(CartStore $store, $instance = 'cart')
    {
        $this->instance = $instance;
        $this->setStore($store);
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

    public function setCurrency($code)
    {
        parent::setCurrency($code);
        $this->store->save($this->items);
    }

    public function setStore(CartStore $store)
    {
        $this->store = $store;
        $this->items = $store->load($this->instance);
    }
}
