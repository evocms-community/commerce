<?php

namespace Commerce\Carts;

class DocListerItemsList extends SessionCart implements \Commerce\Interfaces\Cart
{
    use DocListerTrait;

    protected function getSubtotals(array &$rows, &$total)
    {
        $rows = [];
    }

    public function getTotal()
    {
        return 0;
    }
}
