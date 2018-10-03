//<?php
/**
 * Payment Yandexkassa
 *
 * Yandex Kassa payments processing
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnRegisterPayments
 * @internal    @properties &title=Название;text; &shop_id=Идентификатор магазина (shop_id);text;  &secret=Секретный ключ;text; &vat_code=Код системы налогообложения;list;Общая система налогообложения==1||Упрощенная (УСН, доходы)==2||Упрощенная (УСН, доходы минус расходы)==3||Единый налог на вмененный доход (ЕНВД)==4||Единый сельскохозяйственный налог (ЕСН)==5||Патентная система налогообложения==6;1 &debug=Отладка;list;Нет==0||Да==1;0
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $class = new \Commerce\Payments\YandexkassaPayment($modx, $params);

    if (empty($params['title'])) {
        $lang = $modx->commerce->getUserLanguage('payments');
        $params['title'] = $lang['payments.yandexkassa_title'];
    }

    $modx->commerce->registerPayment('yandexkassa', $params['title'], $class);
}