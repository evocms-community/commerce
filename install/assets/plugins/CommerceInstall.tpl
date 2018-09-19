//<?php
/**
 * CommerceInstall
 *
 * Commerce solution installer
 *
 * @category    plugin
 * @author      mnoskov
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

$modx->clearCache('full');

$tableEventnames = $modx->getFullTableName('system_eventnames');
$tablePlugins    = $modx->getFullTablename('site_plugins');
$tableEvents     = $modx->getFullTablename('site_plugin_events');

$events = [
    'OnInitializeCommerce',
    'OnInitializeOrderProcessor',
    'OnCollectSubtotals',
    'OnRegisterDelivery',
    'OnRegisterPayments',
    'OnBeforeCartItemAdding',
    'OnOrderRawDataChanged',
    'OnBeforeOrderProcessing',
    'OnBeforePaymentProcess',
    'OnBeforeOrderSaving',
    'OnOrderSaved',
    'OnOrderProcessed',
    'OnBeforeOrderHistoryUpdate',
    'OnManagerBeforeOrdersListRender',
    'OnManagerBeforeOrderRender',
];

foreach ($events as $event) {
    $query = $modx->db->select('*', $tableEventnames, "`name` = '$event'");

    if (!$modx->db->getRecordCount($query)) {
        $modx->db->insert([
            'name'      => $event,
            'service'   => 6,
            'groupname' => 'Commerce',
        ], $tableEventnames);
    }
}

// TODO store all parameters in main plugin, link plugins and snippets

// remove installer
$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
