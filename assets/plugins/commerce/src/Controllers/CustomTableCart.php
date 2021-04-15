<?php

use Commerce\Controllers\Traits;

class CustomTableCartDocLister extends CustomTableLangDocLister
{
    use Traits\CommonCartTrait, Traits\PrepareTrait;

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        $cfg = $this->initializePrepare($cfg);
        $cfg = $this->initializeCommonCart($cfg);

        parent::__construct($modx, $cfg, $startTime);
        setlocale(LC_NUMERIC, 'C');
    }

    public function getDocs($tvlist = '')
    {
        $this->_docs = $this->mixinCartItems(parent::getDocs($tvList));

        return $this->_docs;
    }

    protected function getDocList()
    {
        $cartItems = $this->getCFGDef('cart')->getItems();
        $join = [];

        foreach ($cartItems as $row => $item) {
            $join[] = "SELECT " . $item['id'] . " AS id, '$row' AS `hash`";
        }

        if (!empty($join)) {
            $this->setFiltersJoin('JOIN (' . implode(' UNION ', $join) . ') hashes ON c.id = hashes.id');

            $this->config->setConfig([
                'selectFields' => $this->getCFGDef('selectFields', 'c.*') . ', c.id AS docid, hashes.hash AS id',
                'groupBy'      => 'hashes.hash, hashes.id',
            ]);
        }

        return parent::getDocList();
    }

    protected function SortOrderSQL($sortName, $orderDef = 'DESC')
    {
        $cartItems = $this->getCFGDef('cart')->getItems();
        $hashes = array_keys($cartItems);
        $out = [
            'orderBy' => "FIND_IN_SET(hashes.hash, '" . implode(',', $hashes) . "')",
        ];
        $this->config->setConfig($out);

        return "ORDER BY " . $out['orderBy'];
    }

    public function _render($tpl = '')
    {
        $this->_docs = $this->mixinProductOptionsRender($this->_docs);
        $this->handleEmptyResult($this->_docs);

        return parent::_render($tpl);
    }

    public function getJSON($data, $fields, $array = array())
    {
        $result = parent::getJSON($data, $fields, $array);

        if ($this->getCFGDef('JSONformat') == 'new') {
            $result = $this->mixinCartAdditionals($result);
        }

        return $result;
    }
}
