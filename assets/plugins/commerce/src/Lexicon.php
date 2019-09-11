<?php

namespace Commerce;

class Lexicon extends \Helpers\Lexicon
{
    public function loadLang($name = 'core', $lang = '', $langDir = '')
    {
        parent::loadLang($name, $lang, $langDir);
        setlocale(LC_NUMERIC, 'C');

        if (is_callable([$this, 'getLexicon'])) {
            return $this->getLexicon();
        }

        return $this->_lang;
    }
}
