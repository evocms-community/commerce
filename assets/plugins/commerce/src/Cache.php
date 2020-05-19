<?php

namespace Commerce;

class Cache
{
    static protected $self;

    protected $path = 'assets/cache/commerce';
    protected $salt = 'i_LjFSmtLz9i5zQ_KWCB';

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

    public function getOrCreate($name, $callback, $options = [])
    {
        try {
            $content = $this->get($name);
        } catch (\Exception $e) {
            $content = call_user_func($callback);
            $this->save($name, $content, $options);
        }

        return $content;
    }

    protected function generateKey($name)
    {
        $path = md5($name . $this->salt);
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

        if (is_readable($name)) {
            $handle = fopen($name, 'r');
            $time = fread($handle, 10);
            fclose($handle);

            if ((int) $time == 0 || $time > time()) {
                return true;
            }

            unlink($name);
        }

        return false;
    }

    public function get($name)
    {
        $key  = $this->generateKey($name);
        $path = $this->getKeyPath($key);

        if ($this->has($path, true)) {
            $contents = file_get_contents($path);
            return unserialize(substr($contents, 10));
        }

        throw new \Exception('Key "' . print_r($name, true) . '" not found in cache!');
    }

    public function save($name, $content, $options = [])
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

        if (isset($options['seconds'])) {
            $time = time() + $options['seconds'];
        } else {
            $time = 0;
        }

        file_put_contents($path . '/' . $filename, sprintf("%'.010d", $time) . serialize($content));
    }

    public function forget($name)
    {
        $path = MODX_BASE_PATH . trim($this->path, '/ ') . '/' . $this->generateKey($name);

        if (file_exists($path)) {
            unlink($path);
        }
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
