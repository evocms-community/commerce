/**
 * cart_row
 * 
 * Cart row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<tr data-id="[+id+]" data-commerce-row="[+row+]">
	<td>
		<img src="[[phpthumb? &input=`[+tv.image+]` &options=`w=80,h=80,zc=1,f=png`]]" class="img-fluid" alt="[+e.title+]">
		
	<td>
		<a href="[+url+]">[+name+]</a>
		
		<div class="small text-muted">
			[+options+]
		</div>
		
	<td>
		<input type="text" name="count" class="form-control" value="[+count+]" data-commerce-action="recount">
		<button type="button" data-commerce-action="decrease">[%cart.decrease%]</button>
		<button type="button" data-commerce-action="increase">[%cart.increase%]</button>
		<button type="button" data-commerce-action="remove">[%cart.remove%]</button>
		
	<td class="text-xs-right">
		[[PriceFormat? &price=`[+price+]`]]
		
	<td class="text-xs-right">
		[[PriceFormat? &price=`[+total+]`]]
</tr>
