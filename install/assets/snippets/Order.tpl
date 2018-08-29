//<?php
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

$lang = $modx->commerce->getUserLanguage('order');

$params = array_merge([
    'formid'                => 'order',
    'controller'            => 'OrderController',
    'dir'                   => 'assets/plugins/commerce/src/Controllers/',
    'parseDocumentSource'   => 1,
    'formTpl'               => 'order_form',
    'deliveryTpl'           => 'order_form_delivery',
    'deliveryRowTpl'        => 'order_form_delivery_row',
    'paymentsTpl'           => 'order_form_payments',
    'paymentsRowTpl'        => 'order_form_payments_row',
    'reportTpl'             => 'order_report',
    'to'                    => $modx->getConfig('client_email_recipients'),
    'ccSender'              => '1',
    'ccSenderField'         => 'email',
    'ccSenderTpl'           => 'order_reportback',
    'subject'               => $lang['order.subject'],
    'successTpl'            => $lang['order.success'],
    'rules'                 => [
        'name' => [
            'required' => 'Введите имя',
        ],
        'email' => [
            'required' => 'Введите email',
            'email' => 'Введите email правильно',
        ],
        'phone' => [
            'required' => 'Введите телефон',
            'matches' => [
                'params'  => '/^\+?[78]\s?\(\d{3}\)\s?\d{3}-\d\d-\d\d$/',
                'message' => 'Формат телефона неверный',
            ],
        ],
    ],
], $params);
    
return $modx->runSnippet('FormLister', $params);
