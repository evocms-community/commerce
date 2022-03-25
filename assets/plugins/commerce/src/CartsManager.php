<?php

namespace Commerce;

use Commerce\Interfaces\Cart;

class CartsManager
{
    protected $carts = [];
    protected $stores = [];

    static protected $self;

    public static function getManager()
    {
        if (is_null(self::$self)) {
            self::$self = new self();
        }

        return self::$self;
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    public function addCart($name, Cart $cart)
    {
        if (isset($this->carts[$name])) {
            throw new \Exception('Cart "' . $name . '" already exists!');
        }

        $this->carts[$name] = $cart;
    }

    public function getCart($name)
    {
        if (isset($this->carts[$name])) {
            return $this->carts[$name];
        }

        return null;
    }

    public function has($name)
    {
        return isset($this->carts[$name]);
    }

    public function changeCurrency($code)
    {
        foreach ($this->carts as $cart) {
            $cart->setCurrency($code);
        }
    }

    public function getInstanceByHash($hash)
    {
        if ($params = ci()->commerce->restoreParams($hash)) {
            $instance = !empty($params['instance']) ? $params['instance'] : 'products';
            return $instance;
        }

        return null;
    }

    public function registerStore($name, $store)
    {
        if (isset($this->stores[$name])) {
            throw new \Exception('Store "' . print_r($name, true) . '" already registered!');
        }

        $this->stores[$name] = $store;
    }

    public function getStore($name)
    {
        if (!isset($this->stores[$name])) {
            throw new \Exception('Store "' . print_r($name, true) . '" not registered!');
        }

        return new $this->stores[$name];
    }

    public function hasStore($name)
    {
        return isset($this->stores[$name]);
    }
}
