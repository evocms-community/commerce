;

var Commerce = (function() {
    var CommerceEvent = function(target, name, data) {
        this.event = new CustomEvent(name, {
            cancelable: true,
            bubbles: true,
            detail: data
        });

        target.dispatchEvent(this.event);

        if (window.jQuery) {
            this.$event = $.Event(name);
            jQuery(target).trigger(this.$event, data);
        }

        this.isPrevented = function() {
            if (this.event.defaultPrevented || this.event.returnValue === false) {
                return true;
            }

            if (window.jQuery) {
                if (this.$event.isDefaultPrevented() || typeof this.$event.result != 'undefined' && this.$event.result === false) {
                    return true;
                }
            }

            return false;
        };

        return this;
    };

    function triggerEvent(target, name, data) {
        return new CommerceEvent(target, name, data);
    }

    /**
     * Convert object to FormData, based on object-to-formdata by Parmesh Krishen
     * @link https://github.com/therealparmesh/object-to-formdata
     * @license MIT
     */
    function objectToFormData(object) {
        function isArray(value) {
            return Array.isArray(value);
        }

        function isBlob(value) {
            return value &&
                typeof value.size === 'number' &&
                typeof value.type === 'string' &&
                typeof value.slice === 'function';
        }

        function isFile(value) {
            return isBlob(value) &&
                typeof value.name === 'string' &&
                (typeof value.lastModifiedDate === 'object' ||
                typeof value.lastModified === 'number');
        }

        function serialize(value, fd, prefix) {
            fd = fd || new FormData();

            if (value === undefined) {
                return fd;
            } else if (value === null) {
                fd.append(prefix, '');
            } else if (typeof value === 'boolean') {
                fd.append(prefix, value);
            } else if (isArray(value)) {
                if (value.length) {
                    value.forEach(function(val, index) {
                        serialize(val, fd, prefix + '[' + index + ']');
                    });
                } else {
                    fd.append(prefix + '[]', '');
                }
            } else if (value instanceof Date) {
                fd.append(prefix, value.toISOString());
            } else if (value === Object(value) && !isFile(value) && !isBlob(value)) {
                Object.keys(value).forEach(function(prop) {
                    var val = value[prop];

                    if (isArray(val)) {
                        while (prop.length > 2 && prop.lastIndexOf('[]') === prop.length - 2) {
                            prop = prop.substring(0, prop.length - 2);
                        }
                    }

                    var key = prefix ? prefix + '[' + prop + ']' : prop;

                    serialize(val, fd, key);
                });
            } else {
                fd.append(prefix, value);
            }

            return fd;
        }

        return serialize(object);
    }

    /**
     * Form serialization, based on jQuery serializeObject
     * @copyright 2014, macek <paulmacek@gmail.com>
     * @link https://github.com/macek/jquery-serialize-object
     * @license BSD
     */
    function serializeForm(form) {
        var self = this,
            json = {},
            push_counters = {},
            patterns = {
                "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[ *(?:\d*|[a-zA-Z0-9_]+) *\])*$/,
                "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
                "push":     /^$/,
                "fixed":    /^\d+$/,
                "named":    /^[a-zA-Z0-9_]+$/
            };

        this.build = function(base, key, value) {
            base[key] = value;
            return base;
        };

        this.push_counter = function(key) {
            if (push_counters[key] === undefined) {
                push_counters[key] = 0;
            }
            return push_counters[key]++;
        };

        Array.from(new FormData(form)).forEach(function(row) {
            // Skip invalid keys
            if (!patterns.validate.test(row[0])) {
                return;
            }

            var k,
                keys = row[0].match(patterns.key),
                merge = row[1],
                reverse_key = row[0];

            while ((k = keys.pop()) !== undefined) {
                // Adjust reverse_key
                reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

                // Push
                if (k.match(patterns.push)) {
                    merge = self.build([], self.push_counter(reverse_key), merge);
                }

                // Fixed
                else if (k.match(patterns.fixed)) {
                    merge = self.build([], k, merge);
                }

                // Named
                else if (k.match(patterns.named)) {
                    merge = self.build({}, k, merge);
                }
            }

            json = extendObject({}, json, merge);
        });

        return json;
    }

    function serializeDataAttributes(element) {
        var data = {};

        [].forEach.call(element.attributes, function(attr) {
            if (/^data-/.test(attr.name)) {
                var camelCaseName = attr.name.substr(5).replace(/-(.)/g, function ($0, $1) {
                    return $1.toUpperCase();
                });

                data[camelCaseName] = attr.value;
            }
        });

        return data;
    }

    function extendObject(out) {
        out = out || {};

        for (var i = 1; i < arguments.length; i++) {
            var obj = arguments[i], key;

            if (!obj) {
                continue;
            }

            if (obj instanceof Array == true) {
                for (key = 0; key < obj.length; key++) {
                    if (typeof obj[key] == 'undefined') {
                        continue;
                    }

                    if (typeof obj[key] === 'object') {
                        out[key] = extendObject(out[key], obj[key]);
                    } else {
                        out[key] = obj[key];
                    }
                }
            } else {
                for (key in obj) {
                    if (!obj.hasOwnProperty(key)) {
                        continue;
                    }

                    if (typeof obj[key] === 'object') {
                        out[key] = extendObject(out[key], obj[key]);
                    } else {
                        out[key] = obj[key];
                    }
                }
            }
        }

        return out;
    }

    function markupToElement(markup) {
        var template = document.createElement('template');
        template.innerHTML = markup.trim();
        return template.content.firstChild;
    }

    function getCartsHashes() {
        var carts = document.querySelectorAll('[data-commerce-cart]'),
            form  = document.querySelector('[data-commerce-order]'),
            hashes = {
                carts: []
            };

        Array.prototype.forEach.call(carts, function(cart) {
            hashes.carts.push(cart.getAttribute('data-commerce-cart'));
        });

        if (form) {
            hashes.form = form.getAttribute('data-commerce-order');
        }

        var event = triggerEvent(document, 'collect-hashes.commerce', {
            hashes: hashes
        });

        if (!event.isPrevented()) {
            return hashes;
        }

        return hashes;
    }

    function reloadMarkupFromResponse(markup) {
        if (typeof markup.carts == 'object') {
            for (var hash in markup.carts) {
                var carts = document.querySelectorAll('[data-commerce-cart="' + hash + '"]');

                if (carts.length) {
                    for (var i = 0; i < carts.length; i++) {
                        var newCart = markupToElement(markup.carts[hash]),
                            event = triggerEvent(carts[i], 'cart-reload.commerce', {newCart: newCart});

                        if (event.isPrevented()) {
                            return;
                        }

                        carts[i].replaceWith(newCart);
                        triggerEvent(newCart, 'cart-reloaded.commerce');
                    }
                }
            }
        }

        if (typeof markup.form == 'object') {
            var node;

            if (typeof markup.form.delivery != 'undefined') {
                node = document.querySelector('[data-commerce-deliveries]');

                if (node) {
                    node.innerHTML = markup.form.delivery;
                }
            }

            if (typeof markup.form.payments != 'undefined') {
                node = document.querySelector('[data-commerce-payments]');

                if (node) {
                    node.innerHTML = markup.form.payments;
                }
            }

            triggerEvent(document.querySelector('[data-commerce-order]'), 'form-reloaded.commerce');
        }
    }

    function handleActionEvent(e) {
        if (this.tagName == 'FORM' && e.type != 'submit') {
            return;
        }

        var self   = this,
            action = self.getAttribute('data-commerce-action'),
            row    = self.getAttribute('data-commerce-row'),
            data   = serializeDataAttributes(self),
            cartContainer = self.closest('[data-commerce-cart]'),
            parent;

        if (!row) {
            parent = self.closest('[data-commerce-row]');

            if (parent) {
                row = parent.getAttribute('data-commerce-row');
            }
        }

        var cart   = {
            instance: self.getAttribute('data-instance') || (cartContainer ? cartContainer.getAttribute('data-instance') : null) || 'products',
            hash: cartContainer ? cartContainer.getAttribute('data-commerce-cart') : null
        };

        if (action == 'redirect-to-payment') {
            e.preventDefault();
            var link = self.getAttribute('data-redirect-link');

            if (link && link != '') {
                location.href = link;
            } else {
                document.querySelector('form#payment_request').submit();
            }

            return;
        }

        if (action == 'add') {
            e.preventDefault();

            if (e.type == 'submit') {
                data = serializeForm(self);
            }

            data.cart = cart;

            if (data.batch && typeof data.batch == 'object') {
                for (var i in data.batch) {
                    if (!data.batch[i].id) {
                        delete data.batch[i];
                    }
                }

                Commerce.action('cart/addmultiple', data, self);
            } else {
                Commerce.action('cart/add', data, self);
            }

            return;
        }

        if (e.type == 'click') {
            e.preventDefault();

            switch (action) {
                case 'increase':
                case 'decrease': {
                    var rowField = cartContainer.querySelector('[data-commerce-row="' + row + '"]'),
                        countField = rowField.matches('input[name="count"]') ? rowField : rowField.querySelector('input[name="count"]');

                    if (countField) {
                        var min   = parseFloat(countField.getAttribute('data-min')),
                            max   = parseFloat(countField.getAttribute('data-max')),
                            count = parseFloat(countField.value) || 0,
                            diff  = data.count ? parseFloat(data.count) || 1 : 1;

                        count += action == 'increase' ? diff : -diff;
                        count = Math.max(0, count);

                        if (!count && (min !== min || min === 0)) {
                            Commerce.action('cart/remove', {row: row, cart: cart, data: data}, self);
                        } else if ((min !== min || count >= min) && (max !== max || count <= max )) {
                            count.value = count;
                            Commerce.action('cart/update', {row: row, cart: cart, data: data, attributes: {count: count}}, self);
                        }
                    }

                    return;
                }

                case 'remove': {
                    Commerce.action('cart/remove', {row: row, cart: cart, data: data}, self);
                    return;
                }

                case 'clean': {
                    Commerce.action('cart/clean', {cart: cart, data: data}, self);
                    return;
                }
            }
        }

        if (e.type == 'change') {
            switch (action) {
                case 'recount': {
                    var count = parseFloat(self.value) || 0,
                        min   = parseFloat(self.getAttribute('data-min')) || 0,
                        max   = parseFloat(self.getAttribute('data-max')) || count;

                    if (count >= 0) {
                        if (!count && !min) {
                            Commerce.action('cart/remove', {row: row, cart: cart, data: data}, self);
                        } else if (count >= min && count <= max) {
                            Commerce.action('cart/update', {row: row, cart: cart, data: data, attributes: {count: count}}, self);
                        }
                    }

                    return;
                }
            }
        }
    }

    function handleOrderChange(e) {
        if (['delivery_method', 'payment_method'].indexOf(e.target.name) !== -1) {
            Commerce.updateOrderData(this);
        }
    }

    function delegateEvent(events, selector, handler) {
        if (!(events instanceof Array)) {
            events = [events];
        }

        events.forEach(function(event) {
            document.addEventListener(event, function(e) {
                for (var target = e.target; target && target != this; target = target.parentNode) {
                    if (target.matches(selector)) {
                        handler.call(target, e);
                        break;
                    }
                }
            }, false);
        });
    }

    function request(url, data, callback) {
        if (!data) {
            data = {};
        }

        fetch(url, {
            method: 'POST',
            body: objectToFormData(data)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(response) {
            Commerce.serverResponse = response;
            callback.call(Commerce, response);
        });
    }

    if (window.jQuery) {
        jQuery(document).on('submit click change', '[data-commerce-action]', function(e) {
            handleActionEvent.call(this, e);
        });

        jQuery(document).on('change', '[data-commerce-order]', function(e) {
            handleOrderChange.call(this, e);
        });
    } else {
        delegateEvent(['submit', 'click', 'change'], '[data-commerce-action]', handleActionEvent);

        delegateEvent('change', '[data-commerce-order]', handleOrderChange);
    }

    delegateEvent('cart-remove-complete.commerce', '.comparison-table, .wishlist-table', function(e) {
        window.location.reload();
    });

    document.addEventListener('DOMContentLoaded', function() {
        if (Commerce.params.isCartPage) {
            Commerce.reloadCarts({order_completed: true});
        }
    });

    return {
        params: {},

        action: function(action, data, initiator) {
            if (window.jQuery && initiator instanceof jQuery) {
                initiator = initiator.get(0);
            }

            if (!initiator) {
                initiator = document.body;
            }

            triggerEvent(initiator, 'action-start.commerce', {
                action: action,
                data: data
            });

            var matches;

            if (matches = action.match(/^([a-z]+)\/([a-z]+)$/)) {
                var eventName = matches[1].charAt(0).toUpperCase() + matches[1].slice(1) + '-' + matches[2].charAt(0).toUpperCase() + matches[2].slice(1);
                eventName = eventName.toLowerCase();

                var event = triggerEvent(initiator, eventName + '.commerce', data);
                if (event.isPrevented()) {
                    return;
                }

                var hashes = getCartsHashes();

                (function(eventName, data, initiator) {
                    request(Commerce.params.path + 'commerce/action', {
                        action: action,
                        data:   data,
                        hashes: hashes
                    }, function(response) {
                        if (response.markup) {
                            reloadMarkupFromResponse(response.markup);
                        }

                        if (!document.body.contains(initiator)) {
                            var container = initiator;

                            while (container.parentElement) {
                                container = container.parentElement;
                            }

                            if (container.hasAttribute('data-commerce-cart')) {
                                initiator = document.querySelector('[data-commerce-cart="' + container.getAttribute('data-commerce-cart') + '"]');

                                if (!initiator) {
                                    initiator = document.body;
                                }
                            }
                        }

                        triggerEvent(initiator, eventName + '-complete.commerce', {
                            response: response,
                            data: data
                        });

                        triggerEvent(initiator, 'action-complete.commerce', {
                            response: response,
                            action: action,
                            data: data
                        });
                    });
                })(eventName, data, initiator);
            }
        },

        add: function(id, count, options) {
            options = options || {};
            var data = extendObject({}, options, {
                id: id,
                count: count || 1,
                cart: {
                    instance: options.instance || 'products'
                }
            });

            return Commerce.action('cart/add', data);
        },

        remove: function(row, options) {
            options = options || {};
            return Commerce.action('cart/remove', {
                row: row,
                cart: {
                    instance: options.instance || 'products'
                }
            });
        },

        clean: function(instance) {
            return Commerce.action('cart/clean', {
                cart: {
                    instance: instance || 'products'
                }
            });
        },

        reloadCarts: function(options) {
            if (typeof options == 'undefined') {
                options = {};
            }

            options.hashes = getCartsHashes();

            if (options.hashes.carts.length || options.hashes.form) {
                request(Commerce.params.path + 'commerce/cart/contents', options, function(response) {
                    if (response.status == 'success' && response.markup) {
                        reloadMarkupFromResponse(response.markup);
                        triggerEvent(document, 'carts-reloaded.commerce');
                    }
                });
            }
        },

        updateOrderData: function(form) {
            if (window.jQuery && form instanceof jQuery) {
                form = form.get(0);
            }

            var data = serializeForm(form);

            data.hashes = getCartsHashes();

            triggerEvent(form, 'order-data-update.commerce', {
                data: data
            });

            request(Commerce.params.path + 'commerce/data/update', data, function(response) {
                triggerEvent(form, 'order-data-updated.commerce', {
                    data: data,
                    response: response
                });

                if (response.status == 'success') {
                    if (response.markup) {
                        reloadMarkupFromResponse(response.markup);
                    }
                }
            });
        },

        setCurrency: function(code) {
            request(Commerce.params.path + 'commerce/currency/set', {code: code}, function(response) {
                if (response.status == 'success') {
                    location.reload();
                }
            });
        },

        formatPrice: function(price) {
            var input   = price,
                cleaned = price.toString().replace(',', '.').replace(' ', ''),
                number  = parseFloat(cleaned),
                minus   = '',
                curr    = Commerce.params.currency;

            if (number != cleaned) {
                return input;
            }

            if (number < 0) {
                minus  = '-';
                number = -number;
            }

            var integer = parseInt(number.toFixed(curr.decimals)) + '',
                left    = integer.split(/(?=(?:\d{3})+$)/).join(curr.thsep),
                right   = (curr.decimals ? curr.decsep + Math.abs(number - integer).toFixed(curr.decimals).replace(/-/, 0).slice(2) : '');

            return minus + curr.left + left + right + curr.right;
        },

        getServiceFunctions: function() {
            return {
                objectToFormData: objectToFormData,
                serializeForm: serializeForm,
                serializeDataAttributes: serializeDataAttributes,
                extendObject: extendObject,
                markupToElement: markupToElement,
                delegateEvent: delegateEvent,
                triggerEvent: triggerEvent,
                request: request,
            };
        }
    };
}());
