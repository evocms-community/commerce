<?php
/**
 * Order
 *
 * Order form, FormLister based
 *
 * @category    snippet
 * @version     0.4.0
 * @author      mnoskov
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (defined('COMMERCE_INITIALIZED')) {
    $commerce = ci()->commerce;
    $lang = $commerce->getUserLanguage('order');

    $params = array_merge([
        'controller'            => 'Order',
        'dir'                   => 'assets/plugins/commerce/src/Controllers/',
        'formid'                => 'order',
        'parseDocumentSource'   => 1,
        'langDir'               => 'assets/plugins/commerce/lang/',
        'lexicon'               => 'common,delivery,payments,order',
        'templatePath'          => 'assets/plugins/commerce/templates/front/',
        'templateExtension'     => 'tpl',
        'formTpl'               => '@FILE:order_form',
        'deliveryTpl'           => '@FILE:order_form_delivery',
        'deliveryRowTpl'        => '@FILE:order_form_delivery_row',
        'paymentsTpl'           => '@FILE:order_form_payments',
        'paymentsRowTpl'        => '@FILE:order_form_payments_row',
        'reportTpl'             => $commerce->getUserLanguageTemplate('order_report', true),
        'to'                    => $commerce->getSetting('email', $modx->getConfig('emailsender')),
        'ccSender'              => '1',
        'ccSenderField'         => 'email',
        'ccSenderTpl'           => $commerce->getUserLanguageTemplate('order_reportback'),
        'subjectTpl'            => $lang['order.subject'],
        'successTpl'            => $lang['order.success'],
        'rules'                 => [
            'name' => [
                'required' => $lang['order.error.name_required'],
            ],
            'email' => [
                'required' => $lang['order.error.email_required'],
                'email'    => $lang['order.error.email_incorrect'],
            ],
            'phone' => [
                'required' => $lang['order.error.phone_required'],
            ],
        ],
    ], $params);

    $params['form_hash'] = $commerce->storeParams($params);

    return $modx->runSnippet('FormLister', $params);
}
