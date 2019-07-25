<?php

namespace Commerce\Payments;

class RobokassaPayment extends Payment implements \Commerce\Interfaces\Payment
{
    public function init()
    {
        return [
            'code' => 'robokassa',
            'title' => 'Robokassa',
        ];
    }

    public function getMarkup()
    {
        $out = [];

        if (empty($this->getSetting('merchant_login'))) {
            $out[] = $this->lang['payments.error_empty_shop_id'];
        }

        $prefix = !empty($this->getSetting('debug')) ? 'test' : '';

        if (empty($this->getSetting($prefix . 'pass1'))) {
            $out[] = $this->lang['payments.error_empty_password1'];
        }

        if (empty($this->getSetting($prefix . 'pass2'))) {
            $out[] = $this->lang['payments.error_empty_password2'];
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
        $order = $processor->getOrder();
        $fields = $order['fields'];
        $currency = ci()->currency->getCurrency($order['currency']);
        $payment = $this->createPayment($order['id'], ci()->currency->convertToDefault($order['amount'], $currency['code']));

        $data = [
            'MerchantLogin' => $this->getSetting('merchant_login'),
            'OutSum' => $order['amount'],
            'InvId' => $order['id'],
            'Shp_PaymentHash' => $payment['hash'],
            'Shp_PaymentId' => $payment['id'],
            'Encoding' => 'utf-8',
            'InvDesc' => ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
                'order_id' => $order['id'],
                'site_name' => $this->modx->getConfig('site_name'),
            ]),
        ];

        $cart = $processor->getCart();
        $vat_code = $this->getSetting('vat_code');
        $items = [];

        foreach ($cart->getItems() as $item) {
            $items[] = [
                'name' => mb_substr($item['name'], 0, 64),
                'quantity' => $item['count'],
                'sum' => $item['price'] * $item['count'],
                'tax' => $vat_code,
            ];
        }

        $rows = [];
        $cart->getSubtotals($rows, $amountTotal);

        foreach ($rows as $row) {
            $items[] = [
                'name' => mb_substr($row['title'], 0, 64),
                'quantity' => 1,
                'sum' => $row['price'],
                'tax' => $vat_code,
            ];
        }

        $data['receipt'] = urlencode(json_encode([
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE));

        if (!empty($order['email']) && filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            $data['Email'] = $order['email'];
        }

        if ($debug) {
            $data['IsTest'] = 1;
            $this->modx->logEvent(0, 1, 'Request data: <pre>' . htmlentities(print_r($data, true)) . '</pre>', 'Commerce Robokassa Payment Debug: payment start');
        }

        $data['SignatureValue'] = md5(implode(':', [
            $data['MerchantLogin'],
            $data['OutSum'],
            $data['InvId'],
            $data['receipt'],
            $this->getSetting($debug ? 'testpass1' : 'pass1'),
            'Shp_PaymentHash=' . $data['Shp_PaymentHash'],
            'Shp_PaymentId=' . $data['Shp_PaymentId'],
        ]));

        $view = new \Commerce\Module\Renderer($this->modx, null, [
            'path' => 'assets/plugins/commerce/templates/front/',
        ]);

        return $view->render('payment_form.tpl', [
            'url' => 'https://merchant.roboxchange.com/Index.aspx',
            'data' => $data,
        ]);
    }

    public function handleCallback()
    {
        $debug = !empty($this->getSetting('debug'));
        $data = $_POST;

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Callback data: <pre>' . htmlentities(print_r($data, true)) . '</pre>', 'Commerce Robokassa Payment Debug: callback start');
        }

        if (empty($data['OutSum']) || empty($data['InvId']) || empty($data['Shp_PaymentHash']) || empty($data['Shp_PaymentId']) || !isset($data['SignatureValue'])) {
            $this->modx->logEvent(0, 3, 'Not enough data', 'Commerce Robokassa Payment');
            return false;
        }

        $signature = strtoupper(md5(implode(':', [
            $data['OutSum'],
            $data['InvId'],
            $this->getSetting($debug ? 'testpass2' : 'pass2'),
            'Shp_PaymentHash=' . $data['Shp_PaymentHash'],
            'Shp_PaymentId=' . $data['Shp_PaymentId'],
        ])));

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Generated signature: ' . $signature, 'Commerce Robokassa Payment Debug');
        }

        if ($signature != strtoupper($data['SignatureValue'])) {
            $this->modx->logEvent(0, 3, 'Signature check failed: ' . $signature . ' != ' . $data['SignatureValue'], 'Commerce Paymaster Payment');
            return false;
        }

        $processor = $this->modx->commerce->loadProcessor();

        try {
            $processor->processPayment($data['Shp_PaymentId'], floatval($data['OutSum']));
        } catch (\Exception $e) {
            $this->modx->logEvent(0, 3, 'JSON processing failed: ' . $e->getMessage(), 'Commerce Robokassa Payment');
            return false;
        }

        return true;
    }

    public function getRequestPaymentHash()
    {
        if (isset($_REQUEST['Shp_PaymentHash']) && is_scalar($_REQUEST['Shp_PaymentHash'])) {
            return $_REQUEST['Shp_PaymentHash'];
        }

        return null;
    }
}
