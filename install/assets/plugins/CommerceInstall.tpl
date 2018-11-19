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

function tableExists($modx, $table)
{
    try {
        $query = $modx->db->query("SHOW FIELDS FROM " . $table, false);
    } catch (Exception $e) {
        return false;
    }

    return $modx->db->getRecordCount($query) > 0;
}

$modx->clearCache('full');

$tableEventnames = $modx->getFullTablename('system_eventnames');
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
    'OnManagerOrdersListRender',
    'OnManagerOrderRender',
    'OnManagerStatusesListRender',
    'OnManagerStatusRender',
    'OnManagerCurrencyListRender',
    'OnManagerCurrencyRender',
    'OnManagerBeforeDefaultCurrencyChange',
    'OnManagerRegisterCommerceController',
    'OnBeforeCurrencyChange',
];

$query  = $modx->db->select('*', $tableEventnames, "`groupname` = 'Commerce'");
$exists = [];

while ($row = $modx->db->getRow($query)) {
    $exists[$row['name']] = $row['id'];
}

foreach ($events as $event) {
    if (!isset($exists[$event])) {
        $modx->db->insert([
            'name'      => $event,
            'service'   => 6,
            'groupname' => 'Commerce',
        ], $tableEventnames);
    }
}

require_once MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php';
require_once MODX_BASE_PATH . 'assets/snippets/FormLister/lib/Lexicon.php';

$lexicon = new \Helpers\Lexicon($modx, [
    'langDir' => 'assets/plugins/commerce/lang/',
    'lang'    => $modx->getConfig('manager_language'),
]);

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_orders') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) DEFAULT NULL,
        `phone` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `amount` float NOT NULL DEFAULT '0',
        `currency` varchar(8) NOT NULL,
        `fields` text,
        `status_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_order_products') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(10) unsigned NOT NULL,
        `product_id` int(10) unsigned DEFAULT NULL,
        `title` varchar(255) NOT NULL,
        `price` float NOT NULL,
        `count` float unsigned NOT NULL DEFAULT '1',
        `options` text,
        `meta` text,
        `position` tinyint(3) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`,`product_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    CREATE TABLE IF NOT EXISTS " . $modx->getFullTablename('commerce_order_history') . " (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(10) unsigned NOT NULL,
        `status_id` int(10) unsigned NOT NULL,
        `comment` text NOT NULL,
        `notify` tinyint(1) unsigned NOT NULL DEFAULT '1',
        `user_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`,`status_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$table = $modx->getFullTablename('commerce_order_statuses');

if (!tableExists($modx, $table)) {
    $modx->db->query("
        CREATE TABLE IF NOT EXISTS $table (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `notify` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `default` tinyint(1) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
    ");

    $lang = $lexicon->loadLang('order');

    $modx->db->insert(['title' => $lang['order.status.new'], 'default' => 1], $table);
    $modx->db->insert(['title' => $lang['order.status.processing']], $table);
    $modx->db->insert(['title' => $lang['order.status.paid'], 'notify' => 1], $table);
    $modx->db->insert(['title' => $lang['order.status.shipped']], $table);
    $modx->db->insert(['title' => $lang['order.status.canceled'], 'notify' => 1], $table);
    $modx->db->insert(['title' => $lang['order.status.complete']], $table);
    $modx->db->insert(['title' => $lang['order.status.pending']], $table);
}

$table = $modx->getFullTablename('commerce_currency');

if (!tableExists($modx, $table)) {
    $modx->db->query("
        CREATE TABLE IF NOT EXISTS $table (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `code` varchar(8) NOT NULL,
            `value` float NOT NULL DEFAULT '1',
            `left` varchar(8) NOT NULL,
            `right` varchar(8) NOT NULL,
            `decimals` tinyint(3) unsigned NOT NULL DEFAULT '2',
            `decsep` varchar(8) NOT NULL,
            `thsep` varchar(8) NOT NULL,
            `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
            `default` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
    ");

    $lang = $lexicon->loadLang('common');

    $modx->db->insert([
        'title'    => $lang['currency.title'],
        'code'     => $lang['currency.code'],
        'right'    => $lang['currency.right_symbol'],
        'decimals' => $lang['currency.decimals'],
        'decsep'   => $lang['currency.decimals_separator'],
        'thsep'    => $lang['currency.thousands_separator'],
        'value'    => 1,
        'default'  => 1,
    ], $table);
}

$modx->db->update(['disabled' => 0], $modx->getFullTablename('site_plugins'), "`name` = 'Commerce'");

// TODO store all parameters in main plugin, link plugins and snippets

// remove installer
$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
