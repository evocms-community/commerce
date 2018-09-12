<?php

namespace Commerce\Carts;

trait DocListerTrait
{
    public function prepareCartRow($data, $modx, $DL, $e)
    {
        $total = $e->getStore('total');
        $count = $e->getStore('count');

        $data['total'] = $data['price'] * $data['count'];

        $total += $data['total'];
        $count += $data['count'];
        
        $e->setStore('total', $total);
        $e->setStore('count', $count);
        
        return $data;
    }

    public function prepareCartRowOptions($data, $modx, $DL, $e)
    {
        $options = '';

        if (isset($data['options'])) {
            foreach ($data['options'] as $key => $option) {
                $options .= \DLTemplate::getInstance($modx)->parseChunk($DL->getCFGDef('optionsTpl'), [
                    'key'    => htmlentities($key),
                    'option' => htmlentities($option),
                ]);
            }
        }

        $data['_options'] = $data['options'];
        $data['options']  = $options;
        return $data;
    }

    public function prepareCartOuter($data, $modx, $DL, $e)
    {
        $placeholders = $data['placeholders'];

        $placeholders['hash']      = $DL->getCFGDef('hash');
        $placeholders['subtotals'] = $this->renderSubtotals($e->getStore('total'), $DL, $e);
        $placeholders['total']     = $e->getStore('total');
        $placeholders['count']     = $e->getStore('count');

        return $placeholders;
    }

    protected function renderSubtotals($total, $DL, $e)
    {
        $DLTemplate = \DLTemplate::getInstance($this->modx);
        $result = '';
        $rows = [];

        $this->getSubTotals($rows, $total);

        foreach ($rows as $row) {
            $result .= $DLTemplate->parseChunk($DL->getCFGDef('subtotalsRowTpl'), $row);
        }

        if (!empty($result)) {
            $result = $DLTemplate->parseChunk($DL->getCFGDef('subtotalsTpl'), [
                'wrap' => $result,
            ]);
        }

        $e->setStore('total', $total);
        return $result;
    }

    public function render(array $params)
    {
        $params['hash'] = \Commerce\CartsManager::getManager()->storeParams($params);

        foreach (['prepare', 'prepareWrap'] as $prepare) {
            if (isset($params[$prepare])) {
                if (!is_array($params[$prepare])) {
                    $params[$prepare] = explode(',', $params[$prepare]);
                }
            } else {
                $params[$prepare] = [];
            }
        }

        $params['prepare'][]     = [$this, 'prepareCartRow'];
        $params['prepareWrap'][] = [$this, 'prepareCartOuter'];

        if (empty($params['defaultOptionsRender'])) {
            $params['prepare'][] = [$this, 'prepareCartRowOptions'];
        }

        $docids = [];
        foreach ($this->items as $item) {
            $docids[] = $item['id'];
        }

        return $this->modx->runSnippet('DocLister', array_merge($params, [
            'controller' => 'Cart',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'sortType'   => 'doclist',
            'idType'     => 'documents',
            'documents'  => $docids,
            'cartItems'  => $this->items,
            'tree'       => 0,
        ]));
    }
}
