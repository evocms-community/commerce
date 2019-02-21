<?php

namespace Commerce;

class Lexicon extends \Helpers\Lexicon
{
    public function loadLang($name = 'core', $lang = '', $langDir = '')
    {
        parent::loadLang($name, $lang, $langDir);
        setlocale(LC_NUMERIC, 'C');
        return $this->_lang;
    }
}
