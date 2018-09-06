//<?php
/**
 * CommerceDeliveryPickup
 *
 * Pickup
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @properties &title=Title;text; 
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$e = &$modx->Event;

if (empty($params['title'])) {
    $lang = $modx->commerce->getUserLanguage('delivery');
    $params['title'] = $lang['delivery.pickup_title'];
}

switch ($e->name) {
    case 'OnCollectSubtotals': {
        if ($modx->commerce->loadProcessor()->getCurrentDelivery() == 'pickup') {
            $params['total'] += $params['price'];
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
