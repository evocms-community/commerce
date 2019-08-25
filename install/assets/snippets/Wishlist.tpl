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
    $params = [
        'instance'          => 'wishlist',
        'templatePath'      => 'assets/plugins/commerce/templates/front/',
        'templateExtension' => 'tpl',
        'tpl'               => '@FILE:wishlist_row',
        'ownerTPL'          => '@FILE:wishlist_wrap',
        'customLang'        => 'common,cart',
    ];

    if (isset($params['prepare'])) {
        if (!is_array($params['prepare'])) {
            $params['prepare'] = explode(',', $params['prepare']);
        } else if (is_callable($params['prepare'])) {
            $params['prepare'] = [$params['prepare']];
        }
    } else {
        $params['prepare'] = [];
    }

    if (!function_exists('prepareWishlistPrice')) {
        function prepareWishlistPrice($data, $modx, $DL, $eDL) {
            if (isset($data['price'])) {
                $priceField = $DL->getCFGDef('priceField');
                $data[$priceField] = $modx->runSnippet('PriceFormat', ['price' => $data[$priceField]]);
            }

            return $data;
        }
    }

    $priceField = $modx->commerce->getSetting('price_field', 'price');
    $tvPrefix = isset($params['tvPrefix']) ? $params['tvPrefix'] : 'tv.';

    $params['tvList']     = (isset($params['tvList']) ? $params['tvList'] . ',' : '') . $priceField;
    $params['priceField'] = $tvPrefix . $priceField;
    $params['prepare'][]  = 'prepareWishlistPrice';

    return $modx->runSnippet('Cart', $params);
}
