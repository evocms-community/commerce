<?php

namespace Commerce\Module;

class Manager
{
    use \Commerce\SettingsTrait;

    private $modx;

    public $flash;

    public function __construct($modx, array $params = [])
    {
        $this->modx = $modx;
        $this->setSettings($params);
ini_set('display_errors', 1);
        $this->flash = new FlashMessages;
    }

    public function processRoute($route)
    {
        $parts = explode('/', $route);
        $controller = !empty($parts[0]) ? array_shift($parts) : 'orders';

        if (!in_array($controller, ['orders', 'statuses', 'currency'])) {
            $this->modx->sendRedirect('index.php?a=106');
        }

        $classname = '\\Commerce\\Module\\Controllers\\' . ucfirst($controller) . 'Controller';
        $controller = new $classname($this->modx, $this);

        switch ($route) {
            case 'statuses/edit': {
                return;
            }

            case 'statuses': {
                return $controller->showList();
            }

            case 'currency/edit': {
                return;
            }

            case 'currency': {
                return $controller->showList();
            }

            case 'orders/edit': {
                return $controller->show();
            }

            case 'orders/change-status': {
                return $controller->changeStatus();
            }

            case 'orders':
            case '': {
                return $controller->showList();
            }
        }

        $this->flash->set('error', $this->lang['module.unknown_route']);
        $this->sendRedirect('orders');
    }

    public function sendRedirect($route = '')
    {
        $this->modx->sendRedirect($this->makeUrl($route));
        exit;
    }

    public function sendRedirectBack($fallback = '')
    {
        $url = $fallback;

        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = htmlspecialchars_decode($_SERVER['HTTP_REFERER']);
        }

        $this->modx->sendRedirect($url);
        exit;
    }

    public function makeUrl($route, $query = '')
    {
        $url = $this->getSetting('module_url');

        if (!empty($route)) {
            $url .= '&route=' . $route;
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
}
