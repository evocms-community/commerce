<?php

class CartDocLister extends CustomLangDocLister
{
    protected $productsCount = 0;
    protected $rowsCount     = 0;
    protected $priceTotal    = 0;

    public function __construct($modx, $cfg = [], $startTime = null)
    {
        if (isset($cfg['prepareWrap'])) {
            if (!is_array($cfg['prepareWrap'])) {
                $cfg['prepareWrap'] = explode(',', $cfg['prepareWrap']);
            } else if (is_callable($cfg['prepareWrap'])) {
                $cfg['prepareWrap'] = [$cfg['prepareWrap']];
            }
        } else {
            $cfg['prepareWrap'] = [];
        }

        array_unshift($cfg['prepareWrap'], [$this, 'prepareCartOuter']);
        parent::__construct($modx, $cfg, $startTime);
    }

    public function prepareCartOuter($data, $modx, $DL, $e)
    {
        $placeholders = &$data['placeholders'];

        $placeholders['hash']        = $this->getCFGDef('hash');
        $placeholders['items_price'] = $this->priceTotal;
        $placeholders['subtotals']   = $this->renderSubtotals();
        $placeholders['total']       = $this->priceTotal;
        $placeholders['count']       = $this->productsCount;
        $placeholders['rows_count']  = $this->rowsCount;
        unset($placeholders);

        return $data;
    }

    protected function renderSubtotals()
    {
        $DLTemplate = ci()->tpl;
        $tpl = $this->getCFGDef('subtotalsRowTpl');
        $result = '';
        $rows = [];

        $this->getCFGDef('cart')->getSubtotals($rows, $this->priceTotal);

        foreach ($rows as $row) {
            $result .= $DLTemplate->parseChunk($tpl, $row);
        }

        if (!empty($result)) {
            $result = $DLTemplate->parseChunk($this->getCFGDef('subtotalsTpl'), [
                'wrap' => $result,
            ]);
        }

        return $result;
    }

    public function getDocs($tvlist = '')
    {
        if ($tvlist == '') {
            $tvlist = $this->getCFGDef('tvList', '');
        }

        $this->extTV->getAllTV_Name();

        /**
         * @var $multiCategories multicategories_DL_Extender
         */
        $multiCategories = $this->getCFGDef('multiCategories', 0) ? $this->getExtender('multicategories', true) : null;
        if ($multiCategories) {
            $multiCategories->init($this);
        }

        if ($this->extPaginate = $this->getExtender('paginate')) {
            $this->extPaginate->init($this);
        }

        $this->_docs = $this->getDocList();

        if ($tvlist != '' && count($this->_docs) > 0) {
            $tv = $this->extTV->getTVList(array_column($this->_docs, 'docid'), $tvlist);

            if (!is_array($tv)) {
                $tv = array();
            }

            foreach ($this->_docs as $hash => $doc) {
                if (isset($tv[$doc['docid']])) {
                    $this->_docs[$hash] = array_merge($doc, $tv[$doc['docid']]);
                }
            }
        }

        $cartItems = $this->getCFGDef('cart')->getItems();

        foreach ($this->_docs as $hash => $doc) {
            if (isset($cartItems[$hash])) {
                $doc = array_merge($doc, $cartItems[$hash]);
            }

            $doc['hash'] = $doc['id'];
            $doc['id']   = $doc['docid'];

            if (!empty($doc['count'])) {
                $doc['price'] = (float)$doc['price'];
                $doc['count'] = (float)$doc['count'];

                $this->productsCount += $doc['count'];
                $this->rowsCount++;

                $doc['total'] = $doc['price'] * $doc['count'];
                $this->priceTotal += $doc['total'];
            }

            $this->_docs[$hash] = $doc;
        }

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
                'groupBy'      => 'hashes.hash',
            ]);
        }

        return parent::getDocList();
    }

    public function _render($tpl = '')
    {
        if (!empty($this->getCFGDef('defaultOptionsRender', 1))) {
            $DLTemplate = \DLTemplate::getInstance($this->modx);
            $optionsTpl = $this->getCFGDef('optionsTpl');

            foreach ($this->_docs as $id => $item) {
                $options = '';

                if (isset($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $key => $option) {
                        $options .= $DLTemplate->parseChunk($optionsTpl, [
                            'key'    => htmlentities($key),
                            'option' => htmlentities(is_scalar($option) ? $option : json_encode($option)),
                        ]);
                    }
                }

                $item['_options'] = $item['options'];
                $item['options']  = $options;
                $this->_docs[$id] = $item;
            }
        }

        return parent::_render($tpl);
    }
}
