<?php

class WishlistDocLister extends CartDocLister
{
    protected $priceField;

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        $this->priceField = $modx->commerce->getSetting('price_field', 'price');
        $cfg['tvList']    = $this->priceField . (!empty($cfg['tvList']) ? ',' . $cfg['tvList'] : '');
        $this->priceField = (isset($cfg['tvPrefix']) ? $cfg['tvPrefix'] : 'tv.') . $this->priceField;

        $cfg = $this->initializePrepare($cfg);

        $cfg['prepare'][] = [$this, 'prepareRow'];

        parent::__construct($modx, $cfg, $startTime);
    }

    public function prepareRow($data, $modx, $DL, $eDL)
    {
        $data[$this->priceField] = ci()->currency->format($data[$this->priceField]);
        return $data;
    }
}
