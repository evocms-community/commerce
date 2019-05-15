<p>Новый заказ #[+order.id+] на сайте [(site_url)]</p>

<h4>Данные покупателя:</h4>

<p>
    [+order.name+], [+order.email+], [+order.phone+]<br>
    Способ доставки: [+order.fields.delivery_method_title+]<br>
    Способ оплаты: [+order.fields.payment_method_title+]
</p>

<h4>Состав заказа:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
