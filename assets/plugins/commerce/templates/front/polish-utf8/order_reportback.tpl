<p>
    Witam!<br>
    Złożyłeś zamówienie na stronie [(site_url)].<br>
    Numer zamówienia: [+order.id+]
</p>

<h4>Dane kupującego:</h4>

<p>
    Rodzaje dostawy: [+delivery_method_title+]<br>
    Rodzaje płatności: [+payment_method_title+]
</p>

[+extra+]

<h4>Lista zamówień:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
