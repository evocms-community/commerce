<?php

namespace Commerce;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\Processor;
use Commerce\Carts\SessionCartStore;
use Commerce\Carts\CookiesCartStore;
use Commerce\Carts\ProductsCart;
use Commerce\Carts\ProductsList;
use Commerce\Lexicon;

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
    private $langDir = 'assets/plugins/commerce/lang/';
    private $lang;
    private $backendLang;
    private $langKeys = [];
    private $langData = [];

    public function __construct($modx, array $params)
    {
        $this->modx = $modx;
        $this->setSettings($params);
        $this->currency = new Currency($modx);

        $this->backendLang = $modx->getConfig('manager_language');
    }

    public function initializeCommerce()
    {
        $this->modx->invokeEvent('OnInitializeCommerce');

        $carts = ci()->carts;
        $carts->registerStore('session', new SessionCartStore());

        if (!$carts->has('products')) {
            $this->cart = new ProductsCart($this->modx);
            $this->cart->setCurrency($this->currency->getCurrencyCode());
            $carts->addCart('products', $this->cart);
        }

        $this->cart->setTitleField($this->getSetting('title_field', 'pagetitle'));
        $this->cart->setPriceField($this->getSetting('price_field', 'price'));

        foreach (['wishlist', 'comparison'] as $listname) {
            if (!$carts->has($listname)) {
                $list = new ProductsList($this->modx, $listname);
                $list->setStore(new CookiesCartStore($listname));
                $carts->addCart($listname, $list);
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

    public function setLang($code)
    {
        if ($code != $this->lang) {
            $this->lang = $code;

            $this->lexicon = new Lexicon($this->modx, [
                'langDir' => $this->langDir,
                'lang'    => $this->lang,
            ]);

            foreach ($this->langKeys as $instance) {
                $this->getUserLanguage($instance);
            }

            return true;
        }

        return false;
    }

    public function getUserLanguage($instance = 'common')
    {
        if (is_null($this->lang)) {
            $this->setLang($this->backendLang);
        }

        if (!isset($this->langKeys[$instance])) {
            $this->langKeys[$instance] = $instance;
            $this->langData = array_merge($this->langData, $this->lexicon->loadLang($instance));
        }

        return $this->langData;
    }

    /**
     * Returns template from language directory
     * 
     * @param  string  $name Template name (without extension)
     * @param  boolean $forceDefaultLanguage Force to use admin language
     * @return string
     */
    public function getUserLanguageTemplate($name, $forceDefaultLanguage = false)
    {
        $lang = $forceDefaultLanguage ? $this->backendLang : $this->lang;
        $filename = realpath(MODX_BASE_PATH . $this->langDir . $lang . '/' . $name . '.tpl');

        if ($filename && is_readable($filename)) {
            return '@CODE:' . file_get_contents($filename);
        }

        throw new \Exception('Template "' . print_r($name, true) . '" not found!');
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
                $this->processor = new Processors\OrdersProcessor($this->modx);
            }
        }

        return $this->processor;
    }

    private function getCartsMarkup($hashes)
    {
        $result = [];

        if (is_array($hashes)) {
            foreach ($hashes as $hash) {
                if (!is_string($hash)) {
                    continue;
                }

                if (($params = $this->restoreParams($hash)) !== false) {
                    $result[$hash] = $this->modx->runSnippet('Cart', $params);
                }
            }
        }

        return $result;
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

                if (isset($_POST['hashes'])) {
                    $markup = $this->getCartsMarkup($_POST['hashes']);

                    if (!empty($markup)) {
                        $response['markup'] = $markup;
                        $response['status'] = 'success';
                    }
                }

                echo json_encode($response);
                exit;
            }

            case 'commerce/data/update': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $this->loadProcessor()->updateRawData($_POST);

                    $result = [
                        'status' => 'success',
                        'markup' => [],
                    ];

                    if (!empty($_POST['hashes']['form'])) {
                        if (($params = $this->restoreParams($_POST['hashes']['form'])) !== false) {
                            $controller = new \FormLister\Order($this->modx, $params);
                            $controller->initForm();
                            $output = $controller->getPaymentsAndDelivery();

                            foreach ($output as $type => $markup) {
                                $output[$type] = $controller->parseChunk('@CODE:' . $markup, [], true);
                            }

                            $result['markup']['form'] = $output;
                        }
                    }

                    if (!empty($_POST['hashes']['carts']) && is_array($_POST['hashes']['carts'])) {
                        $result['markup']['carts'] = $this->getCartsMarkup($_POST['hashes']['carts']);
                    }

                    echo json_encode($result);
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

                break;
            }

            case 'commerce/module/action': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $manager = new \Commerce\Module\Manager($this->modx, []);

                    $route = filter_input(INPUT_POST, 'route', FILTER_VALIDATE_REGEXP, ['options' => [
                        'regexp'  => '/^[a-z]+(:?\/[a-z-]+)*$/',
                        'default' => '',
                    ]]);

                    echo $manager->processRoute($route);
                    exit;
                }

                break;
            }
        }

        if (preg_match('/^commerce\/([a-z-_]+?)\/([a-z-]+?)$/', $route, $parts)) {
            try {
                $payment = $this->getPayment($parts[1]);
            } catch (\Exception $e) {
                return;
            }

            $paymentProcessor = $payment['processor'];

            switch ($parts[2]) {
                case 'payment-process': {
                    if ($paymentProcessor->handleCallback()) {
                        exit;
                    }
                    break;
                }

                case 'payment-success': {
                    if ($paymentProcessor->handleSuccess()) {
                        $docid = $this->getSetting('payment_success_page_id', $this->modx->getConfig('site_start'));
                        $url   = $this->modx->makeUrl($docid);

                        $payment_hash = $paymentProcessor->getRequestPaymentHash();

                        if (!empty($payment_hash) && is_scalar($payment_hash)) {
                            $payment = $this->loadProcessor()->loadPaymentByHash($payment_hash);

                            if (!empty($payment)) {
                                ci()->flash->setMultiple([
                                    'last_order_id'   => $payment['order_id'],
                                    'last_payment_id' => $payment['id'],
                                ]);
                            }
                        }

                        $this->modx->sendRedirect($url);
                        exit;
                    }
                    break;
                }

                case 'payment-failed': {
                    if ($paymentProcessor->handleError()) {
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
            $cart = ci()->carts->getCartByHash($data['cart']['hash']);
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
                    if (!empty($data['row']) && !empty($data['attributes']) && $cart->update($data['row'], $data['attributes'])) {
                        $response['status'] = 'success';
                    }

                    break;
                }

                case 'cart/remove': {
                    if (!empty($data['row'])) {
                        if ($cart->remove($data['row'])) {
                            $response['status'] = 'success';
                        }
                    } else if (!empty($data['data']['row'])) {
                        if ($cart->remove($data['data']['row'])) {
                            $response['status'] = 'success';
                        }
                    } else if (!empty($data['data']['id'])) {
                        if ($cart->removeById($data['data']['id'])) {
                            $response['status'] = 'success';
                        }
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

        setlocale(LC_NUMERIC, 'C');
        $result = $formlister->validate($validator, $rules, $data);

        if ($result !== true && !empty($result)) {
            return $result;
        }

        return true;
    }

    public function storeParams(array $params)
    {
        $hash = md5(json_encode($params));
        $_SESSION['commerce.' . $hash] = serialize($params);

        return $hash;
    }

    public function restoreParams($hash)
    {
        if (!empty($_SESSION['commerce.' . $hash])) {
            return unserialize($_SESSION['commerce.' . $hash]);
        }

        return false;
    }
}
