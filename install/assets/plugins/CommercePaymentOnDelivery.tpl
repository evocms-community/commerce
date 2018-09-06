//<?php
/**
 * CommercePaymentOnDelivery
 *
 * Dummy payment (offline payment)
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @properties &title=Payment title;text; 
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\Payment($modx, $params);

    if (empty($params['title'])) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $params['title'] = $lang['payments.on_delivery_title'];
    }

    $modx->commerce->registerPayment('on_delivery', $params['title'], $class);
}
