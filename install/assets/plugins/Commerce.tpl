//<?php
/**
 * Commerce
 *
 * Commerce solution
 *
 * @category    plugin
 * @version     0.11.1
 * @author      mnoskov
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound,OnWebPagePrerender,OnManagerMenuPrerender,OnSiteRefresh,OnLoadWebDocument
 * @internal    @properties &payment_success_page_id=Page ID for redirect after successfull payment;text; &payment_failed_page_id=Page ID for redirect after payment error;text;  &cart_page_id=Cart page ID;text;  &order_page_id=Order page ID;text; &status_id_after_payment=Status ID after payment;text; &product_templates=Product templates IDs;text; &title_field=Product title field name;text;pagetitle &price_field=Product price field name;text;price &status_notification=Chunk name for status change notification;text; &order_paid=Chunk name for order paid notification;text; &order_changed=Chunk name for order changed notification;text; &templates_path=Path to custom templates;text; &email=Email notifications recipient;text; &default_payment=Default payment code;text; &default_delivery=Default delivery code;text; &instant_redirect_to_payment=Redirect to payment after order process;list;Instant==1||Show prepare text==0;1 &redirect_to_payment_tpl=Chunk name for redirect prepare text;text; &payment_wait_time=Waiting time for order payment, days;text;3 &cart_controller=Class to use as a cart controller;text;CartDocLister &orders_display=The number of orders per page;text;10 &module_id=Commerce module ID (if renamed);text;
 * @internal    @modx_category Commerce
 * @internal    @disabled 1
 * @internal    @installset base
*/

if (!class_exists('Commerce\\Commerce')) {
    require_once MODX_BASE_PATH . 'assets/plugins/commerce/autoload.php';

    $ci = ci();

    $ci->set('modx', function($ci) use ($modx) {
        return $modx;
    });

    $ci->set('commerce', function($ci) use ($modx, $params) {
        return new Commerce\Commerce($modx, $params);
    });

    $ci->set('currency', function($ci) {
        return $ci->commerce->currency;
    });

    $ci->set('cache', function($ci) use ($modx) {
        return Commerce\Cache::getInstance();
    });

    $ci->set('carts', function($ci) use ($modx) {
        return Commerce\CartsManager::getManager($modx);
    });

    $ci->set('db', function($ci) {
        return $ci->modx->db;
    });

    $ci->set('tpl', function($ci) use ($modx) {
        require_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';
        return DLTemplate::getInstance($modx);
    });

    $ci->set('flash', function($ci) {
        return new Commerce\Module\FlashMessages;
    });

    $ci->set('statuses', function($ci) use ($modx) {
        return new Commerce\Statuses($modx);
    }
}

if ($modx instanceof \Illuminate\Container\Container) {
    if (!$modx->offsetExists('commerce')) {
        $modx->instance('commerce', ci()->commerce);
        $modx->commerce->initializeCommerce();
    }
} else if (!isset($modx->commerce) || isset($modx->commerce) && !($modx->commerce instanceof \Commerce\Commerce)) {
    $modx->commerce = ci()->commerce;
    $modx->commerce->initializeCommerce();
}

switch ($modx->event->name) {
    case 'OnWebPageInit': {
        $order_id = ci()->flash->get('last_order_id');

        if (!empty($order_id) && is_numeric($order_id)) {
            $modx->commerce->loadProcessor()->populateOrderPlaceholders($order_id);
        }

        $payment_id = ci()->flash->get('last_payment_id');

        if (!empty($payment_id) && is_numeric($payment_id)) {
            $modx->commerce->loadProcessor()->populatePaymentPlaceholders($payment_id);
        }

        break;
    }

    case 'OnLoadWebDocument': {
        if (!empty($params['product_templates'])) {
            $templates = array_map('trim', explode(',', $params['product_templates']));

            if (in_array($modx->documentObject['template'], $templates)) {
                $modx->commerce->populateProductPagePlaceholders();
            }
        }

        break;
    }

    case 'OnWebPagePrerender': {
        $modx->documentOutput = str_replace('</body>', $modx->commerce->populateClientScripts() . '</body>', $modx->documentOutput);
        return;
    }

    case 'OnManagerMenuPrerender': {
        if(!isset($params['module_id'])) {
            $moduleid = $modx->db->getValue($modx->db->select('id', $modx->getFullTablename('site_modules'), "name = 'Commerce'"));
        } else {
            $moduleid = $params['module_id'];
        }
        $url = 'index.php?a=112&id=' . $moduleid;
        $lang = $modx->commerce->getUserLanguage('menu');

        $params['menu'] = array_merge($params['menu'], [
            'commerce' => ['commerce', 'main', '<i class="fa fa-shopping-cart"></i>' . $lang['menu.commerce'], 'javascript:;', $lang['menu.commerce'], 'return false;', 'exec_module', 'main', 0, 90, ''],
            'orders'   => ['orders', 'commerce', '<i class="fa fa-list"></i>' . $lang['menu.orders'], $url . '&type=orders', $lang['menu.orders'], '', 'exec_module', 'main', 0, 10, ''],
            'statuses' => ['statuses', 'commerce', '<i class="fa fa-play-circle"></i>' . $lang['menu.statuses'], $url . '&type=statuses', $lang['menu.statuses'], '', 'exec_module', 'main', 0, 20, ''],
            'currency' => ['currency', 'commerce', '<i class="fa fa-usd"></i>' . $lang['menu.currency'], $url . '&type=currency', $lang['menu.currency'], '', 'exec_module', 'main', 0, 30, ''],
        ]);

        $modx->event->output(serialize($params['menu']));
        break;
    }

    case 'OnPageNotFound': {
        if (!empty($_GET['q']) && is_scalar($_GET['q']) && strpos($_GET['q'], 'commerce') === 0) {
            $modx->commerce->processRoute($_GET['q']);
        }
        break;
    }

    case 'OnSiteRefresh': {
        ci()->cache->clean();
        break;
    }
}
