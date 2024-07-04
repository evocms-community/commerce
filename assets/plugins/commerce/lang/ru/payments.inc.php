<?php

setlocale(LC_ALL, 'ru_RU.UTF-8');

return [
    'payments.caption' => 'Способ оплаты',
    'payments.sberbank_title' => 'Оплата картой Visa, MasterCard (Sberbank)',
    'payments.yandexkassa_title' => 'Оплата через Яндекс.Кассу',
    'payments.paykeeper_title' => 'Оплата через Paykeeper',
    'payments.robokassa_title' => 'Оплата через Robokassa',
    'payments.on_delivery_title' => 'Оплата при вручении',
    'payments.error_empty_token' => 'Укажите токен в настройках плагина!',
    'payments.error_empty_shop_id' => 'Укажите идентификатор магазина в настройках плагина!',
    'payments.error_empty_pay_url' => 'Укажите адрес платежной формы в настройках плагина!',
    'payments.error_empty_secret' => 'Укажите секретный ключ в настройках плагина!',
    'payments.error_empty_password1' => 'Укажите пароль №1 в настройках плагина!',
    'payments.error_empty_password2' => 'Укажите пароль №2 в настройках плагина!',
    'payments.error_phone_required' => 'Номер телефона обязателен для совершения оплаты!',
    'payments.error_initialization' => '@CODE:Не удалось инициировать процесс оплаты!',
    'payments.payment_description' => '@CODE:Оплата заказа [+order_id+] на сайте [+site_name+]',
];
