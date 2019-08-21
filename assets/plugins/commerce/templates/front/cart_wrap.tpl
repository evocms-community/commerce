<div data-commerce-cart="[+hash+]">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <td colspan="2">[%cart.item_title%]</td>
                    <td>[%cart.count%]</td>
                    <td class="text-xs-right">[%cart.item_price%]</td>
                    <td class="text-xs-right">[%cart.item_summary%]</td>
                </tr>
            </thead>

            <tfoot>
                [+subtotals+]

                <tr>
                    <td class="text-xs-right" colspan="4">[%cart.total%]:</td>
                    <td class="text-xs-right">[[PriceFormat? &price=`[+total+]` &convert=`0`]]</td>
                </tr>
            </tfoot>

            <tbody>
                [[if? &is=`[+count+]:>:0` &then=`
                    [+dl.wrap+]
                ` &else=`
                    <tr>
                        <td colspan="5" class="text-xs-center">
                            [%cart.no_items%]
                    </tr>
                `]]
            </tbody>
        </table>
    </div>

    [[if? &is=`[+count+]:>:0` &then=`
        <div class="text-xs-right">
            <p>
                <span class="btn btn-secondary" data-commerce-action="clean">[%cart.clean%]</span>
                <a href="[~3~]" class="btn btn-primary">[%cart.to_checkout%]</a>
            </p>
        </div>
    `]]
</div>
