;

var Commerce = {
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

            initiator.trigger(event + '.commerce', data);

            (function(event, data, initiator) {
                $.post('commerce/action', {
                    action: action,
                    data: data
                }, function(response) {
                    initiator.trigger(event + '-complete.commerce', {
                        response: response,
                        data: data,
                        initiator: initiator
                    });

                    initiator.trigger('action-complete.commerce', {
                        response: response,
                        action: action,
                        data: data,
                        initiator: initiator
                    });
                }, 'json');
            })(event, data, initiator);
        }
    },

    getCartsHashes: function() {
        var hashes = [];

        $('[data-commerce-cart]').each(function() {
            hashes.push($(this).attr('data-commerce-cart'));
        });

        return hashes;
    },

    updateCarts: function() {
        var $carts = $('[data-commerce-cart]'),
            hashes = this.getCartsHashes();

        if ($carts.length) {
            (function($carts) {
                $.post('commerce/cart/contents', {hashes: hashes}, function(response) {
                    if (response.status == 'success' && response.markup) {
                        $carts.each(function() {
                            var $cart = $(this),
                                hash  = $cart.attr('data-commerce-cart');

                            if (response.markup[hash]) {
                                $cart.replaceWith(response.markup[hash]);
                            }
                        });
                    }
                }, 'json');
            })($carts);
        }
    },

    updateOrderData: function($form) {
        var data = $form.serializeObject();

        data.hashes = {
            form: $form.attr('data-commerce-order'),
            carts: this.getCartsHashes()
        };

        $.post('commerce/data/update', data, function(response) {
            if (response.status == 'success') {
                if (response.markup.form) {
                    if (response.markup.form.delivery) {
                        $form.find('[data-commerce-deliveries]').replaceWith(response.markup.form.delivery);
                    }

                    if (response.markup.form.payments) {
                        $form.find('[data-commerce-payments]').replaceWith(response.markup.form.payments);
                    }
                }

                if (response.markup.carts) {
                    for (var hash in response.markup.carts) {
                        var $cart = $('[data-commerce-cart="' + hash + '"]');

                        if ($cart.length) {
                            $cart.replaceWith(response.markup.carts[hash]);
                        }
                    }
                }
            }
        }, 'json');
    },

    setCurrency: function(code) {
        $.post('commerce/currency/set', {code: code}, function(response) {
            if (response.status == 'success') {
                location.reload();
            }
        }, 'json');
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

    if (action == 'add') {
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
                    var count = parseInt($count.val()) || 0;
                    count += action == 'increase' ? 1 : -1;
                    count = Math.max(0, count);

                    if (!count) {
                        Commerce.action('cart/remove', {row: row, cart: cart});
                    } else {
                        $count.val(count);
                        Commerce.action('cart/update', {row: row, cart: cart, attributes: {count: count}});
                    }
                }

                break;
            }

            case 'remove': {
                Commerce.action('cart/remove', {row: row, cart: cart});
                break;
            }
        }
    }

    if (e.type == 'change') {
        switch (action) {
            case 'recount': {
                var count = parseInt($self.val());

                if (typeof count != 'NaN' && count >= 0) {
                    if (!count) {
                        Commerce.action('cart/remove', {row: row});
                    } else {
                        Commerce.action('cart/update', {row: row, attributes: {count: count}});
                    }
                }

                break;
            }
        }
    }
});

$(document).on('action-complete.commerce', function(e, data) {
    if (data.response.status == 'success') {
        Commerce.updateCarts();
    }
});

$(document).on('change', '[data-commerce-order]', function(e) {
    if (['delivery_method', 'payment_method'].indexOf(e.target.name) !== -1) {
        Commerce.updateOrderData($(this));
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
