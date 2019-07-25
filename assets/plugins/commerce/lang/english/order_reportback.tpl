<p>
    Hello!<br>
    You left an order on the site [(site_url)].<br>
    Your order number is [+order.id+]
</p>

<h4>Buyer data:</h4>

<p>
    Delivery method: [+delivery_method_title+]<br>
    Payment method: [+payment_method_title+]
</p>

[+extra+]

<h4>Order list:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
