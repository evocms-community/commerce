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
                break;
            }

            case 'statuses': {
                $controller->showList();
                break;
            }

            case 'currency/edit': {
                break;
            }

            case 'currency': {
                $controller->showList();
                break;
            }

            case 'orders/edit': {
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    if ($result = $controller->save()) {
                        $this->flash->set('success', $this->lang['module.order_updated']);
                    } else {
                        $this->flash->set('error', $this->lang['module.error.order_not_updated']);
                    }

                    $target = isset($stay) || $result === false ? 'orders/edit&order_id = ' . $id : 'orders';
                    $this->sendRedirect($target);
                } else {
                    echo $controller->show();
                }

                break;
            }

            case 'orders':
            default: {
                echo $controller->showList();
                break;
            }
        }
    }

    public function sendRedirect($route = '')
    {
        $this->modx->sendRedirect($this->makeUrl($route));
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
}
