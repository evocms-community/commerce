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
return $currency->convertFromDefault($params['price'], !empty($params['currency']) ? $params['currency'] : null);
