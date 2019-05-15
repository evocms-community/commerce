<p>
    Hallo!<br>
    Je hebt een bestelling via [(site_url)] achtergelaten.<br>
    Uw volgnummer: [+order.id+]
</p>

<h4>Gegevens van de klant:</h4>

<p>
    Bezorgmethode: [+delivery_method_title+]<br>
    Betaalmethode: [+payment_method_title+]
</p>

[+extra+]

<h4>De opsomming van de bestelling:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
