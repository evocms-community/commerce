//<?php
/**
 * Payment Paykeeper
 *
 * Paykeeper payments processing
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @properties &title=Title;text; &pay_url=Payment url;text; &secret=Token;text; &template=Chunk name or code;text; &debug=Отладка;list;No==0||Yes==1;0
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\PaykeeperPayment($modx, $params);

    if (empty($params['title'])) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $params['title'] = $lang['payments.paykeeper_title'];
    }

    $modx->commerce->registerPayment('paykeeper', $params['title'], $class);
}
