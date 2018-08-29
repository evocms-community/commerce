<?php

namespace Commerce\Interfaces;

interface Cart
{
    public function getItems();

    public function get($row);

    public function setItems(array $items);

    public function add($id, $name, $count = 1, $price = 0, $options = [], $meta = null);

    public function addMultiple(array $items = []);

    public function update($row, array $attributes = []);

    public function remove($row);

    public function clean();
}
