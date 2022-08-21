<?php

namespace Commerce\Module\Controllers;

use Commerce\Module\Renderer;

class Controller implements \Commerce\Module\Interfaces\Controller
{
    protected $modx;
    protected $module;
    protected $view;
    protected $icon = 'fa fa-cog';

    public function __construct($modx, $module)
    {
        $this->modx = $modx;
        $this->module = $module;
        $this->view = new Renderer($modx, $module);
    }

    public function registerRoutes()
    {
        return [
            'index' => 'index',
        ];
    }

    public function index()
    {
        return '';
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getModule()
    {
        return $this->module;
    }
}
