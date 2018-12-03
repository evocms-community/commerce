<?php

namespace FormLister;

class Order extends Form
{
    public function getPaymentsAndDelivery()
    {
        $processor = $this->modx->commerce->loadProcessor();
        $delivery  = $this->modx->commerce->getDeliveries();
        $payments  = [];
        $result    = [];

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
                    'index'  => $index,
                ]);

                $markup .= isset($row['markup']) && is_scalar($row['markup']) ? $row['markup'] : '';
            }

            if (!empty($output)) {
                $output = $this->DLTemplate->parseChunk($this->getCFGDef($type . 'Tpl'), [
                    'wrap'   => $output,
                    'markup' => $markup,
                ]);
            }

            $result[$type] = $output;
        }

        return $result;
    }

    public function render()
    {
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

        $fields = $this->getFormData('fields');

        if (!empty($fields['payment_method'])) {
            $payment = $this->modx->commerce->getPayment($fields['payment_method']);
            $this->setField('payment_method_title', $payment['title']);
        }

        if (!empty($fields['delivery_method'])) {
            $delivery = $this->modx->commerce->getDelivery($fields['delivery_method']);
            $this->setField('delivery_method_title', $delivery['title']);
        }

        $processor->createOrder($items, $this->getFormData('fields'));
        parent::process();
        $processor->postProcessForm($this);

        $this->modx->invokeEvent('OnOrderProcessed', [
            'order' => $processor->getOrder(),
            'cart'  => $processor->getCart(),
        ]);

        $this->redirect();
    }
}
