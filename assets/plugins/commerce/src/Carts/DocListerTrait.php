<?php

namespace Commerce\Carts;

trait DocListerTrait
{
    public function prepareCartRow($data, $modx, $DL, $e)
    {
        $total = $e->getStore('total');
        $count = $e->getStore('count');

        if (isset($this->items[$data['hash']])) {
            $data = array_merge($data, $this->items[$data['hash']]);
        }

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
                    'key'    => $key,
                    'option' => $option,
                ]);
            }
        }

        $data['options'] = $options;
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
            $result .= $DLTemplate->parseChunk('cart_subtotals_row', $row);
        }

        if (!empty($result)) {
            $result = $DLTemplate->parseChunk('cart_subtotals', [
                'wrap' => $result,
            ]);
        }

        $e->setStore('total', $total);
        return $result;
    }

    public function render(array $params)
    {
        if (!empty($params['useSavedParams']) && !empty($params['hash'])) {
            unset($params['useSavedParams']);
            $params = $this->restoreRenderingParams($params['hash']);
        }

        $params['hash'] = $this->storeRenderingParams($params);

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
        $params['prepare'][]     = [$this, 'prepareCartRowOptions'];
        $params['prepareWrap'][] = [$this, 'prepareCartOuter'];

        $params['theme'] = isset($params['theme']) ? $params['theme'] : '';

        $docids = [];
        foreach ($this->items as $item) {
            $docids[] = $item['id'];
        }

        return $this->modx->runSnippet('DocLister', array_merge([
            'tpl'        => $params['theme'] . 'cart_row',
            'optionsTpl' => $params['theme'] . 'cart_row_options_row',
            'ownerTPL'   => $params['theme'] . 'cart_wrap',
            'noneTPL'    => $params['theme'] . 'cart_wrap_empty',
        ], $params, [
            'controller' => 'Cart',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'sortType'   => 'doclist',
            'idType'     => 'documents',
            'documents'  => $docids,
            'items'      => $this->items,
            'tree'       => 0,
        ]));
    }

    protected function storeRenderingParams($params)
    {
        $hash = md5(json_encode($params));
        $_SESSION['commerce.cart-' . $hash] = serialize($params);

        return $hash;
    }

    protected function restoreRenderingParams($hash)
    {
        $result = [];

        if (!empty($_SESSION['commerce.cart-' . $hash])) {
            $result = unserialize($_SESSION['commerce.cart-' . $hash]);
        }

        return $result;
    }
}
