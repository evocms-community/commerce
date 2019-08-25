<?php

class WishlistDocLister extends CartDocLister
{
    protected $priceField;

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        if (isset($cfg['prepare'])) {
            if (!is_array($cfg['prepare'])) {
                $cfg['prepare'] = explode(',', $cfg['prepare']);
            } else if (is_callable($cfg['prepare'])) {
                $cfg['prepare'] = [$cfg['prepare']];
            }
        } else {
            $cfg['prepare'] = [];
        }

        $cfg['prepare'][] = [$this, 'prepareRow'];
        $this->priceField = $modx->commerce->getSetting('price_field', 'price');
        $cfg['tvList']    = $this->priceField . (!empty($cfg['tvList']) ? ',' . $cfg['tvList'] : '');
        $this->priceField = (isset($cfg['tvPrefix']) ? $cfg['tvPrefix'] : '') . $this->priceField;

        parent::__construct($modx, $cfg, $startTime);
    }

    public function prepareRow($data, $modx, $DL, $eDL)
    {
        if (isset($data['price'])) {
            $data[$this->priceField] = $modx->runSnippet('PriceFormat', ['price' => $data[$this->priceField]]);
        }

        return $data;
    }
}
