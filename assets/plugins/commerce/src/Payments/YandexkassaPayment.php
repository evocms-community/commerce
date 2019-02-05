<?php

namespace Commerce\Payments;

class YandexkassaPayment extends Payment implements \Commerce\Interfaces\Payment
{
    public function init()
    {
        return [
            'code'  => 'yandexkassa',
            'title' => $this->lang['payments.yandexkassa_title'],
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

    public function getPaymentLink()
    {
        $debug = !empty($this->getSetting('debug'));

        $processor = $this->modx->commerce->loadProcessor();
        $order     = $processor->getOrder();
        $fields    = $order['fields'];
        $currency  = ci()->currency->getCurrency($order['currency']);
        $payment   = $this->createPayment($order['id'], ci()->currency->convertToDefault($order['amount'], $currency['code']));

        $data = [
            'amount' => [
                'value'    => number_format($order['amount'], 2, '.', ''),
                'currency' => 'RUB',
            ],
            'description' => ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
                'order_id'  => $order['id'],
                'site_name' => $this->modx->getConfig('site_name'),
            ]),
            'confirmation' => [
                'type'       => 'redirect',
                'return_url' => $this->modx->getConfig('site_url') . 'commerce/yandexkassa/payment-success',
            ],
            'metadata'     => [
                'order_id'     => $order['id'],
                'payment_id'   => $payment['id'],
                'payment_hash' => $payment['hash'],
            ],
        ];

        if (!empty($fields['phone']) || !empty($fields['email'])) {
            $receipt = ['items' => []];

            foreach ($processor->getCart()->getItems() as $item) {
                $receipt['items'][] = [
                    'description' => mb_substr($item['title'], 0, 64),
                    'vat_code'    => $this->getSetting('vat_code'),
                    'quantity'    => $item['count'],
                    'amount'      => [
                        'value'    => number_format($item['price'] * $item['count'], 2, '.', ''),
                        'currency' => 'RUB',
                    ],
                ];
            }

            if (!empty($fields['email']) && filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
                $receipt['email'] = $fields['email'];
            } elseif (!empty($fields['phone'])) {
                $receipt['phone'] = substr(preg_replace('/[^\d]+/', '', $fields['phone']), 0, 15);
            }

            $data['receipt'] = $receipt;
        }

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Request data: ' . print_r($data, true), 'Commerce YandexKassa Payment Debug: payment start');
        }

        try {
            if (($result = $this->request('payments', $data)) === false) {
                exit();
            }
        } catch (\Exception $e) {
            $this->modx->logEvent(0, 3, 'Link is not received: ' . $e->getMessage(), 'Commerce YandexKassa Payment');
            exit();
        }

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Response data: ' . print_r($result, true), 'Commerce YandexKassa Payment Debug: payment start');
        }

        return $result['confirmation']['confirmation_url'];
    }

    public function handleCallback()
    {
        $debug = !empty($this->getSetting('debug'));
        $source = file_get_contents('php://input');

        if ($debug) {
            $this->modx->logEvent(0, 1, 'Callback data: ' . print_r($source, true), 'Commerce YandexKassa Payment Debug: callback start');
        }

        if (empty($source)) {
            $this->modx->logEvent(0, 3, 'Empty data', 'Commerce YandexKassa Payment');
            return false;
        }

        $data = json_decode($source, true);

        if (empty($data) || empty($data['object'])) {
            $this->modx->logEvent(0, 3, 'Invalid json', 'Commerce YandexKassa Payment');
            return false;
        }

        $payment = $data['object'];

        if ($payment['status'] == 'waiting_for_capture' && $payment['paid'] === true) {
            $processor = $this->modx->commerce->loadProcessor();

            try {
                $processor->processPayment($payment['metadata']['payment_id'], floatval($payment['amount']['value']));
            } catch (\Exception $e) {
                $this->modx->logEvent(0, 3, 'JSON processing failed: ' . $e->getMessage(), 'Commerce YandexKassa Payment');
                return false;
            }

            $this->request('payments/' . $payment['id'] . '/capture', [
                'amount' => $payment['amount'],
            ]);
        }

        return true;
    }

    public function getRequestPaymentHash()
    {
        if (!empty($_SERVER['HTTP_REFERER']) && is_scalar($_SERVER['HTTP_REFERER'])) {
            $url = parse_url($_SERVER['HTTP_REFERER']);

            if (isset($url['query']) && isset($url['host']) && $url['host'] == 'money.yandex.ru') {
                parse_str($url['query'], $query);

                if (isset($query['orderId']) && is_scalar($query['orderId'])) {
                    $response = $this->request('payments/' . $query['orderId']);

                    if (!empty($response) && !empty($response['metadata']['payment_hash'])) {
                        return $response['metadata']['payment_hash'];
                    }
                }
            }
        }

        return null;
    }

    protected function request($method, $data = [])
    {
        $url     = 'https://payment.yandex.net/api/v3/';
        $shop_id = $this->getSetting('shop_id');
        $secret  = $this->getSetting('secret');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url . $method,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => "$shop_id:$secret",    
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE        => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Idempotence-Key: ' . uniqid(),
                'Content-Type: application/json',
                'Cache-Control: no-cache',
                'charset="utf-8"',
            ],
        ]);

        if (!empty($data)) {
            curl_setopt_array($curl, [
                CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
                CURLOPT_POST       => true,
            ]);
        }

        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($result, true);

        if ($code != 200) {
            if (isset($result['type']) && $result['type'] == 'error') {
                $msg = 'Server return error:<br>' . print_r($result, true);
            } else {
                $msg = 'Server is not responding';
            }
            
            $this->modx->logEvent(0, 3, $msg, 'Commerce YandexKassa Payment');
            return false;
        }

        return $result;
    }
}
