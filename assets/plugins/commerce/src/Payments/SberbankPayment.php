<?php

/**
 * DRAFT, NOT FOR USE
 */

namespace Commerce\Payments;

class SberbankPayment extends Payment implements \Commerce\Interfaces\Payment
{
    public function init()
    {
        return [
            'code'  => 'sberbank',
            'title' => $this->lang['payments.sberbank_title'],
        ];
    }

    public function getMarkup()
    {
        if (empty($this->getSetting('token'))) {
            return '<span class="error" style="color: red;">' . $this->lang['payments.error_empty_token'] . '</span>';
        }
    }

    public function getPaymentLink()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $order = $processor->getOrder();
        $order_id = $order['id'];
        $amount = $order['amount'] * 100;
        $fields = $order['fields'];

        $customer = [
            'email' => $fields['email'],
        ];

        if (!empty($fields['phone'])) {
            $phone = preg_replace('/[^0-9]+/', '', $fields['phone']);
            $phone = preg_replace('/^8/', '7', $phone);

            if (preg_match('/^7\d{10}$/', $phone)) {
                $customer['phone'] = $phone;
            }
        }

        $items = [];
        $position = 1;

        foreach ($processor->getCart()->getItems() as $item) {
            $items[] = [
                'positionId'  => $position++,
                'name'        => $item['title'],
                'quantity'    => [
                    'value'   => $item['count'],
                    'measure' => isset($meta['measurements']) ? $meta['measurements'] : $this->lang['measures.units'],
                ],
                'itemAmount'  => $item['price'] * $item['count'] * 100,
                'itemCode'    => $item['id'],
            ];
        }

        $data = [
            'orderNumber' => $order_id . '-' . time(),
            'amount'      => $amount,
            'returnUrl'   => $this->modx->getConfig('site_url') . 'commerce/sberbank/payment-process/',
            'description' => ci()->tpl->parseChunk($this->lang['payments.payment_description'], [
                'order_id'  => $order_id,
                'site_name' => $this->modx->getConfig('site_name'),
            ]),
            'orderBundle' => json_encode([
                'orderCreationDate' => date('c'),
                'customerDetails' => $customer,
                'cartItems' => [
                    'items' => $items,
                ],
            ]),
        ];

        try {
            $result = $this->request('payment/rest/register.do', $data);
        } catch (\Exception $e) {
            $this->modx->logEvent(0, 3, 'Link is not received: ' . $e->getMessage(), 'Commerce Sberbank Payment');
            exit();
        }

        return $result['formUrl'];
    }

    public function handleSuccess()
    {
        if (isset($_REQUEST['orderId']) && is_string($_REQUEST['orderId']) && preg_match('/^[a-z0-9-]{36}$/', $_REQUEST['orderId'])) {
            $order_id = $_REQUEST['orderId'];

            try {
                $status = $this->request('payment/rest/getOrderStatusExtended.do', [
                    'orderId' => $order_id,
                ]);
            } catch (\Exception $e) {
                return false;
            }

            return $status['errorCode'] == 0;
        }

        return false;
    }

    protected function getUrl($method)
    {
        $url = 'https://3dsec.sberbank.ru/';
        return $url . $method;
    }

    protected function request($method, $data)
    {
        $data['token'] = $this->getSetting('token');

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->getUrl($method),
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE        => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Content-type: application/x-www-form-urlencoded',
                'Cache-Control: no-cache',
                'charset="utf-8"',
            ],
        ]);

        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($code != 200) {
            $this->modx->logEvent(0, 3, 'Server is not responding', 'Commerce Sberbank Payment');
            return false;
        }

        $result = json_decode($result, true);

        if (isset($result['errorMessage'])) {
            $this->modx->logEvent(0, 3, 'Server return error: ' . $result['errorMessage'], 'Commerce Sberbank Payment');
            return false;
        }

        return $result;
    }
}
