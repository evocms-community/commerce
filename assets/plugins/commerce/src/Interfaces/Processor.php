<?php

namespace Commerce\Interfaces;

interface Processor
{
    public function createOrder(array $items, array $fields);

    public function getOrder();

    public function postProcessForm($FL);

    public function processPayment($payment_id, $amount);
}
