## Commerce

<img src="https://img.shields.io/badge/CMS-%3E%3D1.4.6-green.svg"> <img src="https://img.shields.io/badge/PHP-%3E=7.1-green.svg?php=7.1">

E-commerce solution for Evolution CMS.

Payment methods:

<a href="https://github.com/mnoskov/commerce-payment-robokassa" target="_blank">Robokassa</a><br>
<a href="https://github.com/mnoskov/commerce-payment-paymaster" target="_blank">Paymaster</a><br>
<a href="https://github.com/mnoskov/commerce-payment-paypal" target="_blank">PayPal</a><br>
<a href="https://github.com/mnoskov/commerce-payment-cloudpayments" target="_blank">CloudPayments</a><br>
<a href="https://github.com/mnoskov/commerce-payment-sberbank" target="_blank">Sberbank</a><br>
<a href="https://github.com/mnoskov/commerce-payment-pokupay" target="_blank">Sberbank Credit</a><br>
<a href="https://github.com/mnoskov/commerce-payment-yandexkassa" target="_blank">Яндекс.Касса</a><br>
<a href="https://github.com/dzhuryn/commerce-payment-liqpay" target="_blank">LiqPay</a><br>
<a href="https://github.com/Pathologic/commerce-payment-payneteasy" target="_blank">PaynetEasy</a><br>
<a href="https://github.com/mnoskov/commerce-payment-bill" target="_blank">Bill</a><br>
<a href="https://github.com/DDAProduction/commerce-payment-stripe" target="_blank">Stripe</a><br>

Delivery:

<a href="https://github.com/dzhuryn/commerce-delivery-novaposhta-pickup" target="_blank">Новая почта</a><br>

Other:

<a href="https://github.com/mnoskov/commerce-options" target="_blank">Product Options</a><br>
<a href="https://github.com/webber12/CommerceCoupons" target="_blank">Coupons</a><br>
<a href="https://github.com/webber12/CommerceDiscounts" target="_blank">Discounts</a><br>
<a href="https://github.com/mnoskov/commerce-cbr-currency-updater" target="_blank">CBR Currency Updater</a><br>
<a href="https://github.com/mnoskov/commerce-dashboard" target="_blank">Dashboard</a><br>

Add product to cart:
```html
<form action="#" data-commerce-action="add">
    <input type="hidden" name="id" value="[*id*]">
    <input type="hidden" name="count" value="1">
    <input type="hidden" name="options[color]" value="White">
    <input type="hidden" name="options[services][]" value="Uplift">
    <input type="hidden" name="options[services][]" value="Assembling">
    <input type="hidden" name="meta[key]" value="value">
    <button type="submit">Add to cart</button>
</form>

<a href="#" data-commerce-action="add" data-id="[*id*]" data-count="2">Add to cart</a>

<a href="#" data-commerce-action="add" data-id="[*id*]" data-instance="wishlist">Add to wishlist</a>

<a href="#" data-commerce-action="remove" data-row="[*row*]">Remove from cart by row hash</a>

<a href="#" data-commerce-action="remove" data-id="[*id*]">Remove from cart by ID</a>
```

Show cart:
```php
[!Cart
    &instance=`products`
    &theme=``
    &tpl=`tpl`
    &optionsTpl=`optionsTpl`
    &ownerTPL=`ownerTPL`
    &subtotalsRowTpl=`subtotalsRowTpl`
    &subtotalsTpl=`subtotalsTpl`
!]
```

Show currency selection:
```php
[!CurrencySelect
    &tpl=`tpl`
    &activeTpl=`activeTpl`
    &outerTpl=`outerTpl`
!]
```

Show order form:
```php
[!Order
    &formTpl=`formTpl`
    &deliveryTpl=`deliveryTpl`
    &deliveryRowTpl=`deliveryRowTpl`
    &paymentsTpl=`paymentsTpl`
    &paymentsRowTpl=`paymentsRowTpl`
    &reportTpl=`reportTpl`
    &ccSenderTpl=`ccSenderTpl`
!]
```

Payments settings:

```
Process: POST /commerce/<payment_code>/payment-process
Success: POST /commerce/<payment_code>/payment-success
Failed:  POST /commerce/<payment_code>/payment-failed
```
