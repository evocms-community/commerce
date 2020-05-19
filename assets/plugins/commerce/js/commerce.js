;

var Commerce = {
    params: {},

    action: function(action, data, initiator) {
        if (typeof initiator == 'undefined') {
            initiator = $(document);
        }

        initiator.trigger('action-start.commerce', {
            action: action,
            data: data
        });

        if (matches = action.match(/^([a-z]+)\/([a-z]+)$/)) {
            var event = matches[1].charAt(0).toUpperCase() + matches[1].slice(1) + '-' + matches[2].charAt(0).toUpperCase() + matches[2].slice(1);
            event = event.toLowerCase();

            if (!initiator) {
                initiator = $(document);
            }

            var e = $.Event(event + '.commerce');
            initiator.trigger(e, data);

            if (e.isDefaultPrevented() || typeof e.result != 'undefined' && e.result === false) {
                return;
            }

            var hashes = this.getCartsHashes();

            (function(event, data, initiator) {
                $.post(Commerce.params.path + 'commerce/action', {
                    action: action,
                    data:   data,
                    hashes: hashes
                }, function(response) {
                    if (response.markup) {
                        Commerce.reloadMarkupFromResponse(response.markup);
                    }

                    if (!initiator.closest('body').length) {
                        var $container = initiator.parents().last();

                        if ($container.is('[data-commerce-cart]')) {
                            initiator = $('[data-commerce-cart="' + $container.attr('data-commerce-cart') + '"]').first();
                        }

                        if (!initiator.length) {
                            initiator = $(document);
                        }
                    }

                    initiator.trigger(event + '-complete.commerce', {
                        response: response,
                        data: data
                    });

                    initiator.trigger('action-complete.commerce', {
                        response: response,
                        action: action,
                        data: data
                    });
                }, 'json');
            })(event, data, initiator);
        }
    },

    getCartsHashes: function() {
        var hashes = {
            carts: []
        };

        $('[data-commerce-cart]').each(function() {
            hashes.carts.push($(this).attr('data-commerce-cart'));
        });

        var $form = $('[data-commerce-order]');
        if ($form.length) {
            hashes.form = $form.attr('data-commerce-order');
        }

        var event = $.Event('collect-hashes.commerce');
        $(document).trigger(event, {hashes: hashes});

        if (event.isDefaultPrevented() || typeof event.result != 'undefined' && event.result === false) {
            return [];
        }

        return hashes;
    },

    /**
     * TODO: REMOVE_V1
     */
    updateCarts: function(options) {
        console.info('Commerce.updateCarts() is deprecated and will be removed in v1.0.0. Use Commerce.reloadCarts() instead.');
        this.reloadCarts(options);
    },

    reloadCarts: function(options) {
        var hashes = this.getCartsHashes();

        if (typeof options == 'undefined') {
            options = {};
        }

        if (hashes.carts.length || hashes.form) {
            $.post(Commerce.params.path + 'commerce/cart/contents', $.extend(options, {hashes: hashes}), function(response) {
                if (response.status == 'success' && response.markup) {
                    Commerce.reloadMarkupFromResponse(response.markup);
                }
            }, 'json');
        }

        $(document).trigger('carts-reloaded.commerce');
    },

    reloadMarkupFromResponse: function(markup) {
        if (typeof markup.carts == 'object') {
            for (var hash in markup.carts) {
                var $cart = $('[data-commerce-cart="' + hash + '"]');

                if ($cart.length) {
                    var $newCart = $(markup.carts[hash]);
                    var event = $.Event('cart-reload.commerce');
                    $cart.trigger(event, {newCart: $newCart});

                    if (event.isDefaultPrevented() || typeof event.result != 'undefined' && event.result === false) {
                        return;
                    }

                    $cart.replaceWith($newCart);
                    $newCart.trigger('cart-reloaded.commerce');
                }
            }
        }

        if (typeof markup.form == 'object') {
            if (typeof markup.form.delivery != 'undefined') {
                $('[data-commerce-deliveries]').html(markup.form.delivery);
            }

            if (typeof markup.form.payments != 'undefined') {
                $('[data-commerce-payments]').html(markup.form.payments);
            }
        }
    },

    updateOrderData: function($form) {
        var data = $form.serializeObject();

        data.hashes = this.getCartsHashes();

        $form.trigger('order-data-update.commerce', {
            data: data
        });

        $.post(Commerce.params.path + 'commerce/data/update', data, function(response) {
            $form.trigger('order-data-updated.commerce', {
                data: data,
                response: response
            });

            if (response.status == 'success') {
                if (response.markup) {
                    Commerce.reloadMarkupFromResponse(response.markup);
                }
            }
        }, 'json');
    },

    setCurrency: function(code) {
        $.post(Commerce.params.path + 'commerce/currency/set', {code: code}, function(response) {
            if (response.status == 'success') {
                location.reload();
            }
        }, 'json');
    },

    formatPrice: function(price) {
        var input   = price,
            cleaned = price.toString().replace(',', '.').replace(' ', ''),
            number  = parseFloat(cleaned),
            minus   = '',
            o       = this.params.currency;

        if (number != cleaned) {
            return input;
        }

        if (number < 0) {
            minus  = '-';
            number = -number;
        }

        var integer = parseInt(number.toFixed(o.decimals)) + '',
            left    = integer.split(/(?=(?:\d{3})+$)/).join(o.thsep),
            right   = (o.decimals ? o.decsep + Math.abs(number - integer).toFixed(o.decimals).replace(/-/, 0).slice(2) : '');

        return minus + o.left + left + right + o.right;
    }
};

