<tr data-id="[+id+]" data-commerce-row="[+row+]">
    <td>
        <img src="[[phpthumb? &input=`[+tv.image+]` &options=`w=80,h=80,zc=1,f=png`]]" class="img-fluid" alt="[+e.title+]">

    <td>
        <a href="[+url+]">[+name+]</a>

        <div class="small text-muted">
            [+options+]
        </div>

    <td>
        <input type="text" name="count" class="form-control" value="[+count+]" data-commerce-action="recount">
        <button type="button" data-commerce-action="decrease">[%cart.decrease%]</button>
        <button type="button" data-commerce-action="increase">[%cart.increase%]</button>
        <button type="button" data-commerce-action="remove">[%cart.remove%]</button>

    <td class="text-xs-right">
        [[PriceFormat? &price=`[+price+]` &convert=`0`]]

    <td class="text-xs-right">
        [[PriceFormat? &price=`[+total+]` &convert=`0`]]
</tr>
