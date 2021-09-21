<p>Order #[+order.id+] paid!</p>

<p>Payment amount: [[PriceFormat? &price=`[+amount+]` &convert=`0`]]</p>

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
