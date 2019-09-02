<?php

namespace FormLister;

use Commerce\Lexicon;

class Order extends Form
{
    private $order;
    private $cart;

    public function __construct (\DocumentParser $modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);

        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/snippets/FormLister/core/lang/',
            'lang'    => $this->getCFGDef('lang', $this->modx->getConfig('manager_language'))
        ));

        $lang = $this->lexicon->loadLang('form');
        if ($lang) {
            $this->log('Lexicon loaded', array('lexicon' => $lang));
        }

        if (strtolower($this->getCFGDef('formMethod', 'post')) == 'manual') {
            $this->_rq = $this->getCFGDef('formData', []);
        }
    }

    public function getPaymentsAndDelivery()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $delivery  = $this->modx->commerce->getDeliveries();
        $payments  = [];
        $result    = [];

        $fields = $this->prerenderForm($this->getFormStatus());

        foreach ($this->modx->commerce->getPayments() as $code => $payment) {
            $payments[$code] = [
                'title'  => $payment['title'],
                'markup' => $payment['processor']->getMarkup(),
            ];
        }

        foreach (['delivery' => $processor->getCurrentDelivery(), 'payments' => $processor->getCurrentPayment()] as $type => $default) {
            $output = '';
            $rows   = $$type;
            $index  = 0;
            $markup = '';
            $defaultValid = false;

            foreach (array_keys($rows) as $code) {
                if ($code == $default) {
                    $defaultValid = true;
                    break;
                }
            }

            if (!$defaultValid) {
                reset($rows);
                $default = key($rows);
            }

            foreach ($rows as $code => $row) {
                $output .= $this->DLTemplate->parseChunk($this->getCFGDef($type . 'RowTpl'), [
                    'code'   => $code,
                    'title'  => $row['title'],
                    'price'  => isset($row['price']) ? $row['price'] : '',
                    'markup' => isset($row['markup']) ? $row['markup'] : '',
                    'active' => 1 * ($default == $code),
                    'index'  => $index++,
                ]);

                $markup .= isset($row['markup']) && is_scalar($row['markup']) ? $row['markup'] : '';
            }

            if (!empty($output)) {
                $output = $this->DLTemplate->parseChunk($this->getCFGDef($type . 'Tpl'), array_merge($fields, [
                    'wrap'   => $output,
                    'markup' => $markup,
                ]));
            }

            $result[$type] = $output;
        }

        return $result;
    }

    public function render()
    {
        $this->modx->commerce->loadProcessor()->startOrder();

        $cartName = $this->getCFGDef('cartName', 'products');
        $items = ci()->carts->getCart($cartName)->getItems();

        if (empty($items)) {
            return false;
        }

        $this->setPlaceholder('form_hash', $this->getCFGDef('form_hash'));

        foreach ($this->getPaymentsAndDelivery() as $type => $markup) {
            $this->setPlaceholder($type, $markup);
        }

        return parent::render();
    }

    public function process()
    {
        if ($this->checkSubmitProtection()) {
            return;
        }

        $processor = $this->modx->commerce->loadProcessor();
        $processor->startOrder();

        $cartName = $this->getCFGDef('cartName', 'products');
        $cart = ci()->carts->getCart($cartName);
        $items = $cart->getItems();

        $params = [
            'FL'   => $this,
            'items' => &$items,
        ];

        $fields = $this->getFormData('fields');

        if (!empty($fields['payment_method'])) {
            $payment = $this->modx->commerce->getPayment($fields['payment_method']);
            $this->setField('payment_method_title', $payment['title']);
        }

        if (!empty($fields['delivery_method'])) {
            $delivery = $this->modx->commerce->getDelivery($fields['delivery_method']);
            $this->setField('delivery_method_title', $delivery['title']);
        }

        $this->modx->invokeEvent('OnBeforeOrderProcessing', $params);

        if (is_array($params['items'])) {
            $cart->setItems($items);
        }

        $this->order = $processor->createOrder($items, $this->getFormData('fields'));
        $this->cart  = $processor->getCart();

        $this->modx->invokeEvent('OnBeforeOrderSending', [
            'FL'    => $this,
            'order' => &$this->order,
            'cart'  => &$this->cart,
        ]);

        $this->setPlaceholder('order', $this->order);
        parent::process();
    }

    public function postProcess()
    {
        $this->modx->commerce->loadProcessor()->postProcessForm($this);

        $this->modx->invokeEvent('OnOrderProcessed', [
            'FL'    => $this,
            'order' => $this->order,
            'cart'  => $this->cart,
        ]);

        $cartName = $this->getCFGDef('cartName', 'products');
        ci()->carts->getCart($cartName)->clean();

        $_SESSION['commerce_order_completed'] = true;

        parent::postProcess();
    }

    public function initCaptcha()
    {
        if (!$this->getCFGDef('commerceCaptchaFix', 0)) {
            parent::initCaptcha();
        }
    }
}
