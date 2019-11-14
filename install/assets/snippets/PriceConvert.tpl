//<?php
/**
 * PriceConvert
 *
 * Convert price using predefined settings
 *
 * @category    snippet
 * @version     0.4.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (empty($modx->commerce) && !defined('COMMERCE_INITIALIZED')) {
    return $params['price'];
}

$currency = ci()->currency;

if (!empty($params['currency'])) {
    $params['price'] = $currency->convertFromDefault($params['price'], $params['current']);
} else {
    $params['price'] = $currency->convertToActive($params['price']);
}

return $params['price'];
