<?php

namespace Commerce\Carts;

class CookiesCartStore implements \Commerce\Interfaces\CartStore
{
    protected $instance;
    protected $domain;
    private $key;

    public function __construct()
    {
        $this->domain = preg_replace('/^(https?:)?\/\/([^\/]+).*/', '$2', ci()->modx->getConfig('site_url'));
        $this->key    = md5($this->domain);
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
        $secure  = ci()->modx->getConfig('server_protocol') == 'http';
        $encoded = base64_encode(json_encode($items));

        unset($_COOKIE[$this->instance]);
        setcookie($this->instance, '', time() - 3600, '/', $this->domain);
        setcookie($this->instance, $encoded, time() + 60*60*24*30, '/', $this->domain, $secure, true);
        $_COOKIE[$this->instance] = $encoded;
    }
}
