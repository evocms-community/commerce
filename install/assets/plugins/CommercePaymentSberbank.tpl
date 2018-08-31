//<?php
/**
 * CommercePaymentSberbank
 *
 * Sberbank payments processing
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\SberbankPayment($modx, $params);
    $title = $params['title'];

    if (empty($title)) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $title = $lang['payments.sberbank_title'];
    }

    $modx->commerce->registerPayment('sberbank', $title, $class);
}
