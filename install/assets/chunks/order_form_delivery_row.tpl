/**
 * order_form_delivery_row
 * 
 * Order form delivery row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<div>
    <label>
        <input type="radio" name="delivery_method" value="[+code+]"[[if? &is=`[+active+]:eq:1` &then=` checked`]]>
        <input type="hidden" name="delivery_price_[+code+]" value="[+price+]">
        [+title+]
    </label>
    
    [+markup+]
</div>
