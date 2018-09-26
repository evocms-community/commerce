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

    protected function sortFields($fields)
    {
        uasort($fields, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        return $fields;
    }

    protected function processFields($fields, $args)
    {
        $data = reset($args);
        $result = [];

        foreach ($fields as $name => $field) {
            if (isset($field['content'])) {
                if (is_string($field['content']) && isset($data[$field['content']])) {
                    $result[$name] = $data[$field['content']];
                    continue;
                }

                if (is_callable($field['content'])) {
                    $result[$name] = call_user_func_array($field['content'], $args);
                    continue;
                }
            }

            $result[$name] = '';
        }

        return $result;
    }

    protected function injectPrepare($config, $fields)
    {
        if (isset($config['prepare'])) {
            if (!is_array($config['prepare'])) {
                $config['prepare'] = explode(',', $config['prepare']);
            }
        } else {
            $config['prepare'] = [];
        }

        $index = 0;

        $config['prepare'][] = function($data, $modx, $DL, $eDL) use ($fields, &$index) {
            $data['fields']    = json_decode($data['fields'], true);
            $data['index']     = $index;
            $data['iteration'] = ++$index;
            $data['cells']     = $this->processFields($fields, compact('data', 'DL', 'eDL'));
            return $data;
        };

        return $config;
    }
}
