<p>Uw bevel #[+order.id+] is bevestigd!</p>

[[if? &separator=`~` &is=`[+order.email+]~!empty` &then=`
    <p>
        Een e-mail met alle informatie over de bestelling en betaling link werd verzonden naar [+order.email+].
    </p>
`]]

<p>Volgende actie - [+redirect_text+]</p>

[+redirect_markup+]

<p>Druk op knop om verder te gaan:</p>

<a href="#" class="btn btn-secondary" data-commerce-action="redirect-to-payment" data-redirect-link="[+redirect_link+]">Blijven</a>
