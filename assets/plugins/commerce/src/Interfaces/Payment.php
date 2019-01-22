<?php

namespace Commerce\Interfaces;

interface Payment
{
    public function init();

    public function getMarkup();

    public function getPaymentLink();

    public function getPaymentMarkup();

    public function handleCallback();

    public function handleSuccess();

    public function handleError();

    public function createPayment($order_id, $amount);

    public function getRequestPaymentHash();
}
