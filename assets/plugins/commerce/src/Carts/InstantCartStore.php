<?php

namespace Commerce\Carts;

use Commerce\Interfaces\CartStore;

class InstantCartStore implements CartStore
{
    public function load($instance = 'cart')
    {
        return [];
    }

    public function save(array $items)
    {
    }
}