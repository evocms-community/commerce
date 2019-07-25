<p>New order #[+order.id+] on [(site_url)]</p>

<h4>Buyer data:</h4>

<p>
    [+order.name+], [+order.email+], [+order.phone+]<br>
    Delivery method: [+order.fields.delivery_method_title+]<br>
    Payment method: [+order.fields.payment_method_title+]
</p>

<h4>Order list:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
