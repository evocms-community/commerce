<div class="commerce-cart minicart dropdown" data-commerce-cart="[+hash+]">
    <div class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        [%cart.caption%]<br>
        [+count+] [%cart.items_count%] [[PriceFormat? &price=`[+total+]` &convert=`0`]]
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
                    <td class="text-xs-right">[[PriceFormat? &price=`[+total+]` &convert=`0`]]</td>
                </tr>
            </tfoot>

            <tbody>
                [+dl.wrap+]
            </tbody>
        </table>

        <div class="dropdown-divider"></div>

        <a class="dropdown-item" href="[~[+settings.cart_page_id+]~]">[%cart.to_cart%]</a>
        <a class="dropdown-item" href="[~[+settings.order_page_id+]~]">[%cart.to_checkout%]</a>
    </div>
</div>
