<?php

namespace Commerce;

use Commerce\Interfaces\Cart;
use Commerce\Interfaces\Processor;
use Commerce\Carts\SessionCartStore;
use Commerce\Carts\CookiesCartStore;
use Commerce\Carts\InstantCartStore;
use Commerce\Carts\ProductsCart;
use Commerce\Carts\ProductsList;
use Commerce\Lexicon;
use bLang\bLang;

class Commerce
{
    use SettingsTrait {
        setSettings as public;
    }

    const VERSION = '0.13.1';

    public $currency;

    private $modx;
    private $processor;

    private $payments;
    private $deliveries;

    private $lexicon;
    private $langDir = 'assets/plugins/commerce/lang/';
    private $lang;
    private $backendLang;
    private $langKeys = [];
    private $langData = [];

    public $langRelated = [
        'ru' => 'russian-UTF8',
        'ua' => 'russian-UTF8',
        'en' => 'english',
    ];

    public function __construct($modx, array $params)
    {
        $this->modx = $modx;
        $this->setSettings($params);

        $this->lexicon = new Lexicon($this->modx, [
            'langDir' => $this->langDir,
        ]);

        $this->backendLang = $modx->getLocale();
    }

    public function initializeCommerce()
    {
        define('COMMERCE_INITIALIZED', true);
        define('COMMERCE_PATH', dirname(__DIR__) . '/');

        $this->modx->invokeEvent('OnInitializeCommerce');

        if (empty($this->currency)) {
            $this->currency = new Currency($this->modx);
        }

        $carts = ci()->carts;

        if (!$carts->hasStore('instant')) {
            $carts->registerStore('instant', InstantCartStore::class);
        }

        if (!$carts->hasStore('session')) {
            $carts->registerStore('session', SessionCartStore::class);
        }

        if (!$carts->hasStore('cookies')) {
            $carts->registerStore('cookies', CookiesCartStore::class);
        }

        if (!$carts->has('products')) {
            $cart = new ProductsCart($this->modx);
            $carts->addCart('products', $cart);
        } else {
            $cart = $carts->getCart('products');
        }

        $cart->setCurrency($this->currency->getCurrencyCode());
        $cart->setTitleField($this->getSetting('title_field', 'pagetitle'));
        $cart->setPriceField($this->getSetting('price_field', 'price'));

        foreach (['wishlist', 'comparison'] as $listname) {
            if (!$carts->has($listname)) {
                $list = new ProductsList($this->modx, $listname, 'cookies');
                $carts->addCart($listname, $list);
            }
        }

        if ($this->modx->isFrontend() && $this->isBLangEnabled()) {
            $bLang = bLang::GetInstance($this->modx);

            $this->setLang($bLang->lang);

            $currencyCode = $this->currency->getLangCurrencyCode($bLang->lang);
            $this->currency->setCurrency($currencyCode);
        }

        $this->modx->invokeEvent('OnCommerceInitialized');
    }

    public function getCart()
    {
        return ci()->carts->getCart('products');
    }

    public function getWishlist()
    {
        return ci()->carts->getCart('wishlist');
    }

    public function getComparison()
    {
        return ci()->carts->getCart('comparison');
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

            $this->lexicon->config->setConfig([
                'lang' => $this->lang,
            ]);

            if (!isset($this->langData[$this->lang])) {
                $this->langData[$this->lang] = $this->lexicon->fromFile($this->langKeys);
            }
        }

