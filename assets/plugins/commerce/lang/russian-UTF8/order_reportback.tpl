<p>
    Здравствуйте!<br>
    Вы оставили заказ на сайте [(site_url)].<br>
    Номер вашего заказа: [+order.id+]
</p>

<h4>Данные покупателя:</h4>

<p>
    Способ доставки: [+delivery_method_title+]<br>
    Способ оплаты: [+payment_method_title+]
</p>

[+extra+]

<h4>Состав заказа:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
