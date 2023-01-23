<?php

namespace Commerce\Controllers\Traits;

trait CommonCartTrait
{
    protected $productsCount = 0;
    protected $rowsCount     = 0;
    protected $priceTotal    = 0;
    protected $extPrepare;

    protected function getCartAdditionals()
    {
        return [
            'hash'        => $this->getCFGDef('hash'),
            'instance'    => $this->getCFGDef('instance', 'products'),
            'items_price' => $this->priceTotal,
            'subtotals'   => $this->renderSubtotals(),
            'total'       => $this->priceTotal,
            'count'       => $this->productsCount,
            'rows_count'  => $this->rowsCount,
            'settings'    => $this->modx->commerce->getSettings(),
        ];
    }

    protected function initializeCommonCart($cfg)
    {
        array_unshift($cfg['prepareWrap'], [$this, 'prepareCartOuter']);
        return $cfg;
    }

    public function prepareCartOuter($data, $modx, $DL, $e)
    {
        $data['placeholders'] = array_merge($data['placeholders'], $this->getCartAdditionals());

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

        if ($this->getCFGDef('api') == 1) {
            if ($this->getCFGDef('JSONformat') == 'new') {
                $result = $rows;
            }
        } else {
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
        }

        return $result;
    }

    protected function mixinCartItems($docs)
    {
        $cartItems = $this->getCFGDef('cart')->getItems();

        foreach ($docs as $hash => $doc) {
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
                $doc['total'] = $doc['price'] * $doc['count'];
            }

            $docs[$hash] = $doc;
        }

        $this->rowsCount = count($cartItems);

        foreach ($cartItems as $item) {
            $this->productsCount += $item['count'];
            $this->priceTotal += (float)$item['price'] * $item['count'];
        }

        return $docs;
    }

    protected function mixinProductOptionsRender($docs)
    {
        if (!empty($this->getCFGDef('defaultOptionsRender', 1))) {
            $optionsTpl = $this->getCFGDef('optionsTpl');

            foreach ($docs as $id => $item) {
                $options = '';

                if (isset($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $key => $option) {
                        $options .= ci()->tpl->parseChunk($optionsTpl, [
                            'key'    => htmlentities($key),
                            'option' => nl2br(htmlentities(is_scalar($option) ? $option : json_encode($option, JSON_UNESCAPED_UNICODE))),
                        ]);
                    }
                }

                $item['_options'] = $item['options'];
                $item['options']  = $options;
                $docs[$id] = $item;
            }
        }

        return $docs;
    }

    protected function mixinCartAdditionals($result)
    {
        $result = json_decode($result, true);
        $result = array_merge($result, $this->getCartAdditionals());

        $this->outData = json_encode($result);
        $this->isErrorJSON($result);

        return $this->getCFGDef('debug') ? jsonHelper::json_format($this->outData) : $this->outData;
    }

    protected function handleEmptyResult($docs)
    {
        if (!$this->getCFGDef('noneWrapOuter', '1') && !count($docs)) {
            $this->ownerTPL = $this->getCFGDef('noneTPL');
            $this->config->setConfig(['noneTPL' => '']);
        }
    }
}
