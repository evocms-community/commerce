/**
 * order_form_delivery_row
 * 
 * Order form delivery row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */
<div>
	<label>
		<input type="radio" name="delivery[method]" value="[+code+]"[[if? &is=`[+active+]:eq:1` &then=` checked`]]>
		[+title+]
	</label>
	
	[+markup+]
</div>
