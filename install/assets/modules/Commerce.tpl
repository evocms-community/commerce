//<?php
/**
 * Commerce
 *
 * Commerce solution
 *
 * @category    module
 * @version     0.6.13
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */

if (!$modx->hasPermission('exec_module')) {
    $modx->sendRedirect('index.php?a=106');
}

if (!is_array($modx->event->params)) {
    $modx->event->params = [];
}

if (!isset($_COOKIE['MODX_themeMode'])) {
    $_COOKIE['MODX_themeMode'] = '';
}

$manager = new Commerce\Module\Manager($modx, array_merge($modx->event->params, [
    'module_url' => 'index.php?a=112&id=' . $_GET['id'],
    'stay' => $stay,
]));

$route = filter_input(INPUT_GET, 'type', FILTER_VALIDATE_REGEXP, ['options' => [
    'regexp'  => '/^[a-z]+(:?\/[a-z-]+)*$/',
    'default' => '',
]]);

return $manager->processRoute($route);
