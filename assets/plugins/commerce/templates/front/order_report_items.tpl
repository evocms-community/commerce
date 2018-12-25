<table style="border-collapse: collapse;">
    <tr>
        <td style="border: 1px solid #ddd; padding: 5px 10px; background: #f6f6f6;">[%cart.item_title%]</td>
        <td style="border: 1px solid #ddd; padding: 5px 10px; background: #f6f6f6; text-align: center;">[%cart.count%]</td>
        <td style="border: 1px solid #ddd; padding: 5px 10px; background: #f6f6f6; text-align: right;">[%cart.item_price%]</td>
        <td style="border: 1px solid #ddd; padding: 5px 10px; background: #f6f6f6; text-align: right;">[%cart.item_summary%]</td>
    </tr>

    [+dl.wrap+]

    [+subtotals+]

    <tr>
        <td style="border: 1px solid #ddd; padding: 5px 10px;" colspan="3">[%cart.total%]:</td>
        <td style="border: 1px solid #ddd; padding: 5px 10px; text-align: right; white-space: nowrap;">[[PriceFormat? &price=`[+total+]` &convert=`0`]]</td>
    </tr>
</table>
