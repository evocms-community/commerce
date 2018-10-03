//<?php
/**
 * Delivery Pickup
 *
 * Pickup
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @properties &title=Title;text; 
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

$e = &$modx->Event;

if (empty($params['title'])) {
    $lang = $modx->commerce->getUserLanguage('delivery');
    $params['title'] = $lang['delivery.pickup_title'];
}

switch ($e->name) {
    case 'OnCollectSubtotals': {
        $processor = $modx->commerce->loadProcessor();

        if ($processor->isOrderStarted() && $processor->getCurrentDelivery() == 'pickup') {
            $params['rows']['pickup'] = [
                'title' => $params['title'],
                'price' => 0,
            ];
        }
        break;
    }

    case 'OnRegisterDelivery': {
        $params['rows']['pickup'] = [
            'title' => $params['title'],
            'price' => 0,
        ];

        break;
    }
}
