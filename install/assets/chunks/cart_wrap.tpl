/**
 * cart_wrap
 * 
 * Cart outer template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @overwrite false
 * @internal    @installset base
 */
<div data-commerce-cart="[+hash+]">
	<div class="table-responsive">
		<table class="table">
			<thead>
				<tr>
					<td colspan="2">Название</td>
					<td>Кол-во</td>
					<td class="text-xs-right">Стоимость</td>
					<td class="text-xs-right">Цена</td>
				</tr>
			</thead>

			<tfoot>
				[+subtotals+]

				<tr>
					<td class="text-xs-right" colspan="4">ИТОГО:</td>
					<td class="text-xs-right">[+total_fmt+]</td>
				</tr>
			</tfoot>

			<tbody>
				[[if? &is=`[+count+]:>:0` &then=`
					[+dl.wrap+]
				` &else=`
					<tr>
						<td colspan="5" class="text-xs-center">
							Нет товаров
					</tr>
				`]]
			</tbody>
		</table>
	</div>

	[[if? &is=`[+count+]:>:0` &then=`
		<div class="text-xs-right">
			<p><a href="[~11~]">Оформить заказ</a></p>
		</div>
	`]]
</div>
