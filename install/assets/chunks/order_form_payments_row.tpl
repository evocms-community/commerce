/**
 * order_form_payments_row
 * 
 * Order form payments row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */
<div>
	<label>
		<input type="radio" name="payment[method]" value="[+code+]"[[if? &is=`[+active+]:eq:1` &then=` checked`]]>
		[+title+]
	</label>
	
	[+markup+]
</div>
