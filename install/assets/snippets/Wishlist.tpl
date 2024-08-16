//<?php
/**
 * Wishlist
 *
 * Wishlist contents, DocLister based
 *
 * @category    snippet
 * @version     0.13.1
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    return $modx->runSnippet('Cart', array_merge([
        'controller'        => 'Wishlist',
        'instance'          => 'wishlist',
        'tpl'               => '@FILE:wishlist_row',
        'ownerTPL'          => '@FILE:wishlist_wrap',
        'customLang'        => 'common,cart',
    ], $params));
}
