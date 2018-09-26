<?php

namespace Commerce\Module;

class FlashMessages
{
    private $key = 'commerce.module.messages';

    public function __construct()
    {
        if (empty($_SESSION[$this->key])) {
            $_SESSION[$this->key] = [];
        } else {
            register_shutdown_function(function() {
                $this->clean();
            });
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_POST as $key => $value) {
                $this->set('form_' . $key, $value);
            }
        }
    }

    public function has($name)
    {
        return isset($_SESSION[$this->key][$name]);
    }

    public function get($name)
    {
        if (isset($_SESSION[$this->key][$name])) {
            $result = $_SESSION[$this->key][$name];
            unset($_SESSION[$this->key][$name]);
            return $result;
        }

        return null;
    }

    public function set($name, $value)
    {
        $_SESSION[$this->key][$name] = $value;
    }

    public function setMultiple(array $rows) {
        foreach ($rows as $name => $value) {
            $_SESSION[$this->key][$name] = $value;
        }
    }

    public function clean()
    {
        if (isset($_SESSION[$this->key])) {
            unset($_SESSION[$this->key]);
        }
    }
}
