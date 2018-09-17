<?php

namespace Commerce\Interfaces;

interface CartStore
{
    public function load($instance = 'cart');

    public function save(array $items);
}
