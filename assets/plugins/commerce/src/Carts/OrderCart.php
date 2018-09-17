<?php

namespace Commerce\Carts;

class OrderCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $subtotals = [];

    public function setSubtotals($subtotals)
    {
        $this->subtotals = $subtotals;
    }

    protected function getSubtotals(array &$rows, &$total)
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
