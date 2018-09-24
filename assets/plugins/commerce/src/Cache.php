<?php

namespace Commerce;

class Cache
{
    static protected $self;

    protected $path = 'assets/cache/commerce';

    public static function getInstance()
    {
        if (is_null(self::$self)) {
            self::$self = new self();
        }

        return self::$self;
    }

    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    public function getOrCreate($name, $callback)
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        $content = call_user_func($callback);
        $this->save($name, $content);

        return $content;
    }

    protected function generateKey($name)
    {
        $path = md5($name);
        return substr($path, 0, 1) . '/' . substr($path, 1, 2) . '/' . $path . '.cache';
    }

    protected function getKeyPath($key)
    {
        return MODX_BASE_PATH . trim($this->path, '/ ') . '/' . $key;
    }

    public function has($name, $isPath = false)
    {
        if (!$isPath) {
            $name = $this->generateKey($name);
            $name = $this->getKeyPath($name);
        }

        return is_readable($name);
    }

    public function get($name)
    {
        $key  = $this->generateKey($name);
        $path = $this->getKeyPath($key);
        
        if ($this->has($path, true)) {
            return unserialize(file_get_contents($path));
        }

        throw new \Exception('Key "' . print_r($name, true) . '" not found in cache!');
    }

    public function save($name, $content)
    {
        $key  = $this->generateKey($name);
        $path = MODX_BASE_PATH;

        $parts = explode('/', trim($this->path, '/ ') . '/' . $key);
        $filename = array_pop($parts);

        foreach ($parts as $part) {
            $path .= '/' . $part;

            if (!file_exists($path)) {
                mkdir($path);
            }
        }

        file_put_contents($path . '/' . $filename, serialize($content));
    }

    public function clean($path = null)
    {
        if (is_null($path)) {
            $path = MODX_BASE_PATH . trim($this->path, '/ ');
        }

        $dir = opendir($path);

        while (($file = readdir($dir)) !== false) {
            if (!in_array($file, ['.', '..'])) {
                $full = $path . '/' . $file;

                if (is_dir($full)) {
                    $this->clean($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);
        rmdir($path);
    }
}
