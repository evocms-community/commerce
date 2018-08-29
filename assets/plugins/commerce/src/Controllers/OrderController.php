<?php

namespace FormLister;

class OrderController extends Form
{
    public function render()
    {
        foreach (['delivery' => $this->getCFGDef('default_delivery'), 'payments' => $this->getCFGDef('default_payment')] as $type => $default) {
            $output = '';
            $rows   = [];

            $this->modx->invokeEvent('OnRegister' . ucfirst($type), [
                'rows' => &$rows,
            ]);

            foreach ($rows as $code => $row) {
                $output .= $this->DLTemplate->parseChunk('order_form_' . $type . '_row', [
                    'code'   => $code,
                    'title'  => $row['title'],
                    'markup' => isset($row['markup']) ? $row['markup'] : '',
                    'active' => 1 * ($default == $code),
                ]);
            }

            if (!empty($output)) {
                $output = $this->DLTemplate->parseChunk('order_form_' . $type, [
                    'wrap' => $output,
                ]);
            }

            $this->setField($type, $output);
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

        $this->modx->invokeEvent('OnBeforeProcessOrder', $params);

        if (is_array($params['items'])) {
            $cart->setItems($items);
        }

        parent::process();

        $this->modx->invokeEvent('OnProcessOrder', $params);
        //$cart->clean();
    }

    public function postProcess()
    {
        $this->redirect();
    }
}
