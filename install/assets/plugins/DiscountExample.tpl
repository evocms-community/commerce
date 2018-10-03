//<?php
/**
 * Discount example
 *
 * Discount example
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnCollectSubtotals,OnRegisterDelivery
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

$e = &$modx->Event;

switch ($e->name) {
    case 'OnCollectSubtotals': {
        $currency = ci()->currency;
        $price = $currency->convertToActive(200);

        if ($modx->commerce->getCart()->getTotal() >= $price) {
            $discount = $params['total'] * 0.2; // 20% discount

            $params['total'] -= $discount;

            $params['rows']['discount'] = [
                'title' => 'Discount for ' . $currency->format($price),
                'price' => -$discount,
            ];
        }
        break;
    }
}
