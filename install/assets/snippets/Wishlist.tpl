<?php
/**
 * Wishlist
 * 
 * Wishlist contents, DocLister based
 *
 * @category    snippet
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $items = ci()->carts->getCart('wishlist')->getItems();

    $out = $modx->runSnippet('DocLister', array_merge([
        'tvList'            => 'price,image',
        'sortType'          => 'doclist',
        'templatePath'      => 'assets/plugins/commerce/templates/front/',
        'templateExtension' => 'tpl',
        'tpl'               => '@FILE:wishlist_row',
        'ownerTPL'          => '@FILE:wishlist_wrap',
    ], $params, [
        'controller' => 'CustomLang',
        'dir'        => 'assets/plugins/commerce/src/Controllers/',
        'idType'     => 'documents',
        'documents'  => array_values(array_unique(array_column($items, 'id'))),
        'tree'       => 0,
        'customLang' => 'common,cart',
    ]));

    return $out;
}
