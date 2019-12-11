<p>Your order #[+order.id+] is confirmed!</p>

[[if? &separator=`~` &is=`[+order.email+]~!empty` &then=`
    <p>
        An email with all the information about the order and payment link was sent to [+order.email+].
    </p>
`]]

<p>Next action - [+redirect_text+]</p>

[+redirect_markup+]

<p>Press button to continue:</p>

<a href="#" class="btn btn-secondary" data-commerce-action="redirect-to-payment" data-redirect-link="[+redirect_link+]">Continue</a>
