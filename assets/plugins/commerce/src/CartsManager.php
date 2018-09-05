<?php

namespace Commerce;

class CartsManager
{
    protected $carts = [];

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
    private function __wakeup() {}

    public function addCart($name, Interfaces\Cart $cart)
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
}
