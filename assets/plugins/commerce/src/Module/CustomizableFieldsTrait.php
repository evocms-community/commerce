<?php

namespace Commerce\Module;

trait CustomizableFieldsTrait
{
    protected function sortFields($fields)
    {
        uasort($fields, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        return $fields;
    }

    protected function processFields($fields, $args, $key = 'content')
    {
        $data = reset($args);
        $result = [];

        foreach ($fields as $name => $field) {
            if (isset($field[$key])) {
                if (is_string($field[$key]) && isset($data[$field[$key]])) {
                    $result[$name] = $data[$field[$key]];
                    continue;
                }

                if (is_callable($field[$key])) {
                    $result[$name] = call_user_func_array($field[$key], $args);
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
            $data['fields']    = isset($data['fields']) ? json_decode($data['fields'], true) : [];
            $data['index']     = $index;
            $data['iteration'] = ++$index;
            $data['cells']     = $this->processFields($fields, compact('data', 'DL', 'eDL'));
            return $data;
        };

        return $config;
    }
}
