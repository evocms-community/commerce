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
		<img src="[[phpthumb? &input=`[+tv.image+]` &options=`w=80,h=80,zc=1,f=jpg`]]" class="img-fluid" alt="[+e.title+]">
		
	<td>
		<a href="[+url+]">[+name+]</a>
		
		<div class="small text-muted">
			[+options+]
		</div>
		
	<td>
		[+count+]
		
	<td class="text-xs-right">
		[+price_fmt+]
		
	<td class="text-xs-right">
		[+total_fmt+]
</tr>
