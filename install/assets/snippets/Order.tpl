//<?php
/**
 * Order
 *
 * Order form, FormLister based
 *
 * @category    snippet
 * @version     0.6.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    $commerce  = ci()->commerce;
    $userLang  = $commerce->getUserLanguage('order');
    $adminLang = $commerce->getUserLanguage('order', true);
    $theme     = !empty($theme) ? $theme : '';

    $params = array_merge([
        'controller'            => 'Order',
        'dir'                   => 'assets/plugins/commerce/src/Controllers/',
        'formid'                => 'order',
        'parseDocumentSource'   => 1,
        'langDir'               => 'assets/plugins/commerce/lang/',
        'lexicon'               => 'common,delivery,payments,order',
        'templatePath'          => 'assets/plugins/commerce/templates/front/',
        'templateExtension'     => 'tpl',
        'formTpl'               => '@FILE:' . $theme . 'order_form',
        'deliveryTpl'           => '@FILE:' . $theme . 'order_form_delivery',
        'deliveryRowTpl'        => '@FILE:' . $theme . 'order_form_delivery_row',
        'paymentsTpl'           => '@FILE:' . $theme . 'order_form_payments',
        'paymentsRowTpl'        => '@FILE:' . $theme . 'order_form_payments_row',
        'reportTpl'             => $commerce->getUserLanguageTemplate('order_report', true),
        'to'                    => $commerce->getSetting('email', $modx->getConfig('emailsender')),
        'ccSender'              => '1',
        'ccSenderField'         => 'email',
        'ccSenderTpl'           => $commerce->getUserLanguageTemplate('order_reportback'),
        'subjectTpl'            => $adminLang['order.subject'],
        'successTpl'            => $userLang['order.success'],
        'rules'                 => [
            'name' => [
                'required' => $userLang['order.error.name_required'],
            ],
            'email' => [
                'required' => $userLang['order.error.email_required'],
                'email'    => $userLang['order.error.email_incorrect'],
            ],
            'phone' => [
                'required' => $userLang['order.error.phone_required'],
            ],
        ],
    ], $params);

    $params['form_hash'] = $commerce->storeParams($params);

    return $modx->runSnippet('FormLister', $params);
}
