<?php

namespace Commerce\Module;

class Manager
{
    use \Commerce\SettingsTrait;

    private $modx;
    protected $flashKey = 'commerce.module.messages';

    public function __construct($modx, array $params = [])
    {
        $this->modx = $modx;
        $this->setSettings($params);
        
ini_set('display_errors', 1);
        $_SESSION[$this->flashKey] = [];
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
                        $message = ['success' => $this->lang['module.order_updated']];
                    } else {
                        $message = ['error' => $this->lang['module.error.order_not_updated']];
                    }

                    $target = isset($stay) || $result === false ? 'orders/edit&order_id = ' . $id : 'orders';
                    $this->sendRedirect($target, $message);
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

    public function sendRedirect($route = '', array $messages = [])
    {
        if (!empty($messages)) {
            $_SESSION[$this->flashKey] = array_merge($_SESSION[$this->flashKey], $messages);
        }

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
