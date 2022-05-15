<p>Twoje zamówienie #[+order.id+] zostało złożone!</p>

[[if? &separator=`~` &is=`[+order.email+]~!empty` &then=`
    <p>
        E-mail ze wszystkimi informacjami o zamówieniu i linkiem do płatności został wysłany na adres [+order.email+].
    </p>
`]]

<p>Następna akcja - [+redirect_text+]</p>

[+redirect_markup+]

<p>Kliknij przycisk, aby kontynuować:</p>

<a href="#" class="btn btn-secondary" data-commerce-action="redirect-to-payment" data-redirect-link="[+redirect_link+]">Kontynuować</a>
