/**
 * order_form
 * 
 * Order form template
 * 
 * @category	chunk
 * @internal    @modx_category Commerce
 * @internal    @installset base
 */
<form method="post">
	<input type="hidden" name="formid" value="order">
	
	<div class="form-group [+name.errorClass+][+name.requiredClass+]">
		<input type="text" class="form-control" placeholder="Name" name="name" value="[+name.value+]">
		[+name.error+]
	</div>
	
	<div class="form-group [+email.errorClass+][+email.requiredClass+]">
		<input type="text" class="form-control" placeholder="Email" name="email" value="[+email.value+]">
		[+email.error+]
	</div>
	
	<div class="form-group [+phone.errorClass+][+phone.requiredClass+]">
		<input type="text" class="form-control" placeholder="Phone" name="phone" value="[+phone.value+]">
		[+phone.error+]
	</div>
	
	[+delivery+]
	
	[+payments+]
	
	[+form.messages+]
	
	<button type="submit" class="btn btn-primary">Order</button>
</form>

