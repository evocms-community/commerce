<?php

namespace Commerce\Module\Controllers;

use Commerce\Module\Renderer;

class Controller
{
    protected $modx;
    protected $module;
    protected $view;

    public function __construct($modx, $module)
    {
        $this->modx = $modx;
        $this->module = $module;
        $this->view = new Renderer($modx, $module);
    }
}
