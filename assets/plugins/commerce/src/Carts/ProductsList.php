<?php

namespace Commerce\Carts;

class ProductsList extends ProductsCart implements \Commerce\Interfaces\Cart
{
    protected $rules = [
        'id' => [
            'numeric' => 'Item identificator should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Item identificator should be greater than 0',
            ]
        ],
    ];

    protected function getItemPrice($id)
    {
        return $this->defaults['price'];
    }

    protected function prepareItem(array $item)
    {
        $item['count'] = 1;
        return parent::prepareItem($item);
    }

    public function getSubtotals(array &$rows, &$total)
    {
        $rows = [];
    }

    public function getTotal()
    {
        return 0;
    }
}