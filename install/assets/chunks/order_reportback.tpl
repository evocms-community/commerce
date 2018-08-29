/**
 * order_reportback
 * 
 * Order user report template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<p>
	Здравствуйте!<br>
	Вы оставили заказ на сайте [(site_url)]
</p>

<p>
	Состав заказа:
</p>

<table>
	[!Cart?
		&tpl=`order_report_items_row`
		&ownerTPL=`order_report_items`
	!]
</table>
