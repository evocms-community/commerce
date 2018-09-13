/**
 * order_report_items_row
 * 
 * Order cart contents row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<tr>
	<td>
		<a href="[+url+]">[+name+]</a>
		
		<div class="small text-muted">
			[+options+]
		</div>
		
	<td>
		[+count+]
		
	<td class="text-xs-right">
		[[PriceFormat? &price=`[+price+]`]]
		
	<td class="text-xs-right">
		[[PriceFormat? &price=`[+total+]`]]
</tr>
