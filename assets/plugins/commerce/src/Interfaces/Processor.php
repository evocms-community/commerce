<?php

namespace Commerce\Interfaces;

interface Processor
{
    public function create(array $items, array $fields);

    public function get();

    public function processPayment();
}
