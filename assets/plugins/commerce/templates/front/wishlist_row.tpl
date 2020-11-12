<tr>
    <td style="width: 6.5rem;">
        <img src="[[phpthumb? &input=`[+tv.image+]` &options=`w=80,h=80,zc=1,f=png`]]" class="img-fluid" alt="[+e.title+]">

    <td>
        <a href="[+url+]">[+pagetitle+]</a>

    <td class="text-xs-right">
        [!PriceFormat? &price=`[+tv.price+]`!]

    <td>
        <a href="#" class="btn btn-primary" data-commerce-action="add" data-instance="products" data-id="[+id+]">
            [%common.add_to_cart%]
        </a>

        <a href="#" class="btn btn-danger" data-commerce-action="remove" data-instance="wishlist" data-id="[+id+]">
            [%cart.remove%]
        </a>
</tr>
