## Commerce

<img src="https://img.shields.io/badge/CMS-%3E%3D1.4.6-green.svg"> <img src="https://img.shields.io/badge/PHP-%3E=7.1-green.svg?php=7.1">

E-commerce solution for Evolution CMS.

Documentation:

https://docs.evo.im/04_extras/commerce.html<br>
https://github.com/evocms-community/docs/tree/master/ru/04_%D0%9A%D0%BE%D0%BC%D0%BF%D0%BE%D0%BD%D0%B5%D0%BD%D1%82%D1%8B/Commerce

Payment methods:

<a href="https://github.com/mnoskov/commerce-payment-bill" target="_blank">Bill</a><br>
<a href="https://github.com/Pathologic/commerce-payment-bepaid" target="_blank">Bepaid</a><br>
<a href="https://github.com/mnoskov/commerce-payment-cloudpayments" target="_blank">CloudPayments</a><br>
<a href="https://github.com/Pathologic/commerce-payment-interkassa" target="_blank">Interkassa</a><br>
<a href="https://github.com/Pathologic/commerce-payment-lava" target="_blank">Lava</a><br>
<a href="https://github.com/dzhuryn/commerce-payment-liqpay" target="_blank">LiqPay</a><br>
<a href="https://github.com/Pathologic/commerce-payment-mollie" target="_blank">Mollie</a><br>
<a href="https://github.com/Pathologic/commerce-payment-nowpayments" target="_blank">Nowpayments</a><br>
<a href="https://github.com/mnoskov/commerce-payment-paymaster" target="_blank">Paymaster</a><br>
<a href="https://github.com/Pathologic/commerce-payment-payneteasy" target="_blank">PaynetEasy</a><br>
<a href="https://github.com/mnoskov/commerce-payment-paypal" target="_blank">PayPal</a><br>
<a href="https://github.com/mnoskov/commerce-payment-robokassa" target="_blank">Robokassa</a><br>
<a href="https://github.com/pathologic/commerce-payment-alfabank" target="_blank">Альфа-Банк</a><br>
<a href="https://github.com/sahar-08/commerce-payment-psbank" target="_blank">ПСБ</a><br>
<a href="https://github.com/mnoskov/commerce-payment-sberbank" target="_blank">Сбербанк</a><br>
<a href="https://github.com/mnoskov/commerce-payment-pokupay" target="_blank">Сбербанк кредит "Покупай со Сбербанком"</a><br>
<a href="https://github.com/DDAProduction/commerce-payment-stripe" target="_blank">Stripe</a><br>
<a href="https://github.com/DDAProduction/commerce-payment-stripe-embed" target="_blank">Stripe Embed</a><br>
<a href="https://github.com/autogen-travel/commerce-tinkoff" target="_blank">Tinkoff</a><br>
<a href="https://github.com/Pathologic/commerce-payment-uniteller" target="_blank">Uniteller</a><br>
<a href="https://github.com/dzhuryn/commerce-payment-ckassa" target="_blank">Центральная касса (нужен тестовый аккаунт для проверки)</a><br>
<a href="https://github.com/dzhuryn/commerce-payment-wayforpay" target="_blank">WayForPay</a><br>
<a href="https://github.com/mnoskov/commerce-payment-yookassa" target="_blank">ЮKassa (Яндекс.Касса)</a><br>
<a href="https://github.com/DDAProduction/commerce-payment-authorizenet" target="_blank">Authorize.net</a><br>
<a href="https://github.com/Pathologic/commerce-payment-webpay" target="_blank">Webpay</a><br>
<a href="https://github.com/Pathologic/commerce-payment-przelewy24" target="_blank">Przelewy24</a><br>
<a href="https://github.com/express-pay/modx_evo_2.0.x_commerce_erip" target="_blank">Экспресс Платежи (ЕРИП)</a><br>
<a href="https://github.com/Pathologic/commerce-payment-yandexsplit" target="_blank">Яндекс Сплит</a><br>

Delivery:

<a href="https://github.com/dzhuryn/commerce-delivery-novaposhta-pickup" target="_blank">Новая почта</a><br>
<a href="https://github.com/autogen-travel/commerce-cdek" target="_blank">СДЭК</a><br>
<a href="https://github.com/DDAProduction/commerce-delivery-goshippo" target="_blank">GoShippo</a><br>

Other:

<a href="https://github.com/mnoskov/commerce-options" target="_blank">Product Options</a><br>
<a href="https://github.com/webber12/CommerceCoupons" target="_blank">Coupons</a><br>
<a href="https://github.com/DDAProduction/evocms-commerce-coupons" target="_blank">Coupons (3.x)</a><br>
<a href="https://github.com/webber12/CommerceDiscounts" target="_blank">Discounts</a><br>
<a href="https://github.com/mnoskov/commerce-cbr-currency-updater" target="_blank">CBR Currency Updater</a><br>
<a href="https://github.com/mnoskov/commerce-dashboard" target="_blank">Dashboard</a><br>
<a href="https://github.com/Pathologic/commerce-arendakass" target="_blank">Отправка чеков в arendakass.ru</a><br>
<a href="https://github.com/Pathologic/commerce-dbcart" target="_blank">Database storage for commerce carts</a><br>
<a href="https://github.com/Pathologic/Invoice" target="_blank">A plugin to list order data in a separate page</a><br>
<a href="https://github.com/Pathologic/commerce-filesales" target="_blank">A plugin to sell files</a><br>
<a href="https://github.com/Pathologic/Promocodes" target="_blank">Промокоды</a><br>

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

<!-- batch adding -->
<form action="#" data-commerce-action="add">
    <input type="checkbox" name="batch[1][id]" value="1">
    <input type="hidden" name="batch[1][count]" value="1">
    <input type="checkbox" name="batch[2][id]" value="2">
    <input type="hidden" name="batch[2][count]" value="1">
    <button type="submit">Add to cart</button>
</form>
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
