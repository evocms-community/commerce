<?php

namespace Commerce;

class Lexicon extends \Helpers\Lexicon
{
    public function fromFile($name = 'core', $lang = '', $langDir = '')
    {
        parent::fromFile($name, $lang, $langDir);
        setlocale(LC_NUMERIC, 'C');

        return $this->getLexicon();
    }
}
