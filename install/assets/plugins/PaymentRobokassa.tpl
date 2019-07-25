//<?php
/**
 * Payment Robokassa
 *
 * Robokassa payments processing
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @properties &title=Название;text; &merchant_login=Идентификатор магазина;text; &pass1=Пароль 1;text; &pass2=Пароль 2;text; &debug=Отладка;list;Нет==0||Да==1;0 &testpass1=Отладочный пароль 1;text; &testpass2=Отладочный пароль 2;text; &vat_code=Ставка НДС;list;НДС не облагается==none||НДС 0%==vat0||НДС по формуле 10/110==vat110||НДС по формуле 20/120==vat120||НДС 10%==vat10||НДС 20%==vat20;none
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\RobokassaPayment($modx, $params);

    if (empty($params['title'])) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $params['title'] = $lang['payments.robokassa_title'];
    }

    $modx->commerce->registerPayment('robokassa', $params['title'], $class);
}
