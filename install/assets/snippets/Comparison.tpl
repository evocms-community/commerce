//<?php
/**
 * Comparison
 *
 * Comparison snippet, DocLister based
 *
 * @category    snippet
 * @version     0.11.1
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

if (isset($ids)) {
    if (!is_array($ids)) {
        $ids = array_map('trim', explode(',', $ids));
    }
    $items = $ids;
} else {
    $items = array_map(function($item) {
        return $item['id'];
    }, ci()->carts->getCart('comparison')->getItems());
}

$showCategories = isset($params['showCategories']) ? $params['showCategories'] : 1;

if (!empty($items) && $showCategories) {
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
            'tpl'               => '@FILE:comparison_category',
            'ownerTPL'          => '@FILE:comparison_categories',
            'itemClass'         => 'btn-secondary',
            'activeClass'       => 'btn-primary',
            'prepare'           => function($data, $modx, $DL, $eDL) {
                $data['class'] = $DL->getCFGDef('currentId') == $data['id'] ? $DL->getCFGDef('activeClass') : $DL->getCFGDef('itemClass');
                return $data;
            },
        ], $categoryParams, [
            'controller' => 'CustomLang',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'currentId'  => $currentCategory,
            'idType'     => 'documents',
            'documents'  => $parents,
            'sortType'   => 'doclist',
        ]);

        $categories = $modx->runSnippet('DocLister', $categoryParams);
    }

    $ids = $modx->db->getColumn('id', $modx->db->select('id', $table, "`parent` = '$currentCategory' AND `id` IN ('" . implode("','", array_unique($items)) . "')"));
} else {
    $ids = array_values(array_unique($items));
    $currentCategory = 0;
}

$params = array_merge([
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

$docs = $modx->runSnippet('DocLister', $params);
return $categories . $docs;
