<?php

namespace Commerce;

use Interfaces\Cart;
use Interfaces\Processor;

class Commerce
{
    use SettingsTrait;

    const VERSION = 'v0.1.0';

    private $modx;
    private $cart;
    private $processor;

    private $payments;
    private $deliveries;

    private $lexicon;
    private $lang = [];

    public function __construct($modx, array $params)
    {
        $this->modx = $modx;
        $this->setSettings($params);

        $this->lexicon = new \Helpers\Lexicon($modx, [
            'langDir' => 'assets/plugins/commerce/lang/',
            'lang'    => $modx->getConfig('manager_language'),
        ]);

        $modx->invokeEvent('OnInitializeCommerce');

        if (!($this->cart instanceof Cart)) {
            $this->cart = new Carts\DocListerCart($modx);
        }

        $cartsManager = CartsManager::getManager();
        $cartsManager->addCart('products', $this->cart);

        foreach (['wishlist', 'comparison'] as $cart) {
            if (!$cartsManager->has($cart)) {
                $cartsManager->addCart($cart, new Carts\DocListerItemsList($modx, $cart));
            }
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

    public function registerPayment($code, $title, $processor)
    {
        if (is_null($this->payments)) {
            $this->payments = [];
        }

        if (isset($this->payments[$code])) {
            throw new \Exception('Payment with code "' . print_r($code, true) . '" already registered!');
        }

        $this->payments[$code] = [
            'title'     => $title,
            'processor' => $processor,
        ];
    }

    public function getPayments()
    {
        if (is_null($this->payments)) {
            $this->modx->invokeEvent('OnRegisterPayments');

            if (is_null($this->payments)) {
                $this->payments = [];
            }
        }

        return $this->payments;
    }

    public function getPayment($code)
    {
        $payments = $this->getPayments();

        if (!isset($payments[$code])) {
            throw new \Exception('Payment with code "' . $code . '" not registered!');
        }

        return $payments[$code];
    }

    public function getDeliveries()
    {
        if (is_null($this->deliveries)) {
            $this->deliveries = [];

            $this->modx->invokeEvent('OnRegisterDelivery', [
                'rows' => &$this->deliveries,
            ]);
        }

        return $this->deliveries;
    }

    public function getDelivery($code)
    {
        $deliveries = $this->getDeliveries();

        if (!isset($deliveries[$code])) {
            throw new \Exception('Delivery with code "' . $code . '" not registered!');
        }

        return $deliveries[$code];
    }

    public function getUserLanguage($instance = 'common')
    {
        $this->lang = array_merge($this->lang, $this->lexicon->loadLang($instance));
        return $this->lang;
    }

    public function setProcessor(Processor $processor)
    {
        if ($this->processor instanceof Processor) {
            throw new \Exception('Processor already set!');
        }

        $this->processor = $processor;
    }

    public function loadProcessor()
    {
        if (is_null($this->processor)) {
            $this->modx->invokeEvent('OnInitializeOrderProcessor');

            if (!($this->processor instanceof Processor)) {
                $this->processor = new Processors\SimpleProcessor($this->modx);
            }
        }

        return $this->processor;
    }

    public function processRoute($route)
    {
        switch ($route) {
            case 'commerce/action': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && is_string($_POST['action']) && preg_match('/^[a-z]+\/[a-z]+$/', $_POST['action'])) {
                    try {
                        echo $this->runAction($_POST['action'], isset($_POST['data']) ? $_POST['data'] : []);
                        exit;
                    } catch (\Exception $exc) {
                        $this->modx->logEvent(0, 3, $exc->getMessage());
                        throw $exc;
                    } catch (\TypeError $exc) {
                        $this->modx->logEvent(0, 3, $exc->getMessage());
                        throw $exc;
                    }
                }
                break;
            }

            case 'commerce/cart/contents': {
                $response = [
                    'status' => 'failed',
                ];

                if (isset($_POST['hashes']) && is_array($_POST['hashes'])) {
                    foreach ($_POST['hashes'] as $hash) {
                        if (!is_string($hash)) {
                            continue;
                        }

                        if (empty($_SESSION['commerce.cart-' . $hash])) {
                            continue;
                        }

                        $params = unserialize($_SESSION['commerce.cart-' . $hash]);

                        if (!isset($response['markup'])) {
                            $response['markup'] = [];
                            $response['status'] = 'success';
                        }

                        $response['markup'][$hash] = $this->modx->runSnippet('Cart', $params);
                    }
                }

                echo json_encode($response);
                exit;
            }

            case 'commerce/data/update': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $this->loadProcessor()->updateRawData($_POST);
                    echo json_encode(['status' => 'success']);
                    exit;
                }
                break;
            }
        }

        if (preg_match('/^commerce\/([a-z-_]+?)\/([a-z-]+?)$/', $route, $parts)) {
            $payment = $this->getPayment($parts[1]);

            if (is_null($payment)) {
                return;
            }

            switch ($parts[2]) {
                case 'payment-process': {
                    if ($payment['processor']->handleCallback()) {
                        exit;
                    }
                    break;
                }

                case 'payment-success': {
                    if ($payment['processor']->handleSuccess()) {
                        $docid = $this->getSetting('payment_success_page_id', $this->modx->getConfig('site_start'));
                        $url   = $this->modx->makeUrl($docid);
                        $this->modx->sendRedirect($url);
                        exit;
                    }
                    break;
                }

                case 'payment-failed': {
                    if ($payment['processor']->handleError()) {
                        $docid = $this->getSetting('payment_failed_page_id', $this->modx->getConfig('site_start'));
                        $url   = $this->modx->makeUrl($docid);
                        $this->modx->sendRedirect($url);
                        exit;
                    }
                    break;
                }
            }
        }
    }

    public function runAction($action, array $data = [])
    {
        $response = [
            'status' => 'failed',
        ];

        $instance = 'products';
        if (isset($data['instance']) && is_string($data['instance'])) {
            $instance = $data['instance'];
        }

        $cart = CartsManager::getManager()->getCart($instance);

        if (!is_null($cart)) {
            switch ($action) {
                case 'cart/add': {
// TODO: validation!
                    $data = array_merge(['id' => 0, 'name' => 'Noname', 'count' => 1, 'price' => 0, 'options' => [], 'meta' => []], $data);
                    $row = $cart->add($data['id'], $data['name'], $data['count'], $data['price'], $data['options'], $data['meta']);

                    $response = [
                        'status' => 'success',
                        'row'    => $row,
                    ];

                    break;
                }

                case 'cart/update': {
// TODO: validation!
                    if ($cart->update($data['row'], $data['attributes'])) {
                        $response['status'] = 'success';
                    }

                    break;
                }

                case 'cart/remove': {
                    if ($cart->remove($data['row'])) {
                        $response['status'] = 'success';
                    }
                    break;
                }
            }
        }

        return json_encode($response);
    }

    public function formatPrice($price)
    {
        return number_format(floatval($price), 2, ',', ' ') . ' руб.';
    }
}
