<div>
    <label>
        <input type="radio" name="delivery_method" value="[+code+]"[[if? &is=`[+active+]:eq:1` &then=` checked`]]>
        <input type="hidden" name="delivery_price_[+code+]" value="[+price+]">
        [+title+]
    </label>

    [+markup+]
</div>
