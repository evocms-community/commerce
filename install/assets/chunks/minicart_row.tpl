/**
 * minicart_row
 * 
 * Minicart row template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<tr data-id="[+id+]" data-commerce-row="[+row+]">
	<td>[+name+]</td>
	<td>[+count+]</td>
	<td class="text-xs-right">[[PriceFormat? &price=`[+price+]`]]</td>
	<td class="text-xs-right">[[PriceFormat? &price=`[+total+]`]]</td>
</tr>
