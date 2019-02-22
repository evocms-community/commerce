<form method="post" data-commerce-order="[+form_hash+]">
    <input type="hidden" name="formid" value="order">

    <div class="form-group [+name.errorClass+][+name.requiredClass+]">
        <input type="text" class="form-control" placeholder="[%order.name_field%]" name="name" value="[+name.value+]">
        [+name.error+]
    </div>

    <div class="form-group [+email.errorClass+][+email.requiredClass+]">
        <input type="text" class="form-control" placeholder="[%order.email_field%]" name="email" value="[+email.value+]">
        [+email.error+]
    </div>

    <div class="form-group [+phone.errorClass+][+phone.requiredClass+]">
        <input type="text" class="form-control" placeholder="[%order.phone_field%]" name="phone" value="[+phone.value+]">
        [+phone.error+]
    </div>

    <div data-commerce-deliveries>
        [+delivery+]
    </div>

    <div data-commerce-payments>
        [+payments+]
    </div>

    [+form.messages+]

    <button type="submit" class="btn btn-primary">[%order.submit_btn%]</button>
</form>
