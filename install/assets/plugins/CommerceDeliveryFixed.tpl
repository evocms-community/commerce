//<?php
/**
 * CommerceDeliveryFixed
 * 
 * Simple delivery
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$e = $modx->Event;

if (empty($params['title'])) {
    $lang = $modx->commerce->getUserLanguage('delivery');
    $params['title'] = $lang['delivery.fixed_title'];
}

switch ($e->name) {
    case 'OnCollectSubtotals': {
        $params['total'] += $params['price'];
        $params['rows']['fixed'] = [
            'title' => $params['title'],
            'price' => $params['price'],
        ];
        
        break;
    }
        
    case 'OnRegisterDelivery': {
        $params['rows']['fixed'] = [
            'title' => $params['title'],
            'price' => $params['price'],
        ];
        
        break;
    }
}
