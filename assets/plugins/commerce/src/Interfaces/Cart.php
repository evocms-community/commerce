<?php

namespace Commerce\Interfaces;

interface Cart
{
    public function getItems();

    public function getItemsCount();

    public function getTotal();

    public function get($row);

    public function setItems(array $items);

    public function add(array $item, $isMultiple = false);

    public function addMultiple(array $items = []);

    public function update($row, array $attributes = [], $isAdded = false);

    public function remove($row);

    public function clean();

    public function setCurrency($code);
}
