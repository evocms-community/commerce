<?php

setlocale(LC_ALL, 'ru_RU.UTF-8');

return [
    'order.success' => '@CODE:<div>Спасибо за ваш заказ</div>',
    'order.subject' => '@CODE:Новый заказ на сайте [(site_name)]',
    'order.subject_status_changed' => '@CODE:Статус заказа #[+order_id+] изменен',
    'order.order_paid' => '@CODE:Заказ №[+order_id+] оплачен!',
    'order.status.new' => 'Новый',
    'order.status.processing' => 'В обработке',
    'order.status.paid' => 'Оплачен',
    'order.status.shipped' => 'Доставлен',
    'order.status.canceled' => 'Отменен',
    'order.status.complete' => 'Завершен',
    'order.status.pending' => 'Ожидание',
];
