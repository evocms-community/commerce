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
        $this->lang = $modx->commerce->getUserLanguage('common');
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
        $hash = ci()->commerce->generateRandomString(16);

        $paid = ci()->commerce->loadProcessor()->getOrderPaymentsAmount($order_id);
        $diff = $amount - $paid;

        $this->modx->invokeEvent('OnBeforePaymentCreate', [
            'order_id'     => $order_id,
            'order_amount' => $amount,
            'amount'       => &$diff,
            'hash'         => &$hash,
        ]);

        $payment = [
            'order_id'   => $order_id,
            'amount'     => $diff,
            'hash'       => $hash,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $db->insert($payment, $this->modx->getFullTablename('commerce_order_payments'));
        $payment['id'] = $db->getInsertId();

        return $payment;
    }

    public function createPaymentRedirect()
    {
        $link = $this->getPaymentLink();

        if (!empty($link)) {
            return ['link' => $link];
        }

        $markup = $this->getPaymentMarkup();

        if (!empty($markup)) {
            return ['markup' => $markup];
        }

        return false;
    }

    public function getRequestPaymentHash()
    {
        return null;
    }

    protected function prepareItems($cart)
    {
        $items = [];

        $discount = 0;
        $items_price = 0;

        foreach ($cart->getItems() as $item) {
            $items_price += $item['price'] * $item['count'];
            $items[] = [
                'id'      => $item['id'],
                'name'    => mb_substr($item['name'], 0, 255),
                'count'   => number_format($item['count'], 3, '.', ''),
                'price'   => number_format($item['price'], 2, '.', ''),
                'total'   => number_format($item['price'] * $item['count'], 2, '.', ''),
                'product' => true,
            ];
        }

        $subtotals = [];
        $cart->getSubtotals($subtotals, $total);

        foreach ($subtotals as $item) {
            if ($item['price'] < 0) {
                $discount -= $item['price'];
            } else if ($item['price'] > 0) {
                $items_price += $item['price'];
                $items[] = [
                    'id'      => 0,
                    'name'    => mb_substr($item['title'], 0, 255),
                    'count'   => 1,
                    'price'   => number_format($item['price'], 2, '.', ''),
                    'total'   => number_format($item['price'], 2, '.', ''),
                    'product' => false,
                ];
            }
        }

        // Если в заказе присутствует скидка,
        // нужно пропорционально разделить ее на все товары
        if ($discount > 0) {
            $items = $this->decreaseItemsAmount($items, $items_price, $items_price - $discount);
        }

        return $items;
    }

    protected function decreaseItemsAmount($items, $from, $to)
    {
        if ($from > 0) {
            $total = 0;
            $ratio = $to / $from;
            $last  = count($items) - 1;

            foreach ($items as $i => $item) {
                if ($i < $last) {
                    $items[$i]['price'] = number_format($item['price'] * $ratio, 2, '.', '');
                    $total += $items[$i]['price'] * $items[$i]['count'];
                } else {
                    $items[$i]['price'] = number_format(($to - $total) / $items[$i]['count'], 2, '.', '');
                }
            }
        }

        return $items;
    }
}
