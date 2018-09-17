<?php

namespace Commerce\Carts;

class SessionCartStore implements \Commerce\Interfaces\CartStore
{
    protected $instance;

    public function load($instance = 'cart')
    {
        $this->instance = $instance;

        if (isset($_SESSION[$this->instance])) {
            return $_SESSION[$this->instance];
        }

        return [];
    }

    public function save(array $items)
    {
        $_SESSION[$this->instance] = $items;
    }
}