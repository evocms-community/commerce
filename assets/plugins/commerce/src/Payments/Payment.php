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
        return ci()->commerce->loadProcessor()->createPayment($order_id, $amount);
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

    /**
     * Формирование массива товаров из корзины, распределение скидки
     *
     * @param  \Commerce\Interfaces\Cart $cart
     * @param  string $currency
     * @return array
     */
    protected function prepareItems($cart, $orderCurrency = null, $paymentCurrency = null)
    {
        $items = [];
        $total = 0;
        $discount = 0;
        $items_price = 0;
        $currency = ci()->currency;

        foreach ($cart->getItems() as $item) {
            if ($paymentCurrency) {
                $item['price'] = $currency->convert($item['price'], $orderCurrency, $paymentCurrency);
            }

            $items_price += $item['price'] * $item['count'];

            $item = [
                'id'      => $item['id'],
                'name'    => mb_substr($item['name'], 0, 255),
                'count'   => number_format($item['count'], 3, '.', ''),
                'total'   => number_format($item['price'] * $item['count'], 2, '.', ''),
                'product' => true,
            ];

            $item['price'] = number_format($item['total'] / $item['count'], 2, '.', '');
            $items[] = $item;
        }

        $subtotals = [];
        $cart->getSubtotals($subtotals, $total);

        foreach ($subtotals as $item) {
            if ($paymentCurrency) {
                $item['price'] = $currency->convert($item['price'], $orderCurrency, $paymentCurrency);
            }

            if ($item['price'] < 0) {
                $discount -= $item['price'];
            } else if ($item['price'] > 0) {
                $items_price += $item['price'];

                $item = [
                    'id'      => 0,
                    'name'    => mb_substr($item['title'], 0, 255),
                    'count'   => 1,
                    'price'   => number_format($item['price'], 2, '.', ''),
                    'total'   => number_format($item['price'], 2, '.', ''),
                    'product' => false,
                ];

                $items[] = $item;
            }
        }

        $items = $this->decreaseItemsAmount($items, $items_price, $items_price - $discount);

        return $items;
    }

    /**
     * Корректировка цен всех товаров таким образом,
     * чтобы все цены были с двумя знаками после запятой,
     * а их сумма была равна значению $to.
     * В цикле считается сумма всех товаров кроме последнего,
     * а для последнего задается цена с необходимой корректировкой
     *
     * @param  array $items Массив товаров
     * @param  float $from  Начальная стоимость всех товаров
     * @param  float $to    Конечная стоимость
     * @return array
     */
    protected function decreaseItemsAmount($items, $from, $to)
    {
        if (!$from) {
            return $items;
        }

        $total = 0;
        $ratio = $to / $from;
        $last  = count($items) - 1;
        $result = [];

        foreach ($items as $i => $item) {
            if ($i < $last) {
                $item['total'] = number_format($item['total'] * $ratio, 2, '.', '');
                $item['price'] = number_format($item['price'] * $ratio, 2, '.', '');
            } else {
                $item['total'] = number_format($to - $total, 2, '.', '');
                $item['price'] = number_format($item['total'] / $item['count'], 2, '.', '');
            }

            $total += $item['total'];

            // Если после форматирования цен товаров получилось так,
            // что стоимость позиции не равна произведению его цена на кол-во,
            // нужно, если товар в позиции один, откорректировать цену товара,
            // либо если их больше одного, вынести один товар в новую позицию
            // с необходимой корректировкой цены
            if (abs($item['price'] * $item['count'] - $item['total']) > 0.0001) {
                if ($item['count'] == 1) {
                    $item['price'] = $item['total'];
                } else if (intval($item['count']) == $item['count']) {
                    $newItem = array_merge([], $item);
                    $newItem['count'] = 1;
                    $newItem['price'] += $item['total'] - $item['price'] * $item['count'];
                    $newItem['price'] = number_format($newItem['price'], 2, '.', '');
                    $newItem['total'] = $newItem['price'];

                    $item['count']--;
                    $item['total'] -= $newItem['total'];
                    $item['total'] = number_format($item['total'], 2, '.', '');

                    $result[] = $newItem;
                }
            }

            $result[] = $item;
        }

        return $result;
    }
}
