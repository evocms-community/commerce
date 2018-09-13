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
    <td>[%cart.item_title%]</td>
    <td>[%cart.count%]</td>
    <td class="text-xs-right">[%cart.item_price%]</td>
    <td class="text-xs-right">[%cart.item_summary%]</td>
</tr>

[+dl.wrap+]

[+subtotals+]

<tr>
    <td class="text-xs-right" colspan="3">[%cart.total%]:</td>
    <td class="text-xs-right">[[PriceFormat? &price=`[+total+]`]]</td>
</tr>

