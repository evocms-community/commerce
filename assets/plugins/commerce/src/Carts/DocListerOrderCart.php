<?php

namespace Commerce\Carts;

class DocListerOrderCart extends SimpleCart implements \Commerce\Interfaces\Cart
{
    use DocListerTrait;

    private $modx;

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
}
