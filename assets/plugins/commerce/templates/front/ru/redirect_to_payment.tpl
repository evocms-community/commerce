<p>Ваш заказ #[+order.id+] оформлен!</p>

[[if? &separator=`~` &is=`[+order.email+]~!empty` &then=`
    <p>
        На почту [+order.email+] выслано письмо со всеми данными о заказе и инструкцией по его оплате.<br>
        Если у вас возникнут проблемы с оплатой заказа, ссылка на оплату также есть в письме.
    </p>
`]]

<p>Следующее действие: [+redirect_text+]</p>

[+redirect_markup+]

<p>Нажмите кнопку для продолжения:</p>

<a href="#" class="btn btn-secondary" data-commerce-action="redirect-to-payment" data-redirect-link="[+redirect_link+]">Продолжить</a>
