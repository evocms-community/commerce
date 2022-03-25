<?php

namespace Commerce;

use Helpers\FS;

class Cache
{
    static protected $self;

    protected $path = 'assets/cache/commerce';
    protected $salt = 'i_LjFSmtLz9i5zQ_KWCB';

    /** @var \Helpers\FS */
    protected $filesystem = null;

    public static function getInstance()
    {
        if (is_null(self::$self)) {
            self::$self = new self();
            self::$self->initialize();
        }

        return self::$self;
    }

    public function initialize()
    {
        if (is_null($this->filesystem)) {
            $this->filesystem = FS::getInstance();
        }
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}

    /**
     * @param $name
     * @param callable $callback
     * @param  array  $options
     * @return false|mixed
     */
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

    /**
     * @param $name
     * @return string
     */
    protected function generateKey($name)
    {
        $path = md5($name . $this->salt);
        
        return substr($path, 0, 1) . '/' . substr($path, 1, 2) . '/' . $path . '.cache';
    }

    /**
     * @param $key
     * @return string
     */
    protected function getKeyPath($key)
    {
        return MODX_BASE_PATH . trim($this->path, '/ ') . '/' . $key;
    }

    /**
     * @param $name
     * @param  false  $isPath
     * @return bool
     */
    public function has($name, $isPath = false)
    {
        if (!$isPath) {
            $name = $this->generateKey($name);
            $name = $this->getKeyPath($name);
        }

        if ($this->filesystem->checkFile($name)) {
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

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
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

    /**
     * @param $name
     * @param $content
     * @param  array  $options
     */
    public function save($name, $content, $options = [])
    {
        $key  = $this->generateKey($name);
        $path = $this->path . '/' . $key;

        $this->filesystem->makeDir(preg_replace('/[a-z0-9.]*$/', '', $path));

        if (isset($options['seconds'])) {
            $time = time() + $options['seconds'];
        } else {
            $time = 0;
        }

        file_put_contents(MODX_BASE_PATH . $path, sprintf("%'.010d", $time) . serialize($content));
    }

    /**
     * @param $name
     */
    public function forget($name)
    {
        $this->filesystem->unlink(MODX_BASE_PATH . trim($this->path, '/ ') . '/' . $this->generateKey($name));
    }

    public function clean()
    {
        $this->filesystem->rmDir($this->path);
    }
}
