<?php
/**
 * Order
 * 
 * Order form, FormLister based
 *
 * @category    snippet
 * @version     0.1.0
 * @author      mnoskov
 * @internal    @properties &default_payment=Default payment code;text; &default_delivery=Default delivery code;text;
 * @internal    @modx_category Commerce
 * @internal    @installset base
*/

if (!empty($modx->commerce)) {
    $lang = $modx->commerce->getUserLanguage('order');

    $params = array_merge([
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
        'reportTpl'             => $modx->commerce->getUserLanguageTemplate('order_report', true),
        'to'                    => $modx->getConfig('emailsender'),
        'ccSender'              => '1',
        'ccSenderField'         => 'email',
        'ccSenderTpl'           => $modx->commerce->getUserLanguageTemplate('order_reportback'),
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
    ], $params, [
        'controller' => 'Order',
        'dir'        => 'assets/plugins/commerce/src/Controllers/',
    ]);

    return $modx->runSnippet('FormLister', $params);
}
