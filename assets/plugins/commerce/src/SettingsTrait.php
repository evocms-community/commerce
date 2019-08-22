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

    public function getSettings()
    {
        return $this->settings;
    }

    protected function setSettings($settings)
    {
        $this->settings = array_merge($this->settings, $settings);
    }
}
