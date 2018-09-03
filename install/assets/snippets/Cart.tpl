//<?php
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

$instance = isset($instance) ? $instance : 'products';
$cart = Commerce\CartsManager::getManager()->getCart($instance);

if (!is_null($cart) && method_exists($cart, 'render')) {
    $params['theme'] = isset($params['theme']) ? $params['theme'] : '';
    $params['instance'] = $instance;
    
    return $cart->render(array_merge([
        'tpl'             => $params['theme'] . 'cart_row',
        'optionsTpl'      => $params['theme'] . 'cart_row_options_row',
        'ownerTPL'        => $params['theme'] . 'cart_wrap',
        'noneTPL'         => $params['theme'] . 'cart_wrap_empty',
        'subtotalsRowTpl' => $params['theme'] . 'cart_subtotals_row',
        'subtotalsTpl'    => $params['theme'] . 'cart_subtotals',
    ], $params));
}
