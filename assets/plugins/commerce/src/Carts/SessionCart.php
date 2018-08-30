<?php

namespace Commerce\Carts;

class SessionCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $modx;

    public function __construct($modx, $instance = 'cart')
    {
        $this->modx = $modx;
        $this->instance = 'commerce.' . $instance;
        $this->initializeStore();
    }

    public function setItems(array $items)
    {
        parent::setItems($items);
        $this->storeItems();
    }

    public function add($id, $name, $count = 1, $price = 0, $options = [], $meta = null)
    {
        $item = compact('id', 'name', 'count', 'price', 'options', 'meta');
        $this->modx->invokeEvent('OnBeforeAddingCartItem', ['item' => &$item]);
        extract($item);

        $row = parent::add($id, $name, $count, $price, $options, $meta);
        $this->storeItems();

        return $row;
    }

    public function update($row, array $attributes = [])
    {
        if ($result = parent::update($row, $aatributes)) {
            $this->storeItems();
        }

        return $result;
    }

    public function remove($row)
    {
        if ($result = parent::remove($row)) {
            $this->storeItems();
        }

        return $result;
    }

    public function clean()
    {
        parent::clean();
        $this->storeItems();
    }

    protected function initializeStore()
    {
        if (isset($_SESSION[$this->instance])) {
            $this->items = $_SESSION[$this->instance];
        }
    }

    protected function storeItems()
    {
        $_SESSION[$this->instance] = $this->items;
    }
}
