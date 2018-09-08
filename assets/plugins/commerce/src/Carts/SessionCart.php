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

    public function add(array $item)
    {
        $this->modx->invokeEvent('OnBeforeCartItemAdding', ['item' => &$item]);
        extract($item);

        $row = parent::add($item);
        $this->storeItems();

        return $row;
    }

    public function update($row, array $attributes = [])
    {
        if ($result = parent::update($row, $attributes)) {
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
