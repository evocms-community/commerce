<?php

use Commerce\Controllers\Traits;

class CustomLangDocLister extends site_contentDocLister
{
    use Traits\CustomTemplatesPathTrait;

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        $cfg = $this->initializeCustomTemplatesPath($cfg);
        $cfg['lang'] = $modx->commerce->getCurrentLang();

        parent::__construct($modx, $cfg, $startTime);

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
            $this->_customLang = parent::getCustomLang($lang);
        } else {
            if (empty($lang)) {
                return [];
            }

            $dir = $this->getCFGDef('lang', $this->modx->config['manager_language']);

            if (!is_array($lang)) {
                $lang = explode(',', $lang);
            }

            $files = [];

            foreach ($lang as $item) {
                $files[] = __DIR__ . "/../../lang/$dir/$item.inc.php";
                $files[] = MODX_BASE_PATH . 'assets/' . $item;
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
