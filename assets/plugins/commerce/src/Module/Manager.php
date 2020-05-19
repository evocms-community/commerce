<?php

namespace Commerce\Module;

class Manager
{
    use \Commerce\SettingsTrait;

    private $modx;
    private $controllers = [];
    private $controller;
    private $route = '';

    public $flash;

    public function __construct($modx, array $params = [])
    {
        $this->setSettings($params);
        $this->modx = $modx;
        $this->flash = new FlashMessages;
    }

    public function registerController($name, $controller)
    {
        if (!($controller instanceof Interfaces\Controller)) {
            throw new \Exception('Controller "' . print_r($name, true) . '" should implement Controller interface');
        }

        $this->controllers[$name] = $controller;
    }

    public function processRoute($route)
    {
        $parts = explode('/', $route);
        $controller = !empty($parts[0]) ? array_shift($parts) : 'orders';

        $route = implode('/', $parts);
        if (empty($route)) {
            $route = 'index';
        }

        $this->route = $controller . '/' . $route;

        $this->registerController('orders', new Controllers\OrdersController($this->modx, $this));
        $this->registerController('statuses', new Controllers\StatusesController($this->modx, $this));
        $this->registerController('currency', new Controllers\CurrencyController($this->modx, $this));

        $this->modx->invokeEvent('OnManagerRegisterCommerceController', [
            'module' => $this,
        ]);

        if (!isset($this->controllers[$controller])) {
            $this->modx->sendRedirect('index.php?a=106');
        }

        $this->controller = $this->controllers[$controller];
        $routes = $this->controller->registerRoutes();

        if (isset($routes[$route])) {
            return call_user_func([$this->controller, $routes[$route]]);
        }

        $this->sendRedirect('orders', ['error' => $this->lang['module.unknown_route']]);
    }

    public function sendRedirect($route = '', $messages = [])
    {
        if (!empty($messages)) {
            $this->flash->setMultiple($messages);
        }

        $this->modx->sendRedirect($this->makeUrl($route));
        exit;
    }

    public function sendRedirectWithQuery($route = '', $query = '', $messages = [])
    {
        if (!empty($messages)) {
            $this->flash->setMultiple($messages);
        }

        $this->modx->sendRedirect($this->makeUrl($route, $query));
        exit;
    }

    public function sendRedirectBack($messages = [], $fallback = '')
    {
        $url = $fallback;

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = htmlspecialchars_decode($_SERVER['HTTP_REFERER']);
        }

        if (!empty($messages)) {
            $this->flash->setMultiple($messages);
        }

        $this->modx->sendRedirect($url, $messages);
        exit;
    }

    public function makeUrl($route, $query = '')
    {
        $url = $this->getSetting('module_url');

        if (!empty($route)) {
            $url .= '&type=' . $route;
        }

        if (!empty($query)) {
            $url .= '&' . ltrim($query, '&');
        }

        return $url;
    }

    public function invokeTemplateEvent($event, array $params = [])
    {
        $result = $this->modx->invokeEvent($event, $params);

        if (is_array($result)) {
            $result = implode($result);
        }

        return $result;
    }

    public function getFormAttr($data, $attr)
    {
        if ($this->flash->has('form_' . $attr)) {
            return $this->flash->get('form_' . $attr);
        }

        if (is_array($data) && isset($data[$attr])) {
            return $data[$attr];
        }

        return null;
    }

    public function getCurrentRoute()
    {
        return $this->route;
    }

    public function getCurrentRouteName()
    {
        return trim(preg_replace('/[^\da-zA-Z]+/', '_', $this->route), '_ ');
    }

    public function getControllerIcon()
    {
        return $this->controller->getIcon();
    }
}