        return $this->lang;
    }

    public function getCurrentLang()
    {
        if (is_null($this->lang)) {
            $this->setLang($this->backendLang);
        }

        return $this->lang;
    }

    public function getUserLanguage($instance = 'common', $forceDefaultLanguage = false)
    {
        if (is_null($this->lang)) {
            $this->setLang($this->backendLang);
        }

        $usedLangCode = $forceDefaultLanguage ? $this->backendLang : $this->lang;

        if (!isset($this->langData[$usedLangCode])) {
            $this->langData[$usedLangCode] = $this->lexicon->fromFile($this->langKeys);
        }

        if (!isset($this->langKeys[$instance])) {
            $this->langKeys[$instance] = $instance;

            foreach ($this->langData as $code => $data) {
                $this->langData[$code] = array_merge($data, $this->lexicon->fromFile($instance, $code));
            }
        }

        return $this->langData[$usedLangCode];
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

        $defaultPath = 'assets/plugins/commerce/templates/front/';
        $customPath  = trim($this->getSetting('templates_path', ''), '/ ');

        $alias = $this->lexicon->getAlias($lang);

        $filenames = empty($customPath) ? [] : [
            ['B_CODE', $customPath . '/' . $lang . '/' . $name . '.blade.php'],
            ['B_CODE', $defaultPath . $lang . '/' . $name . '.blade.php'],
            ['B_CODE', $customPath . '/' . $alias . '/' . $name . '.blade.php'],
            ['B_CODE', $defaultPath . $alias . '/' . $name . '.blade.php'],
            ['B_CODE', $defaultPath . 'en/' . $name . '.blade.php'],
        ];

        $filenames = array_merge($filenames, [
            ['CODE', $customPath . '/' . $lang . '/' . $name . '.tpl'],
            ['CODE', $defaultPath . $lang . '/' . $name . '.tpl'],
            ['CODE', $customPath . '/' . $alias . '/' . $name . '.tpl'],
            ['CODE', $defaultPath . $alias . '/' . $name . '.tpl'],
            ['CODE', $defaultPath . 'en/' . $name . '.tpl'],
        ]);

        foreach ($filenames as list($type, $filename)) {
            $filename = realpath(MODX_BASE_PATH . $filename);

            if ($filename && is_readable($filename)) {
                return '@' . $type . ':' . file_get_contents($filename);
            }
        }

        throw new \Exception('Template "' . print_r($name, true) . '" not found!');
    }

    public function getUserLexicon($alias, $default = '')
    {
        $underscored = str_replace('.', '_', $alias);
        $variants = ['__' . $underscored, $underscored, '__' . $alias, $alias];

        $bLangEnabled    = $this->isBLangEnabled();
        $evoBabelEnabled = $this->isEvoBabelEnabled();

        foreach ($variants as $variant) {
            if ($bLangEnabled && $value = $this->modx->getConfig($variant)) {
                return $value;
            }

            if ($evoBabelEnabled && isset($_SESSION['perevod'][$variant])) {
                return $_SESSION['perevod'][$variant];
            }
        }

        if (isset($this->langData[$this->lang][$alias])) {
            return $this->langData[$this->lang][$alias];
        }

        return $default;
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

    private function getFormMarkup($hash)
    {
        $result = [];

        if (is_scalar($hash)) {
            $params = $this->restoreParams($hash);

            if ($params !== false) {
                $controller = new \FormLister\Order($this->modx, array_merge($params, [
                    'commerceCaptchaFix' => true,
                    'disableSubmit'      => true,
                ]));
                $controller->initForm();
                $output = $controller->getPaymentsAndDelivery();

                foreach ($output as $type => $markup) {
                    $output[$type] = $controller->parseChunk('@CODE:' . $markup, [], true);
                }

                $result = $output;
            }
        }

        return $result;
    }

    public function processRoute($route)
    {
        $route = trim($route, '/');
        switch ($route) {
            case 'commerce/action': {
                if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && is_string($_POST['action']) && preg_match('/^[a-z]+\/[a-z]+$/', $_POST['action'])) {
                    try {
                        $response = $this->runAction($_POST['action'], isset($_POST['data']) ? $_POST['data'] : []);
                        echo $this->prepareResponse($response);
                        exit;
                    } catch (\Exception $e) {
                        $this->modx->logEvent(0, 3, $e->getMessage());
                    } catch (\TypeError $e) {
                        $this->modx->logEvent(0, 3, $e->getMessage());
                    }
                }

                return;
            }

            case 'commerce/cart/contents': {
                $response = [
                    'status' => 'failed',
                ];

                $shouldResponse = true;

                if (!empty($_POST['order_completed'])) {
                    $shouldResponse = !empty($_SESSION['commerce_order_completed']);

                    if ($shouldResponse) {
                        unset($_SESSION['commerce_order_completed']);
                    }
                }

                if ($shouldResponse) {
                    $response['status'] = 'success';
                }

                echo $this->prepareResponse($response);
                exit;
            }

            case 'commerce/data/update': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $this->loadProcessor()->updateRawData($_POST);

                    $result = [
                        'status' => 'success',
                        'markup' => [],
                    ];

                    echo $this->prepareResponse($result);
                    exit;
                }

                return;
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
                        echo $this->prepareResponse($response);
                        exit;
                    }

                    $response['status'] = 'success';
                    echo $this->prepareResponse($response);
                    exit;
                }

                return;
            }

            case 'commerce/payorder': {
                if (!empty($_GET['hash']) && is_scalar($_GET['hash']) && $this->loadProcessor()->payOrderByHash($_GET['hash'])) {
                    exit;
                }

                if ($docid = $this->getSetting('payment_failed_page_id')) {
                    $this->modx->sendRedirect($this->modx->makeUrl($docid));
                };

                return;
            }

            case 'commerce/module/action': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $manager = new \Commerce\Module\Manager($this->modx, []);

                    $route = filter_input(INPUT_POST, 'type', FILTER_VALIDATE_REGEXP, ['options' => [
                        'regexp'  => '/^[a-z]+(:?\/[a-z-]+)*$/',
                        'default' => '',
                    ]]);

                    echo $manager->processRoute($route);
                    exit;
                }

                return;
            }
        }

        if (preg_match('/commerce\/([a-z-_]+?)\/(payment-[a-z-]+?)$/', $route, $parts)) {
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

        $instance = 'products';

        if (!empty($data['cart']['instance']) && is_string($data['cart']['instance'])) {
            $instance = $data['cart']['instance'];
        } elseif (!empty($data['instance']) && is_string($data['instance'])) {
            $instance = $data['instance'];
        } elseif (!empty($data['cart']['hash']) && is_string($data['cart']['hash'])) {
            $hashInstance = ci()->carts->getInstanceByHash($data['cart']['hash']);

            if (!is_null($hashInstance)) {
                $instance = $hashInstance;
            }
        }

        $response['instance'] = $instance;
        $cart = ci()->carts->getCart($instance);

        if (!is_null($cart)) {
            switch ($action) {
                case 'cart/add': {
                    $row = $cart->add($data);

                    if ($row !== false) {
                        $response['status'] = 'success';
                        $response['row']    = $row;
                    }

                    break;
                }

                case 'cart/addmultiple': {
                    if (isset($data['batch']) && is_array($data['batch'])) {
                        $response['rows'] = $cart->addMultiple($data['batch']);

                        $successful = array_filter($response['rows'], function($res) {
                            return $res !== false;
                        });
                        $count = count($successful);

                        if ($count == count($response['rows'])) {
                            $response['status'] = 'success';
                        } else if ($count) {
                            $response['status'] = 'ambiguous';
                        }
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

                case 'cart/clean': {
                    if ($cart->clean()) {
                        $response['status'] = 'success';
                    }

                    break;
                }
            }
        }

        return $response;
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
        $hash = md5(serialize($params));
        $key = 'commerce.' . $hash;
        $cache = ci()->cache;
        if (!$cache->has($key)) {
            $cache->save($key, $params);
        }

        return $hash;
    }

    public function restoreParams($hash)
    {
        $key = 'commerce.' . $hash;
        $cache = ci()->cache;
        try {
            return $cache->get($key);
        } catch (\Exception $e)
        {
            return false;
        }
    }

    protected function prepareResponse($response)
    {
        if (!empty($_POST['hashes']) && is_array($_POST['hashes'])) {
            if (!isset($response['markup'])) {
                $response['markup'] = [];
            }

            if (!empty($_POST['hashes']['carts'])) {
                $response['markup']['carts'] = $this->getCartsMarkup($_POST['hashes']['carts']);
            }

            if (!empty($_POST['hashes']['form'])) {
                $markup = $this->getFormMarkup($_POST['hashes']['form']);

                if (!empty($markup)) {
                    $response['markup']['form'] = $markup;
                }
            }
        }

        $this->modx->invokeEvent('OnCommerceAjaxResponse', [
            'response' => &$response,
        ]);

        return json_encode($response);
    }

    public function generateRandomString($length = 32)
    {
        $result = '';

        if (function_exists('random_bytes')) {
            $result = bin2hex(random_bytes($length * 0.5));
        } else if (function_exists('openssl_random_pseudo_bytes')) {
            $result = bin2hex(openssl_random_pseudo_bytes($length * 0.5));
        } else {
            $result = md5(rand() . rand() . rand());
        }

        return substr($result, 0, $length);
    }

    public function getProductPlaceholders($product_id, $lists = [])
    {
        $placeholders = [];

        foreach ($lists as $instance => $items) {
            foreach ($items as $item) {
                if (!empty($item['id']) && $item['id'] == $product_id) {
                    $placeholders = array_merge($placeholders, [
                        $instance . '_contains' => 1,
                        $instance . '_active'   => ' active',
                        $instance . '_count'    => $item['count'],
                    ]);

                    break;
                }
            }
        }

        return $placeholders;
    }

    public function populateProductPagePlaceholders()
    {
        $lists = [];

        foreach (['products', 'wishlist', 'comparison'] as $instance) {
            $lists[$instance] = ci()->carts->getCart($instance)->getItems();
        }

        $this->modx->toPlaceholders($this->getProductPlaceholders($this->modx->documentIdentifier, $lists));
    }

    public function populateProductListPlaceholders($data, $modx, $DL, $eDL)
    {
        $lists = $eDL->getStore('__commerce_lists');

        if ($lists == null) {
            $lists = [];

            foreach (['products', 'wishlist', 'comparison'] as $instance) {
                $lists[$instance] = ci()->carts->getCart($instance)->getItems();
            }

            $eDL->setStore('__commerce_lists', $lists);
        }

        $data = array_merge($data, $this->getProductPlaceholders($data['id'], $lists));
        return $data;
    }

    public function populateClientScripts()
    {
        $params = [
            'currency' => array_intersect_key($this->currency->getCurrency(), array_flip(['left', 'right', 'decimals', 'decsep', 'thsep'])),
            'path'     => $this->modx->getConfig('base_url'),
        ];

        if ($this->isBLangEnabled()) {
            $params['path'] .= $this->modx->getConfig('_root');
        }

        if ($this->getSetting('cart_page_id') == $this->modx->documentIdentifier) {
            $params['isCartPage'] = true;
        }

        return '<script src="' . MODX_BASE_URL . 'assets/plugins/commerce/js/commerce.js?' . self::VERSION . '"></script><script>Commerce.params = ' . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) . ';</script>';
    }

    public function isBLangEnabled()
    {
        return !empty($this->modx->snippetCache['bLang']);
    }

    public function isEvoBabelEnabled()
    {
        return !empty($_SESSION['perevod']);
    }
}
