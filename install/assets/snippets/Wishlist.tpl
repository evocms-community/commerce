<?php
/**
 * Wishlist
 * 
 * Wishlist contents, DocLister based
 *
 * @category    snippet
 * @version     0.1.1
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    return $modx->runSnippet('Cart', [
        'controller'        => 'Wishlist',
        'instance'          => 'wishlist',
        'templatePath'      => 'assets/plugins/commerce/templates/front/',
        'templateExtension' => 'tpl',
        'tpl'               => '@FILE:wishlist_row',
        'ownerTPL'          => '@FILE:wishlist_wrap',
        'customLang'        => 'common,cart',
    ]);
}
