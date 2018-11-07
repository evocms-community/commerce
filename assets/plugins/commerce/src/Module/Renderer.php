<?php

namespace Commerce\Module;

class Renderer
{
    use \Commerce\SettingsTrait;

    private $modx;
    private $module;

    protected $block;
    protected $blocks = [];
    protected $levels = [];

    public function __construct($modx, $module, array $params = [])
    {
        $this->modx = $modx;
        $this->module = $module;
        $this->setSettings($params);
        $this->lang = $this->modx->commerce->getUserLanguage('module');
    }

    public function setPath($path)
    {
        $path = realpath(MODX_BASE_PATH . $path);

        if ($path) {
            $this->setSettings(['path' => $path]);
        }
    }

    public function setLang($lang)
    {
        $this->lang = array_merge($this->lang, $lang);
    }

    public function render($template, array $data = [])
    {
        $template = rtrim($this->getSetting('path', MODX_BASE_PATH . 'assets/plugins/commerce/templates/module'), '/') . '/' . $template;

        if (!is_readable($template)) {
            throw new \Exception('Template "' . $template . '" is not readable!');
        }

        global $_style, $_lang, $lastInstallTime;

        $modx  = $this->modx;
        $lang  = $this->lang;
        $flash = $this->getSetting('flash', []);
        $module = $this->module;
        extract($data);

        ob_start();
        include $template;

        if (!empty($this->levels)) {
            ob_end_clean();
            $template = array_shift($this->levels);
            $data = compact(array_keys($data));

            return $this->render($template, $data);
        }

        return ob_get_clean();
    }

    public function extend($parent)
    {
        $this->levels[uniqid()] = $parent;
    }

    public function block($name, $default = null)
    {
        if (empty($this->levels)) {
            return isset($this->blocks[$name]) ? $this->blocks[$name] : $default;
        }

        if ($this->block) {
            throw new \Exception('Block "' . $this->block . '" not closed!');
        }

        $this->block = $name;
        ob_start();
    }

    public function endBlock()
    {
        if (is_null($this->block)) {
            throw new \Exception('Unexpected endBlock: no blocks are opened!');
        }

        $this->blocks[$this->block] = trim(ob_get_clean());
        $this->block = null;

    }
}
