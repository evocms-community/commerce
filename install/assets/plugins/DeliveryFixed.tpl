//<?php
/**
 * Delivery Fixed
 *
 * Simple delivery
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @properties &title=Title;text; &price=Price;text;
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

$e = &$modx->Event;

if (empty($params['title'])) {
    $lang = $modx->commerce->getUserLanguage('delivery');
    $params['title'] = $lang['delivery.fixed_title'];
}

$price = ci()->currency->convertToActive($params['price']);

switch ($e->name) {
    case 'OnCollectSubtotals': {
        $processor = $modx->commerce->loadProcessor();

        if ($processor->isOrderStarted() && $processor->getCurrentDelivery() == 'fixed') {
            $params['total'] += $price;
            $params['rows']['fixed'] = [
                'title' => $params['title'],
                'price' => $price,
            ];
        }
        break;
    }

    case 'OnRegisterDelivery': {
        $params['rows']['fixed'] = [
            'title' => $params['title'],
            'price' => $price,
        ];

        break;
    }
}
