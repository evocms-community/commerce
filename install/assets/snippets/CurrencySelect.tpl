//<?php
/**
 * CurrencySelect
 * 
 * Shows currency select
 *
 * @category    snippet
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$DLTemplate = DLTemplate::getInstance($modx);

$params = array_merge([
    'templatePath'      => 'assets/plugins/commerce/templates/front/',
    'templateExtension' => 'tpl',
    'tpl'               => '@FILE:currency_select_row',
    'activeTpl'         => '@FILE:currency_select_active_row',
    'outerTpl'          => '@FILE:currency_select_wrap',
], $params);

$currency = ci()->currency;
$rows     = $currency->getCurrencies();
$active   = $currency->getCurrencyCode();

$out = '';

$DLTemplate->setTemplatePath($params['templatePath']);
$DLTemplate->setTemplateExtension($params['templateExtension']);

foreach ($rows as $row) {
    $tpl = $row['code'] == $active ? $params['activeTpl'] : $params['tpl'];
    $out .= $DLTemplate->parseChunk($tpl, $row);
}

return $DLTemplate->parseChunk($params['outerTpl'], ['wrap' => $out]);
