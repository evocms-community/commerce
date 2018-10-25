<p>
    Здравствуйте!<br>
    Вы оставили заказ на сайте [(site_url)]
</p>

<p>
    Состав заказа:
</p>

<table>
    [!Cart?
        &instance=`order`
        &tpl=`@FILE:order_report_items_row`
        &ownerTPL=`@FILE:order_report_items`
    !]
</table>
