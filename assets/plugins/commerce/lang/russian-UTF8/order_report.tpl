<p>
    Новый заказ на сайте [(site_url)]
</p>

<table>
    <tr>
        <th colspan="5">
            Данные покупателя:
        </th>
    </tr>

    <tr>
        <td colspan="5">
            [+name.value+], [+email.value+], [+phone.value+]<br>
            Способ доставки: [+delivery_method_title+]<br>
            Способ оплаты: [+payment_method_title+]
        </td>
    </tr>

    <tr>
        <th colspan="5">
            Состав заказа:
        </th>
    </tr>

    [!Cart?
        &instance=`order`
        &tpl=`@FILE:order_report_items_row`
        &ownerTPL=`@FILE:order_report_items`
    !]
</table>
