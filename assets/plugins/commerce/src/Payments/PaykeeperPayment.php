<?php

/**
 * DRAFT, NOT TESTED, NOT FOR USE
 */

namespace Commerce\Payments;

class PaykeeperPayment extends Payment implements \Commerce\Interfaces\Payment
{
    public function init()
    {
        return [
            'code'  => 'paykeeper',
            'title' => $this->lang['payments.paykeeper_title'],
        ];
    }

    public function getMarkup()
    {
        $out = [];

        if (empty($this->getSetting('pay_url'))) {
            $out[] = $this->lang['payments.error_empty_pay_url'];
        }

        if (empty($this->getSetting('secret'))) {
            $out[] = $this->lang['payments.error_empty_secret'];
        }

        if (!empty($out)) {
            $out = '<span class="error" style="color: red;">' . implode('<br>', $out) . '</span>';
        }

        return $out;
    }

    public function getPaymentMarkup()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $order     = $processor->getOrder();
        $fields    = $order['fields'];

        if (empty($fields['phone'])) {
            return '<span class="error" style="color: red;">' . $this->lang['payments.error_phone_required'] . '</span>';
        }

        $data = [
            'sum'      => number_format($order['amount'], 2, '.', ''),
            'orderid'  => $order['id'],
            'clientid' => $order['id'],
            'phone'    => substr(preg_replace('/[^\d]+/', '', $fields['phone']), 0, 15),
        ];
        
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
            ],
        ]);

        $template = $this->getSetting('template', '@CODE:[+form+]');
        $form = file_get_contents($this->getSetting('pay_url'), false, $context);
        
        return ci()->tpl->parseChunk($template, [
            'form' => $form,
        ]);
    }

    public function handleCallback()
    {
        $debug = !empty($this->getSetting('debug'));

        if ($_SERVER['REQUEST_METHOD'] != 'POST' || empty($_POST)) {
            $this->modx->logEvent(0, 3, 'Empty POST or non-post request', 'Commerce Paykeeper Payment');
            return false;
        }

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Callback data: <pre>' . htmlentities(print_r($_POST, true)) . '</pre>', 'Commerce Paykeeper Payment Debug: callback start');
        }

        $secret = $this->getSetting('secret');

        $rules = [
            'id' => [
                'required' => 'id is required',
                'numeric'  => 'id should be numeric',
            ],
            'sum' => [
                'required' => 'sum is required',
                'decimal'  => 'sum should be numeric',
            ],
            'orderid' => [
                'required' => 'orderid is required',
                'numeric'  => 'orderid should be numeric',
            ],
            'clientid' => [
                'required' => 'clientid is required',
                'numeric'  => 'clientid should be numeric',
            ],
            'key' => [
                'required' => 'key is required',
            ],
        ];

        $data = array_filter($_POST, function($key) use ($rules) {
            return isset($rules[$key]);
        }, ARRAY_FILTER_USE_KEY);

        $formlister = new \FormLister\Form($this->modx);
        $validator  = new \FormLister\Validator;
        $result     = $formlister->validate($validator, $rules, $data);

        if ($result !== true && !empty($result)) {
            $this->modx->logEvent(0, 3, 'POST data invalid.<br><pre>$_POST: ' . htmlentities(print_r($_POST, true)) . '<br>$result: ' . htmlentities(print_r($result, true)) . '</pre>', 'Commerce Paykeeper Payment');
            return false;
        }

        $sign = md5(implode('.', [$data['id'], $data['sum'], $data['clientid'], $data['orderid'], $secret]));

        if ($sign != $data['key']) {
            $this->modx->logEvent(0, 3, 'Signature check failed.<br><pre>$_POST: ' . htmlentities(print_r($_POST, true)) . '<br>Calculated signature: ' . $sign . '</pre>', 'Commerce Paykeeper Payment');
            return false;
        }

        $processor = $this->modx->commerce->loadProcessor();

        try {
            if (!$processor->processPayment($data['orderid'], floatval($data['sum']))) {
                throw new Exception('Failed by processor (order not found or amount mismatch)');
            }
        } catch (\Exception $e) {
            $this->modx->logEvent(0, 3, 'Payment processing failed: ' . $e->getMessage(), 'Commerce Paykeeper Payment');
            return false;
        }

        echo 'OK ' . md5($data['id'] . $secret); 
        return true;
    }
}
