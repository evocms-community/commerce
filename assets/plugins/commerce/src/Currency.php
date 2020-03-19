<?php

namespace Commerce;

class Currency
{
    protected $table = 'commerce_currency';
    protected $currencies;
    protected $langCurrencies;
    protected $defaultCurrency;
    protected $activeCurrency;
    protected $key = 'commerce.currency';

    public function __construct()
    {
        if (isset($_SESSION[$this->key])) {
            $this->activeCurrency = $_SESSION[$this->key];
        }

        $this->table = ci()->modx->getFullTablename($this->table);
        $this->getCurrencies();

        if (is_null($this->activeCurrency)) {
            $this->activeCurrency = $this->defaultCurrency;
        }
    }

    public function getCurrencies()
    {
        if (is_null($this->currencies)) {
            $this->currencies = ci()->cache->getOrCreate('currencies', function() {
                $db = ci()->db;

                $result = [];
                $query  = $db->select('*', $this->table);

                while ($row = $db->getRow($query)) {
                    $result[$row['code']] = $row;

                }

                return $result;
            });

            foreach ($this->currencies as $row) {
                if (!empty($row['default'])) {
                    $this->defaultCurrency = $row['code'];
                }
            }

            if (empty($this->defaultCurrency)) {
                $row = reset($this->currencies);
                $this->defaultCurrency = $row['code'];
            }
        }

        return $this->currencies;
    }

    public function getLangCurrencyCode($lang)
    {
        $this->getCurrencies();

        if (is_null($this->langCurrencies)) {
            $this->langCurrencies = ci()->cache->getOrCreate('lang_currencies', function() {
                $result = [];

                foreach ($this->currencies as $currency) {
                    if (empty($currency['lang'])) {
                        continue;
                    }

                    $langs = array_map('trim', preg_split('/(;|,|\|\|| )/', $currency['lang']));

                    foreach ($langs as $lang) {
                        $result[$lang] = $currency['code'];
                    }
                }

                return $result;
            });
        }

        if (isset($this->langCurrencies[$lang])) {
            return $this->langCurrencies[$lang];
        }

        return $this->defaultCurrency;
    }

    public function getDefaultCurrencyCode()
    {
        if (is_null($this->defaultCurrency)) {
            $this->getCurrencies();
        }

        return $this->defaultCurrency;
    }

    public function getCurrencyCode()
    {
        if (is_null($this->activeCurrency)) {
            $this->getCurrencies();
        }

        return $this->activeCurrency;
    }

    public function getCurrency($code = null)
    {
        if (is_null($code)) {
            $code = $this->activeCurrency;
        }

        $currencies = $this->getCurrencies();

        if (!isset($currencies[$code])) {
            throw new \Exception('Currency with code "' . print_r($code, true) . '" not registered!');
        }

        return $currencies[$code];
    }

    public function setCurrency($code)
    {
        if (!is_scalar($code)) {
            throw new \Exception('Code "' . print_r($code, true) . '" is not scalar!');
        }

        $currencies  = $this->getCurrencies();
        $isNotActive = $this->activeCurrency != $code;

        if ($isNotActive) {
            ci()->modx->invokeEvent('OnBeforeCurrencyChange', [
                'old' => $this->activeCurrency,
                'new' => &$code,
            ]);
        }

        if (!isset($currencies[$code])) {
            throw new \Exception('Currency with code "' . print_r($code, true) . '" not registered!');
        }

        $this->activeCurrency = $code;
        $_SESSION[$this->key] = $code;

        if ($isNotActive) {
            ci()->carts->changeCurrency($code);
        }
    }

    public function format($amount, $code = null)
    {
        if (!is_scalar($amount)) {
            return $amount;
        }

        $cleaned = trim(str_replace(',', '.', $amount));

        if (!is_numeric($cleaned)) {
            return $amount;
        }

        $currency = $this->getCurrency($code);
        return $currency['left'] . number_format($cleaned, $currency['decimals'], $currency['decsep'], $currency['thsep']) . $currency['right'];
    }

    public function convert($amount, $from, $to)
    {
        if (!is_scalar($amount)) {
            return $amount;
        }

        $cleaned = trim(str_replace(',', '.', $amount));

        if (!is_numeric($cleaned)) {
            return $amount;
        }

        $currencies = $this->getCurrencies();

        if (!isset($currencies[$from]) || !isset($currencies[$to])) {
            throw new \Exception('Currency not defined');
        }

        return $cleaned * $currencies[$to]['value'] / $currencies[$from]['value'];
    }

    public function convertToDefault($amount, $from = null)
    {
        if (is_null($from)) {
            $from = $this->activeCurrency;
        }

        if ($this->defaultCurrency == $from) {
            return $amount;
        }

        return $this->convert($amount, $from, $this->defaultCurrency);
    }

    public function convertToActive($amount, $from = null)
    {
        if (is_null($from)) {
            $from = $this->defaultCurrency;
        }

        if ($this->activeCurrency == $from) {
            return $amount;
        }

        return $this->convert($amount, $from, $this->activeCurrency);
    }

    public function convertFromDefault($amount, $to = null)
    {
        if (is_null($to)) {
            $to = $this->activeCurrency;
        }

        return $this->convert($amount, $this->defaultCurrency, $to);
    }

    public function formatWithDefault($amount, $from)
    {
        return $this->format($this->convertToDefault($amount, $from), $this->defaultCurrency);
    }

    public function formatWithActive($amount, $from = null)
    {
        return $this->format($this->convertToActive($amount, $from), $this->activeCurrency);
    }
}
