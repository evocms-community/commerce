<?php

namespace Commerce\Carts;

class DocListerItemsList extends SessionCart implements \Commerce\Interfaces\Cart
{
    use DocListerTrait;

    protected $rules = [
        'id' => [
            'numeric' => 'Item identificator should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Item identificator should be greater than 0',
            ]
        ],
        'name' => [
            'alpha' => 'Item name should be a string',
            'lengthBetween' => [
                'params'  => [1, 255],
                'message' => 'Item name length sould be between 1 and 255',
            ]
        ],
        'count' => [
            'decimal' => 'Count should be numeric',
            'greater' => [
                'params'  => [0],
                'message' => 'Count should be greater than 0',
            ]
        ],
    ];

    protected $defaults = [
        'id'      => 0,
        'name'    => 'Item',
        'count'   => 1,
        'options' => [],
        'meta'    => [],
    ];

    protected function makeHash(array $item)
    {
        return md5(serialize([$item['id'], $item['name'], $item['options'], $item['meta']]));
    }

    protected function getSubtotals(array &$rows, &$total)
    {
        $rows = [];
    }

    public function getTotal()
    {
        return 0;
    }
}
