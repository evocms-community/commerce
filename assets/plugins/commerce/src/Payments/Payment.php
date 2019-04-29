<?php

namespace Commerce\Payments;

class Payment implements \Commerce\Interfaces\Payment
{
    use \Commerce\SettingsTrait;

    protected $modx;
    protected $lang;
    protected $payment_id = null;

    public function __construct($modx, array $params = [])
    {
        $this->modx = $modx;
        $this->lang = $modx->commerce->getUserLanguage('payments');
        $this->setSettings($params);
    }

    public function init()
    {
        return false;
    }

    public function getMarkup()
    {
        return '';
    }

    public function getPaymentLink()
    {
        return false;
    }

    public function getPaymentMarkup()
    {
        return '';
    }

    public function handleCallback()
    {
        return false;
    }

    public function handleSuccess()
    {
        return true;
    }

    public function handleError()
    {
        return true;
    }

    public function createPayment($order_id, $amount)
    {
        $db = ci()->db;

        $payment = [
            'order_id'   => $order_id,
            'amount'     => $amount,
            'hash'       => substr(bin2hex(random_bytes(8)), 0, 16),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $db->insert($payment, ci()->modx->getFullTablename('commerce_order_payments'));
        $payment['id'] = $db->getInsertId();

        return $payment;
    }

    public function createPaymentRedirect()
    {
        $link = $this->getPaymentLink();

        if (!empty($link)) {
            return ['link' => $link];
        }

        return ['markup' => $this->getPaymentMarkup()];
    }

    public function getRequestPaymentHash()
    {
        return null;
    }
}
