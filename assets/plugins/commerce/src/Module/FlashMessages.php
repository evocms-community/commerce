<?php

namespace Commerce\Module;

class FlashMessages
{
    private $key = 'commerce.module.messages';
    private $previous = [];

    public function __construct()
    {
        if (!empty($_SESSION[$this->key])) {
            $this->previous = $_SESSION[$this->key];
            $this->clean();
        }

        $_SESSION[$this->key] = [];

        if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($_POST as $key => $value) {
                $this->set('form_' . $key, $value);
            }
        }
    }

    public function has($name)
    {
        return isset($this->previous[$name]);
    }

    public function get($name)
    {
        if (isset($this->previous[$name])) {
            $result = $this->previous[$name];
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
