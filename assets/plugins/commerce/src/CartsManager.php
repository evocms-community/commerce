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

    public function storeParams(array $params)
    {
        $hash = md5(json_encode($params));
        $_SESSION['commerce.cart-' . $hash] = serialize($params);

        return $hash;
    }

    public function restoreParams($hash)
    {
        if (!empty($_SESSION['commerce.cart-' . $hash])) {
            return unserialize($_SESSION['commerce.cart-' . $hash]);
        }

        return false;
    }

    public function getCartByHash($hash)
    {
        if ($params = $this->restoreParams($hash)) {
            $instance = !empty($params['instance']) ? $params['instance'] : 'products';
            return $this->getCart($instance);
        }

        return null;
    }
}
