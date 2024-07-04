<p>
    Добры дзень!<br>
    Вы пакінулі замову на сайце [(site_url)].<br>
    Нумар вашай замовы: [+order.id+]
</p>

<h4>Зьвесткі пакупніка:</h4>

<p>
    Спосаб дастаўкі: [+delivery_method_title+]<br>
    Спосаб аплаты: [+payment_method_title+]
</p>

[+extra+]

<h4>Зьмесьціва замовы:</h4>

[!Cart?
    &instance=`order`
    &tpl=`@FILE:order_report_items_row`
    &ownerTPL=`@FILE:order_report_items`
    &subtotalsRowTpl=`@FILE:order_report_subtotals_row`
    &urlScheme=`full`
!]
