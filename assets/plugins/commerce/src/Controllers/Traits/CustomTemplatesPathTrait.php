<?php

namespace Commerce\Controllers\Traits;

trait CustomTemplatesPathTrait
{
    public function setCustomLanguage()
    {
        $customLang = $this->getCFGDef('customLang');

        if (!empty($customLang)) {
            $this->getCustomLang($customLang);
        }
    }

    public function initializeCustomTemplatesPath($cfg)
    {
        if (!isset($cfg['templatePath'])) {
            $templatePath = trim(ci()->commerce->getSetting('templates_path', ''), '/ ');

            if (!empty($templatePath)) {
                $cfg['templatePath'] = $templatePath . '/';
            } else {
                $cfg['templatePath'] = 'assets/plugins/commerce/templates/front/';
            }

            if (!isset($cfg['templateExtension'])) {
                $cfg['templateExtension'] = 'tpl';
            }
        }

        return $cfg;
    }
}
