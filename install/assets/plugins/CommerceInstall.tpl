//<?php
/**
 * Commerce
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
    'OnCollectSubtotals',
    'OnRegisterDelivery',
    'OnRegisterPayments',
    'OnBeforeAddCartItem',
    'OnBeforeProcessOrder',
    'OnProcessOrder',
];

foreach ($events as $event) {
    $query = $this->modx->db->select('*', $tableEventnames, "`name` = '$event'");

    if (!$this->modx->db->getRecordCount($query)) {
        $this->modx->db->insert([
            'name'      => $event,
            'service'   => 6,
            'groupname' => 'Commerce',
        ], $tableEventnames);
    }
}

// remove installer
$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
