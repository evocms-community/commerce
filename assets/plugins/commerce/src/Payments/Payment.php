<?php

namespace Commerce\Payments;

class Payment implements \Commerce\Interfaces\Payment
{
    use \Commerce\SettingsTrait;

    protected $modx;
    protected $lang;

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
}
