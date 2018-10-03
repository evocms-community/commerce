//<?php
/**
 * Payment Sberbank
 *
 * Sberbank payments processing
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @properties &title=Title;text; &token=Token;text;
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\SberbankPayment($modx, $params);

    if (empty($params['title'])) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $params['title'] = $lang['payments.sberbank_title'];
    }

    $modx->commerce->registerPayment('sberbank', $params['title'], $class);
}
