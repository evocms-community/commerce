/**
 * order_report_items
 * 
 * Order cart contents outer template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<tr>
	<td colspan="2">Название</td>
	<td>Кол-во</td>
	<td class="text-xs-right">Стоимость</td>
	<td class="text-xs-right">Цена</td>
</tr>

[+dl.wrap+]

[+subtotals+]

<tr>
	<td class="text-xs-right" colspan="4">ИТОГО:</td>
	<td class="text-xs-right">[[PriceFormat? &price=`[+total+]`]]</td>
</tr>
