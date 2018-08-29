/**
 * order_report
 * 
 * Order admin report template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
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
			[+name.value+], [+email.value+], [+phone.value+]
		</td>
	</tr>
	
	<tr>
		<th colspan="5">
			Состав заказа:
		</th>
	</tr>
	
	[!Cart?
		&tpl=`order_report_items_row`
		&ownerTPL=`order_report_items`
	!]
</table>
