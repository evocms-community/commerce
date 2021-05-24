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

if (file_exists(MODX_BASE_PATH . 'assets/snippets/FormLister/__autoload.php')) {
    require_once MODX_BASE_PATH . 'assets/snippets/FormLister/__autoload.php';
}

$tableEventnames = $modx->getFullTablename('system_eventnames');
$tablePlugins    = $modx->getFullTablename('site_plugins');
$tableEvents     = $modx->getFullTablename('site_plugin_events');

$previousVersion = '0.0.0';

$description = $modx->db->getValue($modx->db->select('description', $tablePlugins, "`name` = 'Commerce'", '`id` DESC', '1, 1'));
if (!empty($description) && preg_match('/<(b|strong)>(.+?)<\/(b|strong)>/', $description, $matches)) {
    $previousVersion = $matches[2];
}

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

$events = [
    'OnInitializeCommerce',
    'OnInitializeOrderProcessor',
    'OnInitializeOrderForm',
    'OnCollectSubtotals',
    'OnRegisterDelivery',
    'OnRegisterPayments',
    'OnBeforeOrderAddonsRender',
    'OnBeforeCartItemAdding',
    'OnBeforeCartItemUpdating',
    'OnBeforeCartItemRemoving',
    'OnBeforeCartCleaning',
    'OnCartChanged',
    'OnOrderRawDataChanged',
    'OnBeforeOrderProcessing',
    'OnBeforePaymentProcess',
    'OnBeforePaymentCreate',
    'OnBeforeOrderSaving',
    'OnBeforeOrderDeleting',
    'OnBeforeOrderSending',
    'OnOrderSaved',
    'OnOrderDeleted',
    'OnOrderPaid',
    'OnOrderProcessed',
    'OnBeforeOrderHistoryUpdate',
    'OnBeforeCustomerNotifySending',
    'OnManagerBeforeOrdersListRender',
    'OnManagerOrdersListRender',
    'OnManagerBeforeOrderRender',
    'OnManagerOrderRender',
    'OnManagerBeforeOrderEditRender',
    'OnManagerOrderEditRender',
    'OnManagerBeforeOrderValidating',
    'OnManagerOrderValidated',
    'OnManagerStatusesListRender',
    'OnManagerStatusRender',
    'OnManagerCurrencyListRender',
    'OnManagerCurrencyRender',
    'OnManagerBeforeDefaultCurrencyChange',
    'OnManagerRegisterCommerceController',
    'OnBeforeCurrencyChange',
    'OnCommerceAjaxResponse',
    'OnOrderPlaceholdersPopulated',
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

$lexicon = new \Helpers\Lexicon($modx, [
    'langDir' => 'assets/plugins/commerce/lang/',
    'lang'    => $modx->getConfig('manager_language'),
]);

$orders_table = $modx->getFullTablename('commerce_orders');

$modx->db->query("
    CREATE TABLE IF NOT EXISTS {$orders_table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) DEFAULT NULL,
        `phone` varchar(255) DEFAULT NULL,
        `email` varchar(255) DEFAULT NULL,
        `amount` decimal(16,2) NOT NULL,
        `currency` varchar(8) NOT NULL,
        `fields` text,
        `status_id` tinyint(3) unsigned NOT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("ALTER TABLE {$orders_table} ADD `hash` VARCHAR(32) NOT NULL AFTER `status_id`, ADD INDEX (`hash`);", false);
$modx->db->query("ALTER TABLE {$orders_table} ADD `customer_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`, ADD INDEX (`customer_id`);", false);
$modx->db->query("ALTER TABLE {$orders_table} ADD `lang` VARCHAR(32) NOT NULL AFTER `currency`;", false);

$table = $modx->getFullTablename('commerce_order_products');

$modx->db->query("
    CREATE TABLE IF NOT EXISTS {$table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(10) unsigned NOT NULL,
        `product_id` int(10) unsigned DEFAULT NULL,
        `title` varchar(255) NOT NULL,
        `price` decimal(16,2) NOT NULL,
        `count` float unsigned NOT NULL DEFAULT 1,
        `options` text,
        `meta` text,
        `position` tinyint(3) unsigned NOT NULL,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`,`product_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    ALTER TABLE {$table}
        ADD CONSTRAINT `commerce_order_products_ibfk_1`
        FOREIGN KEY (`order_id`)
        REFERENCES {$orders_table} (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
", false);

$table = $modx->getFullTablename('commerce_order_history');

$modx->db->query("
    CREATE TABLE IF NOT EXISTS {$table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(10) unsigned NOT NULL,
        `status_id` int(10) unsigned NOT NULL,
        `comment` text NOT NULL,
        `notify` tinyint(1) unsigned NOT NULL DEFAULT 1,
        `user_id` int(11) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`,`status_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("
    ALTER TABLE {$table}
        ADD CONSTRAINT `commerce_order_history_ibfk_1`
        FOREIGN KEY (`order_id`)
        REFERENCES {$orders_table} (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
", false);

$table = $modx->getFullTablename('commerce_order_payments');

$modx->db->query("
    CREATE TABLE IF NOT EXISTS {$table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` int(10) unsigned NOT NULL,
        `amount` decimal(16,2) NOT NULL,
        `paid` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `hash` varchar(16) NOT NULL,
        `created_at` timestamp NULL DEFAULT NULL,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `hash` (`hash`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
");

$modx->db->query("ALTER TABLE {$table} CHANGE `hash` `hash` VARCHAR(128) NOT NULL;", false);
$modx->db->query("ALTER TABLE {$table} ADD `meta` TEXT NOT NULL AFTER `hash`;", false);
$modx->db->query("ALTER TABLE {$table} ADD `payment_method` VARCHAR(255) NOT NULL DEFAULT '' AFTER `hash`;", false);
$modx->db->query("ALTER TABLE {$table} ADD `original_order_id` VARCHAR(255) NOT NULL DEFAULT '' AFTER `payment_method`;", false);
$modx->db->query("ALTER TABLE {$table} ADD INDEX (`original_order_id`);", false);

$modx->db->query("
    ALTER TABLE {$table}
        ADD CONSTRAINT `commerce_order_payments_ibfk_1`
        FOREIGN KEY (`order_id`)
        REFERENCES {$orders_table} (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
", false);

$table = $modx->getFullTablename('commerce_order_statuses');
$tableExists = tableExists($modx, $table);

$modx->db->query("
    CREATE TABLE IF NOT EXISTS {$table} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `notify` tinyint(1) unsigned NOT NULL DEFAULT 0,
        `default` tinyint(1) unsigned NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
", false);

$modx->db->query("ALTER TABLE {$table} ADD `alias` VARCHAR(255) NOT NULL DEFAULT '' AFTER `title`;", false);
$modx->db->query("ALTER TABLE {$table} ADD `color` VARCHAR(6) NOT NULL DEFAULT '' AFTER `alias`;", false);
$modx->db->query("ALTER TABLE {$table} ADD `canbepaid` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `default`;", false);

$lang = $lexicon->loadLang('order');

if (!$tableExists) {
    $modx->db->insert(['title' => $lang['order.status.new'], 'alias' => 'order.status.new', 'default' => 1, 'color' => '000000'], $table);
    $modx->db->insert(['title' => $lang['order.status.processing'], 'alias' => 'order.status.processing', 'color' => '4CAF50'], $table);
    $modx->db->insert(['title' => $lang['order.status.paid'], 'alias' => 'order.status.paid', 'notify' => 1, 'color' => 'E91E63'], $table);
    $modx->db->insert(['title' => $lang['order.status.shipped'], 'alias' => 'order.status.shipped', 'color' => '673AB7'], $table);
    $modx->db->insert(['title' => $lang['order.status.canceled'], 'alias' => 'order.status.canceled', 'notify' => 1, 'color' => 'FF5722', 'canbepaid' => 0], $table);
    $modx->db->insert(['title' => $lang['order.status.complete'], 'alias' => 'order.status.complete', 'color' => '2196F3', 'canbepaid' => 0], $table);
    $modx->db->insert(['title' => $lang['order.status.pending'], 'alias' => 'order.status.pending', 'color' => '9E9E9E'], $table);
} else {
    $modx->db->update(['color' => '000000'], $table, "`color` = '' AND `title` = '{$lang['order.status.new']}'");
    $modx->db->update(['color' => '4CAF50'], $table, "`color` = '' AND `title` = '{$lang['order.status.processing']}'");
    $modx->db->update(['color' => 'E91E63'], $table, "`color` = '' AND `title` = '{$lang['order.status.paid']}'");
    $modx->db->update(['color' => '673AB7'], $table, "`color` = '' AND `title` = '{$lang['order.status.shipped']}'");
    $modx->db->update(['color' => 'FF5722'], $table, "`color` = '' AND `title` = '{$lang['order.status.canceled']}'");
    $modx->db->update(['color' => '2196F3'], $table, "`color` = '' AND `title` = '{$lang['order.status.complete']}'");
    $modx->db->update(['color' => '9E9E9E'], $table, "`color` = '' AND `title` = '{$lang['order.status.pending']}'");

    if (version_compare($previousVersion, '0.6.4', '<=')) {
        $modx->db->update(['canbepaid' => 0], $table, "`title` = '{$lang['order.status.canceled']}'");
        $modx->db->update(['canbepaid' => 0], $table, "`title` = '{$lang['order.status.complete']}'");
    }
}

$table = $modx->getFullTablename('commerce_currency');

if (!tableExists($modx, $table)) {
    $modx->db->query("
        CREATE TABLE IF NOT EXISTS {$table} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `code` varchar(8) NOT NULL,
            `value` float NOT NULL DEFAULT 1,
            `left` varchar(8) NOT NULL,
            `right` varchar(8) NOT NULL,
            `decimals` tinyint(3) unsigned NOT NULL DEFAULT 2,
            `decsep` varchar(8) NOT NULL,
            `thsep` varchar(8) NOT NULL,
            `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
            `default` tinyint(1) unsigned NOT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
    ");

    $lang = $lexicon->loadLang('common');

    $modx->db->insert([
        'title'    => $lang['currency.title'],
        'code'     => $lang['currency.code'],
        'left'     => $lang['currency.left_symbol'],
        'right'    => $lang['currency.right_symbol'],
        'decimals' => $lang['currency.decimals'],
        'decsep'   => $lang['currency.decimals_separator'],
        'thsep'    => $lang['currency.thousands_separator'],
        'value'    => 1,
        'default'  => 1,
    ], $table);
}

$modx->db->query("ALTER TABLE {$table} ADD `lang` VARCHAR(8) NOT NULL DEFAULT '' AFTER `default`;", false);
$modx->db->query("ALTER TABLE {$table} CHANGE `left` `left` VARCHAR(32) NOT NULL DEFAULT '';", false);
$modx->db->query("ALTER TABLE {$table} CHANGE `right` `right` VARCHAR(32) NOT NULL DEFAULT '';", false);

$id = $modx->db->getValue($modx->db->select('MAX(id)', $tablePlugins, "`name` = 'Commerce'"));
$modx->db->update(['disabled' => 0], $tablePlugins, "`id` = '$id'");

// need to move language templates to a new location
$files = glob(MODX_BASE_PATH . 'assets/plugins/commerce/lang/*/*.tpl');

foreach ($files as $source) {
    if (is_readable($source)) {
        $target = str_replace('/lang/', '/templates/front/', $source);
        file_put_contents(MODX_BASE_PATH . 'in.txt', "$source\n$target\n\n", FILE_APPEND);
        @rename($source, $target);
    }
}

// remove installer
$query = $modx->db->select('id', $tablePlugins, "`name` = 'CommerceInstall'");

if ($id = $modx->db->getValue($query)) {
   $modx->db->delete($tablePlugins, "`id` = '$id'");
   $modx->db->delete($tableEvents, "`pluginid` = '$id'");
};
