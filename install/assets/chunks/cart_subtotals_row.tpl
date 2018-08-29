/**
 * cart_subtotals_row
 * 
 * Cart subtotals row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<tr>
	<td class="text-xs-right" colspan="4">[+title+]:</td>
	<td class="text-xs-right">[[PriceFormat? &price=`[+price+]`]]</td>
</tr>
