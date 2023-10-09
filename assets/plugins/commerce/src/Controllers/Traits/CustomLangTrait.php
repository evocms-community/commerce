<?php

namespace Commerce\Controllers\Traits;

trait CustomLangTrait
{

    protected $_customLang;

    protected function handleCustomLang()
    {
        if (empty($this->lexicon)) {
            $customLang = $this->getCFGDef('customLang');
            if (!empty($customLang)) {
                $this->getCustomLang($customLang);
            }
        }
    }

    public function getCustomLang($lang = '')
    {
        if (!empty($this->lexicon)) {
            $this->_customLang = [];

            if (!is_array($lang)) {
                $lang = array_map('trim', explode(',', $lang ?? ''));
            }

            foreach ($lang as $l) {
                $this->_customLang += parent::getCustomLang($l);
            }
        } else {
            if (empty($lang)) {
                return [];
            }

            $dir = $this->getCFGDef('lang', $this->modx->config['manager_language']);

            if (!is_array($lang)) {
                $lang = explode(',', $lang);
            }

            $userLangDir = $this->getCFGDef('langDir', '');

            if (!empty($userLangDir)) {
                $userLangDir = MODX_BASE_PATH . trim($userLangDir, '/') . '/';
            }

            $files = [];

            foreach ($lang as $item) {
                $files[] = COMMERCE_PATH . "lang/$dir/$item.inc.php";
                $files[] = MODX_BASE_PATH . 'assets/' . $item;

                if (!empty($userLangDir)) {
                    $files[] = $userLangDir . "$item.inc.php";
                    $files[] = $userLangDir . "$dir/$item.inc.php";
                }
            }

            foreach ($files as $file) {
                if (is_readable($file) && is_file($file)) {
                    $tmp = include $file;
                    $this->_customLang = is_array($tmp) ? array_merge($this->_customLang, $tmp) : [];
                }
            }
        }

        setlocale(LC_NUMERIC, 'C');

        return $this->_customLang;
    }
}
