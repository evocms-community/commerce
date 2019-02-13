;

$(function() {
    $('.comparison-table').on('cart-remove-complete.commerce', function(e, data) {
        window.location.reload();
    });
});
