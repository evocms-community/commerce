<?php

class CartDocLister extends CustomLangDocLister
{
    protected $productsCount = 0;
    protected $rowsCount     = 0;
    protected $priceTotal    = 0;
    protected $extPrepare;

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
        setlocale(LC_NUMERIC, 'C');
    }

    public function prepareCartOuter($data, $modx, $DL, $e)
    {
        $data['placeholders'] = array_merge($data['placeholders'], [
            'hash'        => $this->getCFGDef('hash'),
            'items_price' => $this->priceTotal,
            'subtotals'   => $this->renderSubtotals(),
            'total'       => $this->priceTotal,
            'count'       => $this->productsCount,
            'rows_count'  => $this->rowsCount,
            'settings'    => $this->modx->commerce->getSettings(),
        ]);

        return $data;
    }

    protected function prepareSubtotalsRow($data)
    {
        if ($this->extPrepare) {
            $data = $this->extPrepare->init($this, [
                'data'      => $data,
                'nameParam' => 'prepareSubtotalsRow',
            ]);
        }

        return $data;
    }

    protected function prepareSubtotalsWrap($data)
    {
        if ($this->extPrepare) {
            $data = $this->extPrepare->init($this, [
                'data'      => $data,
                'nameParam' => 'prepareSubtotalsWrap',
                'return'    => 'placeholders',
            ]);
        }

        return $data;
    }

    protected function renderSubtotals()
    {
        $DLTemplate = ci()->tpl;
        $this->extPrepare = $this->getExtender('prepare');
        $tpl = $this->getCFGDef('subtotalsRowTpl');
        $result = '';
        $rows = [];

        $this->getCFGDef('cart')->getSubtotals($rows, $this->priceTotal);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $row = $this->prepareSubtotalsRow($row);

                if ($row !== false) {
                    $result .= $DLTemplate->parseChunk($tpl, $row);
                }
            }
        }

        $params = $this->prepareSubtotalsWrap([
            'docs'         => $rows,
            'placeholders' => [
                'wrap' => $result,
            ],
        ]);

        if ($params !== false && !empty($result)) {
            $result = $DLTemplate->parseChunk($this->getCFGDef('subtotalsTpl'), $params);
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
                $doc['original_title'] = $doc['pagetitle'];
                $doc['pagetitle'] = $doc['name'];
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
        if (!empty($this->getCFGDef('defaultOptionsRender', 1))) {
            $optionsTpl = $this->getCFGDef('optionsTpl');

            foreach ($this->_docs as $id => $item) {
                $options = '';

                if (isset($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $key => $option) {
                        $options .= ci()->tpl->parseChunk($optionsTpl, [
                            'key'    => htmlentities($key),
                            'option' => htmlentities(is_scalar($option) ? $option : json_encode($option, JSON_UNESCAPED_UNICODE)),
                        ]);
                    }
                }

                $item['_options'] = $item['options'];
                $item['options']  = $options;
                $this->_docs[$id] = $item;
            }
        }

        if (!$this->getCFGDef('noneWrapOuter', '1') && !count($this->_docs)) {
            $this->ownerTPL = $this->getCFGDef('noneTPL');
            $this->config->setConfig(['noneTPL' => '']);
        }

        return parent::_render($tpl);
    }
}
