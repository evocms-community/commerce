<?php

namespace FormLister;

use Commerce\Lexicon;
use Commerce\Controllers\Traits;

class Order extends Form
{
    use Traits\CustomTemplatesPathTrait;

    protected $order;
    protected $cart;

    public function __construct (\DocumentParser $modx, $cfg = array())
    {
        $modx->invokeEvent('OnInitializeOrderForm', [
            'config' => &$cfg,
        ]);

        $cfg = $this->initializeCustomTemplatesPath($cfg);
        $cfg['lang'] = $modx->commerce->getCurrentLang();

        parent::__construct($modx, $cfg);

        $this->lexicon = new Lexicon($modx, array(
            'langDir' => 'assets/snippets/FormLister/core/lang/',
            'lang'    => $this->getCFGDef('lang', $this->modx->getConfig('manager_language')),
            'handler' => $this->getCFGDef('lexiconHandler', '\\Helpers\\Lexicon\\EvoBabelLexiconHandler')
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

        $currentDelivery = $processor->getCurrentDelivery();
        $currentPayment  = $processor->getCurrentPayment();

        $this->modx->invokeEvent('OnBeforeOrderAddonsRender', [
            'payments'         => &$payments,
            'delivery'         => &$delivery,
            'current_payment'  => &$currentPayment,
            'current_delivery' => &$currentDelivery,
        ]);

        foreach (['delivery' => $currentDelivery, 'payments' => $currentPayment] as $type => $default) {
            $output = '';
            $methods = $$type;
            $index  = 0;
            $markup = '';
            $defaultValid = false;
            $rows = [];

            foreach (array_keys($methods) as $code) {
                if ($code == $default) {
                    $defaultValid = true;
                    break;
                }
            }

            if (!$defaultValid) {
                reset($methods);
                $default = key($methods);
            }

            foreach ($methods as $code => $method) {
                $active = $default == $code;

                $row = array_merge([
                    'code'     => $code,
                    'title'    => $method['title'],
                    'price'    => '',
                    'markup'   => '',
                    'active'   => (int)$active,
                    'selected' => $active ? ' selected' : '',
                    'checked'  => $active ? ' checked' : '',
                    'index'    => $index++,
                ], $method);

                $rows[$code] = $row;

                $output .= $this->DLTemplate->parseChunk($this->getCFGDef($type . 'RowTpl'), $row);

                $markup .= isset($method['markup']) && is_scalar($method['markup']) ? $method['markup'] : '';
            }

            if (!empty($output) || $this->getCFGDef('wrapEmpty' . ucfirst($type), false)) {
                $output = $this->DLTemplate->parseChunk($this->getCFGDef($type . 'Tpl'), array_merge($fields, [
                    'wrap'   => $output,
                    'markup' => $markup,
                    'rows'   => $rows,
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
            $this->redirect('exitTo');
            $this->renderTpl = $this->getCFGDef('skipTpl', '');
            $this->setValid(false);
        } else {
            $this->setPlaceholder('form_hash', $this->getCFGDef('form_hash'));

            foreach ($this->getPaymentsAndDelivery() as $type => $markup) {
                $this->setPlaceholder($type, $markup);
            }

            if ($this->isSubmitted()) {
                $this->modx->commerce->loadProcessor()->updateRawData($_POST);
            }
        }

        return parent::render();
    }

    public function process()
    {
        $processor = $this->modx->commerce->loadProcessor();

        $cartName = $this->getCFGDef('cartName', 'products');
        $this->cart = ci()->carts->getCart($cartName);

        if ($this->checkSubmitProtection()) {
            return;
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

        $items = $this->cart->getItems();

        $preventOrder = false;

        $this->modx->invokeEvent('OnBeforeOrderProcessing', [
            'FL'      => $this,
            'items'   => &$items,
            'prevent' => &$preventOrder,
        ]);

        if ($preventOrder) {
            if (empty($this->getFormData('messages'))) {
                $this->addMessage('[%order.order_cancelled%]');
            }

            return;
        }

        if (is_array($items)) {
            $this->cart->setItems($items);
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
        ci()->flash->set('last_order_id', $this->order['id']);

        parent::postProcess();
    }

    public function initCaptcha()
    {
        if (!$this->getCFGDef('commerceCaptchaFix', 0)) {
            parent::initCaptcha();
        }
    }

    public function getFormHash()
    {
        $hash = parent::getFormHash();

        if (isset($this->cart)) {
            $items = $this->cart->getItems();

            $total = 0;
            $subtotals = [];
            $this->modx->invokeEvent('OnCollectSubtotals', [
                'rows'     => &$subtotals,
                'total'    => &$total,
                'realonly' => true,
            ]);

            $items = array_merge($items, $subtotals);
            $hash = md5($hash . json_encode($items));
        }

        return $hash;
    }
}
