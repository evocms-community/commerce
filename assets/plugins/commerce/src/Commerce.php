<?php

namespace Commerce;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\Processor;
use Commerce\Carts\SessionCartStore;
use Commerce\Carts\ProductsCart;
use Commerce\Carts\ProductsList;
use Helpers\Lexicon;

class Commerce
{
    use SettingsTrait;

    const VERSION = 'v0.1.0';

    public $currency;

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
        $this->currency = new Currency($modx);

        $this->lexicon = new Lexicon($modx, [
            'langDir' => 'assets/plugins/commerce/lang/',
            'lang'    => $modx->getConfig('manager_language'),
        ]);

        $modx->invokeEvent('OnInitializeCommerce');

        $cartsManager = CartsManager::getManager();
        $cartsManager->registerStore('session', new SessionCartStore());

        if (!$cartsManager->has('products')) {
            $this->cart = new ProductsCart($modx);
            $this->cart->setCurrency($this->currency->getCurrencyCode());
            $cartsManager->addCart('products', $this->cart);
        }

        $this->cart->setTitleField($this->getSetting('title_field', 'pagetitle'));
        $this->cart->setPriceField($this->getSetting('price_field', 'price'));

        foreach (['wishlist', 'comparison'] as $cart) {
            if (!$cartsManager->has($cart)) {
                $cartsManager->addCart($cart, new ProductsList($modx, $cart));
            }
        }
    }

    public function getCart()
    {
        return $this->cart;
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
                    $manager = CartsManager::getManager();

                    foreach ($_POST['hashes'] as $hash) {
                        if (!is_string($hash)) {
                            continue;
                        }

                        if (($params = $manager->restoreParams($hash)) !== false) {
                            if (!isset($response['markup'])) {
                                $response['markup'] = [];
                                $response['status'] = 'success';
                            }

                            $response['markup'][$hash] = $this->modx->runSnippet('Cart', $params);
                        }
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

            case 'commerce/currency/set': {
                $response = [
                    'status' => 'failed',
                ];

                if (isset($_POST['code'])) {
                    try {
                        $this->currency->setCurrency($_POST['code']);
                    } catch (\Exception $e) {
                        $response['error'] = $e->getMessage();
                        echo json_encode($response);
                        exit;
                    }

                    ci()->carts->changeCurrency($this->currency->getCurrencyCode());

                    $response['status'] = 'success';
                    echo json_encode($response);
                    exit;
                }

                exit;
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

        if (!empty($data['cart']['hash']) && is_string($data['cart']['hash'])) {
            $cart = CartsManager::getManager()->getCartByHash($data['cart']['hash']);
        }

        if (empty($cart)) {
            $instance = 'products';

            if (isset($data['cart']['instance']) && is_string($data['cart']['instance'])) {
                $instance = $data['cart']['instance'];
            } elseif (isset($data['instance']) && is_string($data['instance'])) {
                $instance = $data['instance'];
            }

            $cart = CartsManager::getManager()->getCart($instance);
        }

        if (!is_null($cart)) {
            switch ($action) {
                case 'cart/add': {
                    $row = $cart->add($data);

                    if ($row !== false) {
                        $response = [
                            'status' => 'success',
                            'row'    => $row,
                        ];
                    }

                    break;
                }

                case 'cart/update': {
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

    public function formatPrice($price, $currency = null)
    {
        return call_user_func_array([$this->currency, 'format'], func_get_args());
    }

    public function validate($data, array $rules)
    {
        $formlister = new \FormLister\Form($this->modx);
        $validator  = new \FormLister\Validator;

        $result = $formlister->validate($validator, $rules, $data);

        if ($result !== true && !empty($result)) {
            return $result;
        }

        return true;
    }

    public function isTableExists($table)
    {
        try {
            $query = $this->modx->db->query("SHOW FIELDS FROM " . $table, false);
        } catch (\Exception $e) {
            return false;
        }

        return $this->modx->db->getRecordCount($query) > 0;
    }
}
