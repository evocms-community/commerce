//<?php
/**
 * Cart
 *
 * Cart contents, DocLister based
 *
 * @category    snippet
 * @version     0.6.13
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    $instance = isset($instance) ? $instance : 'products';
    $theme    = !empty($theme) ? $theme : '';
    $cart     = ci()->carts->getCart($instance);
	$langDir  = isset($langDir) ? $langDir : 'assets/plugins/commerce/lang/';
    if (!is_null($cart)) {
        return $modx->runSnippet('DocLister', array_merge([
            'controller'        => 'Cart',
            'dir'               => 'assets/plugins/commerce/src/Controllers/',
            'tpl'               => '@FILE:' . $theme . 'cart_row',
            'optionsTpl'        => '@FILE:' . $theme . 'cart_row_options_row',
            'ownerTPL'          => '@FILE:' . $theme . 'cart_wrap',
            'subtotalsRowTpl'   => '@FILE:' . $theme . 'cart_subtotals_row',
            'subtotalsTpl'      => '@FILE:' . $theme . 'cart_subtotals',
            'noneTPL'           => '@FILE:' . $theme . 'cart_empty',
            'langDir'           =>  $langDir,
            'customLang'        => 'cart',
            'noneWrapOuter'     => 0,
        ], $params, [
            'idType'     => 'documents',
            'documents'  => array_column($cart->getItems(), 'id'),
            'instance'   => $instance,
            'hash'       => ci()->commerce->storeParams($params),
            'cart'       => $cart,
            'tree'       => 0,
        ]));
    }
}
