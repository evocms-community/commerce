<?php

require_once __DIR__ . '/../../../../snippets/DocLister/core/controller/site_content.php';

class CartDocLister extends site_contentDocLister
{
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

        /**
         * @var $extJotCount jotcount_DL_Extender
         */
        $extJotCount = $this->getCFGdef('jotcount', 0) ? $this->getExtender('jotcount', true) : null;

        if ($extJotCount) {
            $extJotCount->init($this);
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

                $this->_docs[$hash]['hash'] = $doc['id'];
                $this->_docs[$hash]['id'] = $doc['docid'];
            }
        }

        foreach ($this->_docs as $hash => $doc) {
            $this->_docs[$hash]['hash'] = $doc['id'];
            $this->_docs[$hash]['id'] = $doc['docid'];
        }

        return $this->_docs;
    }

    protected function getDocList()
    {
        $this->config->setConfig([
            'selectFields' => $this->getCFGDef('selectFields', 'c.*') . ', c.id AS docid, hashes.hash AS id',
        ]);

        $items = $this->getCFGDef('items');
        $join = [];

        foreach ($items as $row => $item) {
            $join[] = "SELECT " . $item['id'] . " AS id, '$row' AS `hash`";
        }

        $this->setFiltersJoin('JOIN (' . implode(' UNION ', $join) . ') hashes ON c.id = hashes.id');

        return parent::getDocList();
    }
}
