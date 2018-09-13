/**
 * minicart_wrap
 * 
 * Minicart outer template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
[[if? &is=`[+count+]:>:0` &then=`
	<div class="commerce-cart minicart dropdown" data-commerce-cart="[+hash+]">
		<div class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			[%cart.%]<br>
			[+count+] [%cart.items_count%] [[PriceFormat? &price=`[+total+]`]]
		</div>
	
		<div class="dropdown-menu">
			<table class="table">
				<thead>
					<tr>
						<td>[%cart.item_title%]</td>
						<td>[%cart.count%]</td>
						<td class="text-xs-right">[%cart.item_price%]</td>
						<td class="text-xs-right">[%cart.item_summary%]</td>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<td colspan="3">[%cart.total%]:</td>
						<td class="text-xs-right">[[PriceFormat? &price=`[+total+]`]]</td>
					</tr>
				</tfoot>

				<tbody>
					[+dl.wrap+]
				</tbody>
			</table>

			<div class="dropdown-divider"></div>

			<a class="dropdown-item" href="[~4~]">[%cart.to_cart%]</a>
			<a class="dropdown-item" href="[~3~]">[%cart.to_checkout%]</a>
		</div>
	</div>
` &else=`
	<div class="commerce-cart minicart" data-commerce-cart="[+hash+]">
		[%cart.caption%]<br>
		[%cart.no_items%]
	</div>
`]]
