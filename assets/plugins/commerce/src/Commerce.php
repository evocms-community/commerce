<?php

namespace Commerce;

use Interfaces\Cart;

class Commerce
{
    const VERSION = 'v0.1.0';

    private $modx;
    private $cart;

    const SERVICE_PAYMENT  = 1;
    const SERVICE_DELIVERY = 2;
    const SERVICE_SUBTOTAL = 3;

    private $serviceTypes = [
        self::SERVICE_PAYMENT  => 'Payments',
        self::SERVICE_DELIVERY => 'Delivery',
        self::SERVICE_SUBTOTAL => 'Subtotal',
    ];

    private $services = [];

    private $lexicon;
    private $lang = [];

    public function __construct($modx)
    {
        $this->modx = $modx;

        $this->lexicon = new \Helpers\Lexicon($modx, [
            'langDir' => 'assets/plugins/commerce/lang/',
            'lang'    => $modx->getConfig('manager_language'),
        ]);

        $modx->invokeEvent('OnInitializeCommerce');

        if (!($this->cart instanceof Cart)) {
            $this->cart = new DefaultCart($modx);
        }
    }

    public function getCart()
    {
        return $this->cart;
    }

    public function setCart(Cart $cart)
    {
        if ($this->cart instanceof Cart) {
            throw new \Exception('Cart already set!');
        }

        $this->cart = $cart;
    }

    public function getVersion()
    {
        return self::VERSION;
    }

    public function registerService($type, $code, $title, $processor)
    {
        if (!isset($this->services[$type])) {
            $this->services[$type] = [];
        }

        if (isset($this->services[$type][$code])) {
            throw new \Exception('Service with code "' . print_r($code, true) . '" already registered!');
        }

        $this->services[$type][$code] = [
            'title'     => $title,
            'processor' => $processor,
        ];
    }

    public function getServices($type)
    {
        if (!isset($this->services[$type])) {
            if (isset($this->serviceTypes[$type])) {
                $eventType = $this->serviceTypes[$type];
                $this->modx->invokeEvent('OnRegister' . $eventType);
            }

            if (!isset($this->services[$type])) {
                $this->services[$type] = [];
            }
        }

        return $this->services[$type];
    }

    public function getUserLanguage($instance = 'common')
    {
        $this->lang = array_merge($this->lang, $this->lexicon->loadLang($instance));
        return $this->lang;
    }

    public function runAction($action, array $data = [])
    {
        $response = [
            'status' => 'failed',
        ];

        switch ($action) {
            case 'cart/add': {
// TODO: validation!
                $data = array_merge(['id' => 0, 'name' => 'Noname', 'count' => 1, 'price' => 0, 'options' => [], 'meta' => []], $data);
                $row = $this->cart->add($data['id'], $data['name'], $data['count'], $data['price'], $data['options'], $data['meta']);
                
                $response = [
                    'status' => 'success',
                    'row'    => $row,
                ];
                
                break;
            }

            case 'cart/update': {
// TODO: validation!
                if ($this->cart->update($data['row'], $data['attributes'])) {
                    $response['status'] = 'success';
                }

                break;
            }

            case 'cart/remove': {
                if ($this->cart->remove($data['row'])) {
                    $response['status'] = 'success';
                }
                break;
            }
        }

        return json_encode($response);
    }

    public function formatPrice($price)
    {
        return number_format($price, 2, ',', ' ') . ' руб.';
    }
}
