<?php

namespace Commerce\Payments;

class PaymasterPayment extends Payment implements \Commerce\Interfaces\Payment
{
    public function init()
    {
        return [
            'code'  => 'paymaster',
            'title' => 'Paymaster',
        ];
    }

    public function getMarkup()
    {
        $out = [];

        if (empty($this->getSetting('shop_id'))) {
            $out[] = $this->lang['payments.error_empty_shop_id'];
        }

        if (empty($this->getSetting('secret'))) {
            $out[] = $this->lang['payments.error_empty_secret'];
        }

        $out = implode('<br>', $out);

        if (!empty($out)) {
            $out = '<span class="error" style="color: red;">' . $out . '</span>';
        }

        return $out;
    }

    public function getPaymentMarkup()
    {
        $debug = !empty($this->getSetting('debug'));

        $processor = $this->modx->commerce->loadProcessor();
        $order     = $processor->getOrder();
        $fields    = $order['fields'];
        $currency  = ci()->currency->getCurrency($order['currency']);
        $payment   = $this->createPayment($order['id'], ci()->currency->convertToDefault($order['amount'], $currency['code']));

        $data = [
            'COMMERCE_PAYMENT_ID'   => $payment['id'],
            'COMMERCE_PAYMENT_HASH' => $payment['hash'],
            'LMI_MERCHANT_ID'       => $this->getSetting('shop_id'),
            'LMI_PAYMENT_AMOUNT'    => $order['amount'],
            'LMI_CURRENCY'          => $currency['code'],
            'LMI_PAYMENT_NO'        => $order['id'],
            'LMI_PAYMENT_DESC'      => ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
                'order_id'  => $order['id'],
                'site_name' => $this->modx->getConfig('site_name'),
            ]),
        ];

        if (!empty($this->getSetting('debug'))) {
            $data['LMI_SIM_MODE'] = $this->getSetting('debug_mode');
        }

        if (!empty($order['email']) && filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            $data['LMI_PAYER_EMAIL'] = $order['email'];
        }

        if (!empty($order['phone'])) {
            $data['LMI_PAYER_PHONE_NUMBER'] = preg_replace('/[^\d]+/', '', $order['phone']);
        }

        if (!empty($order['phone']) || !empty($order['email'])) {
            $position = 1;
            $vat_code = $this->getSetting('vat_code');

            foreach ($processor->getCart()->getItems() as $item) {
                $key = 'LMI_SHOPPINGCART.ITEMS[' . $position++ . ']';
                $data["$key.NAME"]  = mb_substr($item['name'], 0, 64);
                $data["$key.QTY"]   = $item['count'];
                $data["$key.PRICE"] = $item['price'] * $item['count'];
                $data["$key.TAX"]   = $vat_code;
            }
        }

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Request data: <pre>' . htmlentities(print_r($data, true)) . '</pre>', 'Commerce Paymaster Payment Debug: payment start');
        }

        $view = new \Commerce\Module\Renderer($this->modx, null, [
            'path' => 'assets/plugins/commerce/templates/front/',
        ]);

        return $view->render('payment_form.tpl', [
            'url'  => 'https://paymaster.ru/Payment/Init',
            'data' => $data,
        ]);
    }

    public function handleCallback()
    {
        $debug = !empty($this->getSetting('debug'));
        $data  = $_POST;

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Callback data: <pre>' . htmlentities(print_r($data, true)) . '</pre>', 'Commerce Paymaster Payment Debug: callback start');
        }

        if (
            empty($data['LMI_MERCHANT_ID']) ||
            empty($data['LMI_PAYMENT_NO']) ||
            empty($data['LMI_SYS_PAYMENT_ID']) ||
            empty($data['LMI_SYS_PAYMENT_DATE']) ||
            empty($data['LMI_PAYMENT_AMOUNT']) ||
            empty($data['LMI_CURRENCY']) ||
            empty($data['LMI_PAID_AMOUNT']) ||
            empty($data['LMI_PAID_CURRENCY']) ||
            empty($data['LMI_PAYMENT_SYSTEM']) ||
            empty($data['COMMERCE_PAYMENT_ID']) ||
            empty($data['COMMERCE_PAYMENT_HASH']) ||
            !isset($data['LMI_HASH'])
        ) {
            $this->modx->logEvent(0, 3, 'Not enough data', 'Commerce Paymaster Payment');
            return false;
        }

        $signature = base64_encode(hash('sha256', implode(';', [
            $data['LMI_MERCHANT_ID'],
            $data['LMI_PAYMENT_NO'],
            $data['LMI_SYS_PAYMENT_ID'],
            $data['LMI_SYS_PAYMENT_DATE'],
            $data['LMI_PAYMENT_AMOUNT'],
            $data['LMI_CURRENCY'],
            $data['LMI_PAID_AMOUNT'],
            $data['LMI_PAID_CURRENCY'],
            $data['LMI_PAYMENT_SYSTEM'],
            isset($data['LMI_SIM_MODE']) ? $data['LMI_SIM_MODE'] : '',
            $this->getSetting('secret'),
        ]), true));

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Generated signature: ' . $signature, 'Commerce Paymaster Payment Debug');
        }

        if ($signature != $data['LMI_HASH']) {
            $this->modx->logEvent(0, 3, 'Signature check failed!', 'Commerce Paymaster Payment');
            return false;
        }

        $processor = $this->modx->commerce->loadProcessor();

        try {
            $processor->processPayment($data['COMMERCE_PAYMENT_ID'], floatval($data['LMI_PAID_AMOUNT']));
        } catch (\Exception $e) {
            $this->modx->logEvent(0, 3, 'Signature check failed: ' . $signature . ' != ' . $data['LMI_HASH'], 'Commerce Paymaster Payment');
            return false;
        }

        return true;
    }

    public function getRequestPaymentHash()
    {
        if (isset($_REQUEST['COMMERCE_PAYMENT_HASH']) && is_scalar($_REQUEST['COMMERCE_PAYMENT_HASH'])) {
            return $_REQUEST['COMMERCE_PAYMENT_HASH'];
        }

        return null;
    }
}
