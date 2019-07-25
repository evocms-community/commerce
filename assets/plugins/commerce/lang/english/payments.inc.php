<?php

setlocale(LC_ALL, 'en_US.UTF-8');

return [
    'payments.caption' => 'Payment method',
    'payments.sberbank_title' => 'Payment by Visa card, MasterCard',
    'payments.yandexkassa_title' => 'Payment via Yandex.Kassa',
    'payments.paykeeper_title' => 'Payment by Paykeeper',
    'payments.robokassa_title' => 'Payment by Robokassa',
    'payments.on_delivery_title' => 'Payment on delivery',
    'payments.error_empty_token' => 'Specify the token in the plugin settings!',
    'payments.error_empty_shop_id' => 'Specify the store ID in the plugin settings!',
    'payments.error_empty_pay_url' => 'Enter the address of the payment form in the plugin settings!',
    'payments.error_empty_secret' => 'Specify the secret key in the plugin settings!',
    'payments.error_empty_password1' => 'Enter password #1 in plugin settings!',
    'payments.error_empty_password2' => 'Enter password #2 in plugin settings!',
    'payments.error_phone_required' => 'A phone number is required for making a payment!',
    'payments.error_initialization' => '@CODE:Could not initiate payment process!',
    'payments.payment_description' => '@CODE:Payment order [+order_id+] on the site [+site_name+]',
];
