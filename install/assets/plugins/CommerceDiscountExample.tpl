//<?php
/**
 * CommerceDiscountExample
 *
 * Discount example
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$e = &$modx->Event;

switch ($e->name) {
    case 'OnCollectSubtotals': {
        if ($modx->commerce->getCart()->getTotal() >= 2000) {
            $discount = $params['total'] * 0.2;

            $params['total'] -= $discount;

            $params['rows']['discount'] = [
                'title' => 'Discount example',
                'price' => -$discount,
            ];
        }
        break;
    }
}
