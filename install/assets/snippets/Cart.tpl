<?php
/**
 * Cart
 * 
 * Cart contents, DocLister based
 *
 * @category    snippet
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $instance = isset($instance) ? $instance : 'products';
    $theme    = !empty($theme) ? $theme : '';
    $cart     = ci()->carts->getCart($instance);
    $lang     = $modx->getConfig('manager_language');

    if (!is_null($cart)) {
        return $modx->runSnippet('DocLister', array_merge([
            'templatePath'      => 'assets/plugins/commerce/templates/front/',
            'templateExtension' => 'tpl',
            'tpl'               => '@FILE:' . $theme . 'cart_row',
            'optionsTpl'        => '@FILE:' . $theme . 'cart_row_options_row',
            'ownerTPL'          => '@FILE:' . $theme . 'cart_wrap',
            'subtotalsRowTpl'   => '@FILE:' . $theme . 'cart_subtotals_row',
            'subtotalsTpl'      => '@FILE:' . $theme . 'cart_subtotals',
            'customLang'        => 'assets/plugins/commerce/lang/' . $lang . '/cart.inc.php',
        ], $params, [
            'controller' => 'Cart',
            'dir'        => 'assets/plugins/commerce/src/Controllers/',
            'sortType'   => 'doclist',
            'idType'     => 'documents',
            'documents'  => array_column($cart->getItems(), 'id'),
            'instance'   => $instance,
            'hash'       => ci()->commerce->storeParams($params),
            'cart'       => $cart,
            'tree'       => 0,
        ]));
    }
}
