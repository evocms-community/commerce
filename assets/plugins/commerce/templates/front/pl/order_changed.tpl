<p>
    Witam!<br>
    Dane zamówienia #[+order.id+] zostałe zmienione!
</p>

<h4>Dane kupującego:</h4>

<p>
    [+order.name+], [+order.email+], [+order.phone+]<br>
    Rodzaje dostawy: [+order.fields.delivery_method_title+]<br>
    Rodzaje płatności: [+order.fields.payment_method_title+]
</p>

<h4>Lista zamówień:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
