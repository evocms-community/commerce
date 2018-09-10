<?php

namespace Commerce\Carts;

class DocListerOrderCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    use DocListerTrait;

    protected $modx;

    protected $subtotals = [];

    public function __construct($modx)
    {
        $this->modx = $modx;
    }

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
