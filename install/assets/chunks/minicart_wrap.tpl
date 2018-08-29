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
			Корзина<br>
			[+count+] товар(ов) на [[PriceFormat? &price=`[+total+]`]]
		</div>
	
		<div class="dropdown-menu">
			<table class="table">
				<thead>
					<tr>
						<td>Название</td>
						<td>Кол-во</td>
						<td class="text-xs-right">Стоимость</td>
						<td class="text-xs-right">Цена</td>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<td colspan="3">ИТОГО:</td>
						<td class="text-xs-right">[[PriceFormat? &price=`[+total+]`]]</td>
					</tr>
				</tfoot>

				<tbody>
					[+dl.wrap+]
				</tbody>
			</table>

			<div class="dropdown-divider"></div>

			<a class="dropdown-item" href="[~1~]">Перейти в корзину</a>
			<a class="dropdown-item" href="[~1~]">Оформить заказ</a>
		</div>
	</div>
` &else=`
	<div class="commerce-cart minicart" data-commerce-cart="[+hash+]">
		Корзина<br>
		Товаров нет
	</div>
`]]

