<?php

namespace Commerce;

trait SettingsTrait
{
    protected $settings = [];

    public function getSetting($key, $default = null)
    {
        if (!isset($this->settings[$key])) {
            return $default;
        }

        return $this->settings[$key];
    }

    protected function setSettings($settings)
    {
        $this->settings = array_merge($this->settings, $settings);
    }
}
