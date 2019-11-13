<?php
/**
 * Wishlist
 *
 * Wishlist contents, DocLister based
 *
 * @category    snippet
 * @version     0.4.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    return $modx->runSnippet('Cart', array_merge([
        'controller'        => 'Wishlist',
        'instance'          => 'wishlist',
        'templatePath'      => 'assets/plugins/commerce/templates/front/',
        'templateExtension' => 'tpl',
        'tpl'               => '@FILE:wishlist_row',
        'ownerTPL'          => '@FILE:wishlist_wrap',
        'customLang'        => 'common,cart',
    ], $params));
}
