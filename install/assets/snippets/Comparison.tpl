<?php
/**
 * Comparison
 *
 * Comparison snippet, DocLister based
 *
 * @category    snippet
 * @version     0.4.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

/**
 * [!Comparison
 *      &showCategories=`1`
 *      &tvCategory=`10`
 *      &excludeTV=`category`
 *      &includeTV=`best`
 *      &checkBoundingList=`0`
 *      &categoryItemClass=`btn-secondary`
 *      &categoryActiveClass=`btn-primary`
 * !]
 */

if (!defined('COMMERCE_INITIALIZED')) {
    return;
}

$items = array_map(function($item) {
    return $item['id'];
}, ci()->carts->getCart('comparison')->getItems());

if (empty($items)) {
    return;
}

$showCategories = isset($params['showCategories']) ? $params['showCategories'] : 1;

if ($showCategories) {
    $table   = $modx->getFullTablename('site_content');
    $parents = $modx->db->getColumn('parent', $modx->db->select('parent', $table, "`id` IN (" . implode(',', $items) . ")"));
    $parents = array_unique($parents);

    $categoryParams = [];

    foreach ($params as $key => $value) {
        if (strpos($key, 'category') === 0) {
            unset($params[$key]);
            $key = preg_replace('/^category/', '', $key);
            $key = lcfirst($key);
            $categoryParams[$key] = $value;
        }
    }

    if (isset($_GET['category']) && is_scalar($_GET['category']) && in_array($_GET['category'], $parents)) {
        $currentCategory = $_GET['category'];
    }

    if (empty($currentCategory)) {
        $currentCategory = reset($parents);
    }

    $categories = '';

    if (count($parents) > 1) {
        $categoryParams = array_merge([
            'templatePath'      => 'assets/plugins/commerce/templates/front/',
            'templateExtension' => 'tpl',
            'tpl'               => '@FILE:comparison_category',
            'ownerTPL'          => '@FILE:comparison_categories',
            'itemClass'         => 'btn-secondary',
            'activeClass'       => 'btn-primary',
            'prepare'           => function($data, $modx, $DL, $eDL) {
                $data['class'] = $DL->getCFGDef('currentId') == $data['id'] ? $DL->getCFGDef('activeClass') : $DL->getCFGDef('itemClass');
                return $data;
            },
        ], $categoryParams, [
            'currentId' => $currentCategory,
            'idType'    => 'documents',
            'documents' => $parents,
            'sortType'  => 'doclist',
        ]);

        $categories = $modx->runSnippet('DocLister', $categoryParams);
    }

    $ids = $modx->db->getColumn('id', $modx->db->select('id', $table, "`parent` = '$currentCategory' AND `id` IN ('" . implode("','", array_unique($items)) . "')"));
} else {
    $ids = array_values(array_unique($items));
    $currentCategory = 0;
}

$params = array_merge([
    'templatePath'      => 'assets/plugins/commerce/templates/front/',
    'templateExtension' => 'tpl',
    'ownerTPL'          => '@FILE:comparison_table',
    'headerTpl'         => '@FILE:comparison_table_header_cell',
    'footerTpl'         => '@FILE:comparison_table_footer_cell',
    'keyTpl'            => '@FILE:comparison_table_key_cell',
    'valueTpl'          => '@FILE:comparison_table_value_cell',
    'rowTpl'            => '@FILE:comparison_table_row',
    'customLang'        => 'common,cart',
], $params, [
    'controller' => 'Comparison',
    'dir'        => 'assets/plugins/commerce/src/Controllers/',
    'idType'     => 'documents',
    'sortType'   => 'doclist',
    'documents'  => $ids,
    'category'   => $currentCategory,
    'rows'       => array_flip($items),
]);

$priceField = $modx->commerce->getSetting('price_field', 'price');
$params['tvList'] = (isset($params['tvList']) ? $params['tvList'] . ',' : '') . $priceField;

if (isset($params['prepare'])) {
    if (!is_array($params['prepare'])) {
        $params['prepare'] = explode(',', $params['prepare']);
    } else if (is_callable($params['prepare'])) {
        $params['prepare'] = [$params['prepare']];
    }
} else {
    $params['prepare'] = [];
}

$tvPrefix = isset($params['tvPrefix']) ? $params['tvPrefix'] : 'tv.';
$priceField = $tvPrefix . $priceField;

$params['prepare'][] = function($data, $modx, $DL, $eDL) use ($priceField) {
    if (isset($data[$priceField])) {
        $data[$priceField] = $modx->runSnippet('PriceFormat', ['price' => $data[$priceField]]);
    }

    return $data;
};


$docs = $modx->runSnippet('DocLister', $params);
return $categories . $docs;
