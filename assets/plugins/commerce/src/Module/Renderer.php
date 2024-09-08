<?php

namespace Commerce\Module;

class Renderer
{
    use \Commerce\SettingsTrait;

    private $modx;
    private $module;

    protected $block;
    protected $blocks = [];
    protected $extensionLevels = [];
    protected $templateLevels = [];

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
        $this->templateLevels[] = $template;
        $fullTemplate = rtrim($this->getSetting('path', COMMERCE_PATH . 'templates/module'), '/') . '/' . $template;

        if (!is_readable($fullTemplate)) {
            throw new \Exception('Template "' . $fullTemplate . '" is not readable!');
        }

        $modx  = $this->modx;
        $lang  = $this->lang;
        $flash = $this->getSetting('flash', []);
        $module = $this->module;
        $modx_lang_attribute = $modx->getLocale();
        $lastInstallTime = 0;
        $modx_manager_charset = $modx->getConfig('modx_charset', 'utf-8');
        extract($data);
        setlocale(LC_NUMERIC, 'C');

        ob_start();
        include $fullTemplate;

        if (!empty($this->extensionLevels)) {
            $parent = end($this->extensionLevels);

            if (key($this->extensionLevels) == $template) {
                ob_end_clean();
                array_pop($this->extensionLevels);
                return $this->render($parent, compact(array_keys($data)));
            }
        }

        array_pop($this->templateLevels);
        return ob_get_clean();
    }

    public function extend($parent)
    {
        $this->extensionLevels[end($this->templateLevels)] = $parent;
    }

    public function block($name, $default = null)
    {
        if (empty($this->extensionLevels)) {
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
