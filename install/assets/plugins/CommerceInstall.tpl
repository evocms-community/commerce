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

// do install

// remove installer
$tablePlugins = $modx->getFullTablename('site_plugins');
$tableEvents  = $modx->getFullTablename('site_plugin_events');

$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
