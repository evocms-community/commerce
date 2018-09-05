//<?php
/**
 * Commerce
 *
 * Commerce solution
 *
 * @category    plugin
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound
 * @internal    @properties &payment_success_page_id=Page ID for redirect after successfull payment;text; &payment_failed_page_id=Page ID for redirect after payment error;text; &default_order_status=Default status ID;text; &status_id_after_payment=Status ID after payment;text;
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!class_exists('Commerce\\Commerce')) {
    require_once MODX_BASE_PATH . 'assets/plugins/commerce/autoload.php';
}

$e = &$modx->Event;

if (in_array($e->name, ['OnWebPageInit', 'OnManagerPageInit', 'OnPageNotFound'])) {
    if (empty($modx->commerce) || isset($modx->commerce) && !($modx->commerce instanceof Commerce\Commerce)) {
        $modx->commerce = new Commerce\Commerce($modx, $params);
    }

    if ($e->name == 'OnWebPageInit') {
        $modx->regClientScript('assets/plugins/commerce/js/commerce.js', [
            'version' => $modx->commerce->getVersion(),
        ]);
    }
}

if ($e->name == 'OnPageNotFound') {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    if (strpos($url, 'commerce') === 0) {
        $modx->commerce->processRoute($url);
    }
}
