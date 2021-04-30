var popup;

(function($) {
    var parseTemplate = function(tpl, data) {
        for (var key in data) {
			var value = data[key];
			if (typeof value === 'string') value = value
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;');
            tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'),value);
        }

        return tpl;
    }

    $(function() {
        var $products         = $('#products'),
            $subtotals        = $('#subtotals'),
            productsCount     = $products.children('tr').length,
            subtotalsCount    = $subtotals.children('tr').length,
            productRowTpl     = $('#productRowTpl').html(),
            subtotalRowTpl    = $('#subtotalRowTpl').html(),
            $productsSelector = $('#product-select');

        $productsSelector.on('click', '.evo-popup-close', function(e) {
            $productsSelector.fadeOut(200);
        });

        $productsSelector.on('click', '.expand', function(e) {
            var $trigger = $(this),
                $item = $trigger.parent(),
                $list = $item.children('.children');

            if ($trigger.hasClass('expanded')) {
                $list.slideUp(200);
                $trigger.removeClass('expanded');
            } else {
                if (!$list.children().length) {
                    var params = {
                        type:      'orders/get-tree',
                        order_id:  $('[name="order_id"]').val(),
                        parent_id: $item.attr('data-id')
                    };

                    $.post('/commerce/module/action', params, function(response) {
                        if (response.status == 'success') {
                            $list.hide();
                            $(response.markup).appendTo($list);
                            $list.slideDown(200);
                            $trigger.addClass('expanded');
                        }
                    }, 'json');
                } else {
                    $list.slideDown(200);
                    $trigger.addClass('expanded');
                }
            }
        });

        $productsSelector.on('click', '.title', function(e) {
            var tpl = parseTemplate(productRowTpl, $.extend($(this).parent().data('values'), {
                iteration: ++productsCount,
            }));

            tpl = $(tpl).appendTo($products);
            $productsSelector.fadeOut(200);
            tpl.find('input, textarea, select').filter(':visible').first().focus();
        });

        $(document).on('click', '#add-product', function(e) {
            e.preventDefault();
            $productsSelector.fadeIn(200);
        });

        $(document).on('click', '#add-subtotal', function(e) {
            e.preventDefault();
            
            var tpl = parseTemplate(subtotalRowTpl, {
                iteration: ++subtotalsCount,
            });

            tpl = $(tpl).appendTo($subtotals);
            tpl.find('input, textarea, select').filter(':visible').first().focus();
        });

        $(document).on('click', '.remove-row', function(e) {
            e.preventDefault();
            $(this).closest('tr').remove();
        });
    });
})(jQuery);
