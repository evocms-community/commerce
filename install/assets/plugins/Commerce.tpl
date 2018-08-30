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
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!class_exists('Commerce\\Commerce')) {
    require_once MODX_BASE_PATH . 'assets/plugins/commerce/autoload.php';
}

$e = &$modx->Event;

if (in_array($e->name, ['OnWebPageInit', 'OnManagerPageInit', 'OnPageNotFound'])) {
    $modx->commerce = new \Commerce\Commerce($modx);

    if ($modx->Event->name == 'OnWebPageInit') {
        $modx->regClientScript('assets/plugins/commerce/js/commerce.js', [
            'version' => $modx->commerce->getVersion(),
        ]);
    }
}
        
if ($e->name == 'OnPageNotFound') {
    $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    switch ($url) {
        case 'commerce/action': {
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && is_string($_POST['action']) && preg_match('/^[a-z]+\/[a-z]+$/', $_POST['action'])) {
                try {
                    echo $modx->commerce->runAction($_POST['action'], isset($_POST['data']) ? $_POST['data'] : []);
                    exit;
                } catch (Exception $exception) {
                    $modx->logEvent(0, 3, $exception->getMessage());
                } catch (TypeError $exception) {
                    $modx->logEvent(0, 3, $exception->getMessage());
                }
            }

            break;
        }
            
        case 'commerce/cart/contents': {
            echo $modx->runSnippet('Cart', [
                'useSavedParams' => true,
                'hash' => $_POST['hash'],
            ]);
            exit;
        }
    }
}
