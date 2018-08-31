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
    return $cart->render($params);
}
