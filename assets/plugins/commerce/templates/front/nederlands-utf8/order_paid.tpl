<p>Bestelnummer #[+order_id+] betaald!</p>

<p>Bedrag van de uitkering: [[PriceFormat? &price=`[+amount+]` &convert=`0`]]</p>

<h4>Gegevens van de klant:</h4>

<p>
    [+order.name+], [+order.email+], [+order.phone+]<br>
    Bezorgmethode: [+order.fields.delivery_method_title+]<br>
    Betaalmethode: [+order.fields.payment_method_title+]
</p>

<h4>De opsomming van de bestelling:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
