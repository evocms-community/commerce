/**
 * cart_row
 * 
 * Cart row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */
<tr data-id="[+id+]" data-commerce-row="[+row+]">
	<td>
		<img src="[[phpthumb? &input=`[+tv.image+]` &options=`w=80,h=80,zc=1,f=jpg`]]" class="img-fluid" alt="[+e.title+]">
		
	<td>
		<a href="[+url+]">[+name+]</a>
		
		<div class="small text-muted">
			[+options+]
		</div>
		
	<td>
		<input type="text" name="count" class="form-control" value="[+count+]" data-commerce-action="recount">
		<button type="button" data-commerce-action="decrease">Subtract</button>
		<button type="button" data-commerce-action="increase">Add</button>
		<button type="button" data-commerce-action="remove">Remove</button>
		
	<td class="text-xs-right">
		[+price_fmt+]
		
	<td class="text-xs-right">
		[+total_fmt+]
</tr>
