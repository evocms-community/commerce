<p>Вашая замова #[+order.id+] аформленая!</p>

[[if? &separator=`~` &is=`[+order.email+]~!empty` &then=`
    <p>
        На пошту [+order.email+] дасланы ліст з усімі зьвесткамі пра замову і інструкцыіяй для яе аплаты.<br>
        Калі ў вас ўзьнікнуць праблемы з аплатай замовы, спасылка для аплаты таксама ёсьць у лісьце
    </p>
`]]

<p>Наступнае дзеяньне: [+redirect_text+]</p>

[+redirect_markup+]

<p>Націсьніце кнопку, каб працягнуць:</p>

<a href="#" class="btn btn-secondary" data-commerce-action="redirect-to-payment" data-redirect-link="[+redirect_link+]">Працягнуць</a>
