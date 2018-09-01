<?php

namespace FormLister;

class Order extends Form
{
    public function render()
    {
        $delivery = [];
        $this->modx->invokeEvent('OnRegisterDelivery', [
            'rows' => &$delivery,
        ]);

        $payments = [];
        foreach ($this->modx->commerce->getPayments() as $code => $payment) {
            $payments[$code] = [
                'title'  => $payment['title'],
                'markup' => $payment['processor']->getMarkup(),
            ];
        }

        foreach (['delivery' => $this->getCFGDef('default_delivery'), 'payments' => $this->getCFGDef('default_payment')] as $type => $default) {
            $output = '';
            $rows   = $$type;
            $index  = 0;
            $markup = '';

            foreach ($rows as $code => $row) {
                $output .= $this->DLTemplate->parseChunk('order_form_' . $type . '_row', [
                    'code'   => $code,
                    'title'  => $row['title'],
                    'price'  => isset($row['price']) ? $row['price'] : '',
                    'markup' => isset($row['markup']) ? $row['markup'] : '',
                    'active' => 1 * ($default == $code || empty($default) && !$index),
                    'index'  => $index,
                ]);

                $markup .= isset($row['markup']) ? $row['markup'] : '';
            }

            if (!empty($output)) {
                $output = $this->DLTemplate->parseChunk('order_form_' . $type, [
                    'wrap'   => $output,
                    'markup' => $markup,
                ]);
            }

            $this->setPlaceholder($type, $output);
        }

        return parent::render();
    }

    public function process()
    {
        if ($this->checkSubmitProtection()) {
            return;
        }

        $cart = $this->modx->commerce->getCart();
        $items = $cart->getItems();
        $params = [
            '_FL'   => $this,
            'items' => &$items,
        ];

        $this->modx->invokeEvent('OnBeforeOrderProcessing', $params);

        if (is_array($params['items'])) {
            $cart->setItems($items);
        }

        $processor = $this->modx->commerce->loadProcessor();
        $processor->createOrder($items, $this->getFormData('fields'));

        parent::process();

        $this->modx->invokeEvent('OnOrderProcessed', $params);
        $processor->processPayment($this);
    }

    public function postProcess()
    {
        $this->redirect();
    }
}
