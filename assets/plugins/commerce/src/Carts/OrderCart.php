<?php

namespace Commerce\Carts;

class OrderCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    protected $subtotals = [];
    protected $total = null;

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

        if (is_null($this->total)) {
            $this->total = $total;

            foreach ($rows as $row) {
                $this->total += $row['price'];
            }
        }

        $total = $this->total;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function getTotal()
    {
        if (is_null($this->total)) {
            $this->total = parent::getTotal();

            foreach ($this->subtotals as $subtotal) {
                $this->total += $subtotal['price'];
            }
        }

        return $this->total;
    }
}
