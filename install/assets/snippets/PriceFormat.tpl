//<?php
/**
 * PriceFormat
 *
 * Format price using predefined settings
 *
 * @category    snippet
 * @version     0.6.1
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    $currency = ci()->currency;

    $params = array_merge([
        'price'   => 0,
        'convert' => 1,
    ], $params);

    if ($params['convert']) {
        $params['price'] = $currency->convertToActive($params['price']);
    }

    return $currency->format($params['price']);
}

return array_shift($params);
