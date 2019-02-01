<p>New order online [(site_url)]</p>

<h4>Buyer data:</h4>

<p>
    [+name.value+], [+email.value+], [+phone.value+]<br>
    Delivery method: [+delivery_method_title+]<br>
    Payment method: [+payment_method_title+]
</p>

<h4>Order list:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