$(document).on('submit click change', '[data-commerce-action]', function(e) {
    if (e.currentTarget.tagName == 'FORM' && e.type != 'submit') {
        return;
    }

    var $self  = $(this),
        action = $self.attr('data-commerce-action'),
        row    = $self.attr('data-commerce-row') || $self.closest('[data-commerce-row]').attr('data-commerce-row'),
        data   = $self.serializeDataAttributes(),
        cart   = {
            instance: $self.attr('data-instance') || 'products',
            hash:     $self.closest('[data-commerce-cart]').attr('data-commerce-cart')
        };

    if (action == 'redirect-to-payment') {
        e.preventDefault();
        var link = $self.attr('data-redirect-link');

        if (link && link != '') {
            location.href = link;
        } else {
            $('form#payment_request').submit();
        }

        return;
    }

    if (action == 'add') {
        e.preventDefault();

        if (e.type == 'submit') {
            e.preventDefault();
            data = $self.serializeObject();
        }

        data.cart = cart;

        Commerce.action('cart/add', data, $self);
        return;
    }

    if (e.type == 'click') {
        e.preventDefault();

        switch (action) {
            case 'increase':
            case 'decrease': {
                var $row = $self.closest('[data-commerce-cart]').find('[data-commerce-row="' + row + '"]'),
                    $count = $row.filter('input[name="count"]');

                if (!$count.length) {
                    $count = $row.find('input[name="count"]');
                }

                if ($count.length == 1) {
                    var count = parseFloat($count.val()) || 0,
                        diff  = data.count ? parseFloat(data.count) || 1 : 1;

                    count += action == 'increase' ? diff : -diff;
                    count = Math.max(0, count);

                    if (!count) {
                        Commerce.action('cart/remove', {row: row, cart: cart, data: data}, $self);
                    } else {
                        $count.val(count);
                        Commerce.action('cart/update', {row: row, cart: cart, data: data, attributes: {count: count}}, $self);
                    }
                }

                break;
            }

            case 'remove': {
                Commerce.action('cart/remove', {row: row, cart: cart, data: data}, $self);
                break;
            }

            case 'clean': {
                Commerce.action('cart/clean', {cart: cart, data: data}, $self);
                break;
            }
        }
    }

    if (e.type == 'change') {
        switch (action) {
            case 'recount': {
                var count = parseFloat($self.val());

                if (typeof count != 'NaN' && count >= 0) {
                    if (!count) {
                        Commerce.action('cart/remove', {row: row, data: data}, $self);
                    } else {
                        Commerce.action('cart/update', {row: row, data: data, attributes: {count: count}}, $self);
                    }
                }

                break;
            }
        }
    }
});

$(document).on('change', '[data-commerce-order]', function(e) {
    if (['delivery_method', 'payment_method'].indexOf(e.target.name) !== -1) {
        Commerce.updateOrderData($(this));
    }
});

$(document).on('cart-remove-complete.commerce', '.comparison-table, .wishlist-table', function(e, data) {
    window.location.reload();
});

$(document).on('cart-clean-complete.commerce', function(e, data) {
    window.location.reload();
});

$(function() {
    if (Commerce.params.isCartPage) {
        Commerce.reloadCarts({order_completed: true});
    }
});

$.fn.serializeDataAttributes = function() {
    var data = {};

    this.each(function() {
        [].forEach.call(this.attributes, function(attr) {
            if (/^data-/.test(attr.name)) {
                var camelCaseName = attr.name.substr(5).replace(/-(.)/g, function ($0, $1) {
                    return $1.toUpperCase();
                });

                data[camelCaseName] = attr.value;
            }
        });
    });

    return data;
};


/**
 * jQuery serializeObject
 * @copyright 2014, macek <paulmacek@gmail.com>
 * @link https://github.com/macek/jquery-serialize-object
 * @license BSD
 * @version 2.5.0
 */
!function(e,i){if("function"==typeof define&&define.amd)define(["exports","jquery"],function(e,r){return i(e,r)});else if("undefined"!=typeof exports){var r=require("jquery");i(exports,r)}else i(e,e.jQuery||e.Zepto||e.ender||e.$)}(this,function(e,i){function r(e,r){function n(e,i,r){return e[i]=r,e}function a(e,i){for(var r,a=e.match(t.key);void 0!==(r=a.pop());)if(t.push.test(r)){var u=s(e.replace(/\[\]$/,""));i=n([],u,i)}else t.fixed.test(r)?i=n([],r,i):t.named.test(r)&&(i=n({},r,i));return i}function s(e){return void 0===h[e]&&(h[e]=0),h[e]++}function u(e){switch(i('[name="'+e.name+'"]',r).attr("type")){case"checkbox":return"on"===e.value?!0:e.value;default:return e.value}}function f(i){if(!t.validate.test(i.name))return this;var r=a(i.name,u(i));return l=e.extend(!0,l,r),this}function d(i){if(!e.isArray(i))throw new Error("formSerializer.addPairs expects an Array");for(var r=0,t=i.length;t>r;r++)this.addPair(i[r]);return this}function o(){return l}function c(){return JSON.stringify(o())}var l={},h={};this.addPair=f,this.addPairs=d,this.serialize=o,this.serializeJSON=c}var t={validate:/^[a-z_][a-z0-9_]*(?:\[(?:\d*|[a-z0-9_]+)\])*$/i,key:/[a-z0-9_]+|(?=\[\])/gi,push:/^$/,fixed:/^\d+$/,named:/^[a-z0-9_]+$/i};return r.patterns=t,r.serializeObject=function(){return new r(i,this).addPairs(this.serializeArray()).serialize()},r.serializeJSON=function(){return new r(i,this).addPairs(this.serializeArray()).serializeJSON()},"undefined"!=typeof i.fn&&(i.fn.serializeObject=r.serializeObject,i.fn.serializeJSON=r.serializeJSON),e.FormSerializer=r,r});
