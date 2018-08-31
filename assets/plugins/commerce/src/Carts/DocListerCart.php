<?php

namespace Commerce\Carts;

class DocListerCart extends SessionCart implements \Commerce\Interfaces\Cart
{
    use DocListerTrait;

    protected function getSubtotals(array &$rows, &$total)
    {
        $this->modx->invokeEvent('OnCollectSubtotals', [
            'rows'  => &$rows,
            'total' => &$total,
        ]);
    }
}
