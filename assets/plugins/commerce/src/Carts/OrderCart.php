<?php

namespace Commerce\Carts;

class OrderCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $subtotals = [];

    public function __construct()
    {
        $this->defaults['order_row_id'] = 0;
    }

    public function setSubtotals($subtotals)
    {
        $this->subtotals = $subtotals;
    }

    public function getSubtotals(array &$rows, &$total)
    {
        $rows = $this->subtotals;

        foreach ($rows as $row) {
            $total += $row['price'];
        }
    }

    public function getTotal()
    {
        $total = parent::getTotal();

        foreach ($this->subtotals as $subtotal) {
            $total += $subtotal['price'];
        }

        return $total;
    }
}
