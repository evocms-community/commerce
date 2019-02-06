<?php

namespace Commerce\Carts;

class CookiesCartStore implements \Commerce\Interfaces\CartStore
{
    protected $instance;
    private $key;

    public function __construct()
    {
        $this->key = md5(preg_replace('/^(https?:)?\/\/([^\/]+).*/', '$2', ci()->modx->getConfig('site_url')));
    }

    public function load($instance = 'cart')
    {
        $this->instance = $instance . '_' . $this->key;
        $items = [];

        if (isset($_COOKIE[$this->instance])) {
            $items = json_decode(base64_decode($_COOKIE[$this->instance]), true);
        }

        return $items;
    }

    public function save(array $items)
    {
        global $session_cookie_domain;
        $cookieDomain = !empty($session_cookie_domain) ? $session_cookie_domain : '';

        $secure  = ci()->modx->getConfig('server_protocol') == 'https';
        $ids = [];

        foreach ($items as $row => $item) {
            $ids[$row] = $item['id'];
        }

        $encoded = base64_encode(json_encode($ids));

        unset($_COOKIE[$this->instance]);
        //setcookie($this->instance, '', time() - 3600, MODX_BASE_URL, $cookieDomain);
        setcookie($this->instance, $encoded, time() + 60*60*24*30, MODX_BASE_URL, $cookieDomain, $secure, true);
        $_COOKIE[$this->instance] = $encoded;
    }
}
