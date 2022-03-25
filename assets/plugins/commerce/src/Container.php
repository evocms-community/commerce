<?php

namespace Commerce;

class Container
{
    private $items = [];
    private $fixed = [];

    static protected $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    public function has($name)
    {
        return isset($this->items[$name]);
    }

    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \Exception('Service "' . print_r($name, true) . '" not found!');
        }

        if (!isset($this->fixed[$name])) {
            $this->fixed[$name] = call_user_func($this->items[$name], $this);
        }

        return $this->fixed[$name];
    }

    public function set($name, $callback)
    {
        if ($this->has($name)) {
            throw new \Exception('Service "' . print_r($name, true) . '" already set!');
        }

        $this->items[$name] = $callback;
    }

    public function __get($name)
    {
        return $this->get($name);
    }
}
