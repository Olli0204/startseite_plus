/* Startseite Plus – Newsletter-Deal-Preise unter dem Shop-Preis nachladen und Set-Konfigurator.
   Die Seite enthält für alle Besucher nur leere Platzhalter (cachebar); die Preise liefert die IO-Funktion
   startseitePlusNlDealPrices ausschließlich für Sitzungen mit freigeschaltetem Deal. Neue Platzhalter (z. B. nach dem
   Variantenwechsel auf der Artikelseite) werden per MutationObserver erkannt. */
(function () {
    'use strict';
    if (window.spNlDealPricesInit) {
        return;
    }
    window.spNlDealPricesInit = true;

    var SELECTOR = '.sp-nld-price[data-sp-nld-product]:not([data-sp-nld-done])';
    var cache = {};          // Artikel-ID -> Eintrag oder null (kein Deal-Preis für diese Sitzung)
    var labels = null;
    var pending = false;

    var ioUrl = function () {
        var el = document.getElementById('jtl-io-path');
        var base = el ? (el.getAttribute('data-path') || '') : '';
        return base.replace(/\/$/, '') + '/io';
    };

    var row = function (label, value, extraClass) {
        var div = document.createElement('div');
        div.className = 'sp-nld-price__row' + (extraClass ? ' ' + extraClass : '');
        var l = document.createElement('span');
        l.className = 'sp-nld-price__label';
        l.textContent = label;
        var v = document.createElement('strong');
        v.className = 'sp-nld-price__value';
        v.textContent = value;
        div.appendChild(l);
        div.appendChild(document.createTextNode(' '));
        div.appendChild(v);
        return div;
    };

    var render = function (box) {
        var entry = cache[box.getAttribute('data-sp-nld-product')] || cache[box.getAttribute('data-sp-nld-parent')] || null;
        box.setAttribute('data-sp-nld-done', '1');
        if (!entry || !labels) {
            return;
        }
        box.textContent = '';
        if (entry.price) {
            box.appendChild(row(labels.price, entry.price));
        }
        (entry.sets || []).forEach(function (set) {
            box.appendChild(row(set.label, set.price, 'sp-nld-price__row--set'));
        });
        box.hidden = !box.children.length;
    };

    var scan = function () {
        var boxes = Array.prototype.slice.call(document.querySelectorAll(SELECTOR));
        if (!boxes.length) {
            return;
        }
        var missing = [];
        boxes.forEach(function (box) {
            ['data-sp-nld-product', 'data-sp-nld-parent'].forEach(function (attr) {
                var id = box.getAttribute(attr);
                if (id && id !== '0' && !(id in cache) && missing.indexOf(id) === -1) {
                    missing.push(id);
                }
            });
        });
        if (!missing.length) {
            boxes.forEach(render);
            return;
        }
        if (pending) {
            return;
        }
        pending = true;
        fetch(ioUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: 'io=' + encodeURIComponent(JSON.stringify({name: 'startseitePlusNlDealPrices', params: [missing]}))
        }).then(function (response) {
            return response.json();
        }).then(function (data) {
            var prices = (data && data.prices) || {};
            labels = (data && data.labels) || labels;
            missing.forEach(function (id) {
                cache[id] = prices[id] || null;
            });
        }).catch(function (error) {
            console.error('startseite_plus Newsletter-Deal-Preise:', error);
            missing.forEach(function (id) {
                cache[id] = null;
            });
        }).then(function () {
            pending = false;
            scan();
        });
    };


    /* ---------------------------------------------------------------- Set-Konfigurator
       Platzhalter .sp-nld-sets (Deal-Seite: data-sp-nld-sets-deal, Artikelseite: data-sp-nld-sets-product/-parent).
       Karten liefert startseitePlusNlDealSets nur für freigeschaltete Sitzungen; "Set in den Warenkorb" holt vorher das
       CSRF-Token der eigenen Sitzung (startseitePlusDealToken), weil gecachte Seiten keins enthalten dürfen. */
    var SETS_SELECTOR = '.sp-nld-sets:not([data-sp-nld-done])';

    var io = function (name, params) {
        return fetch(ioUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: 'io=' + encodeURIComponent(JSON.stringify({name: name, params: params}))
        }).then(function (response) {
            return response.json();
        });
    };

    var el = function (tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) {
            node.className = cls;
        }
        if (text !== undefined && text !== null) {
            node.textContent = text;
        }
        return node;
    };

    var buildSlot = function (options, setLabels, current, onChange) {
        var slot = el('div', 'sp-nld-set__item');
        var state = {option: null, variant: 0};
        var media = slot.appendChild(el('a', 'sp-nld-set__media'));
        var img = media.appendChild(el('img'));
        img.alt = '';
        img.loading = 'lazy';
        var body = slot.appendChild(el('div', 'sp-nld-set__body'));
        var name = body.appendChild(el('a', 'sp-nld-set__name'));
        var price = body.appendChild(el('div', 'sp-nld-set__price'));
        var productSelect = null;
        if (options.length > 1) {
            productSelect = body.appendChild(el('select', 'custom-select sp-nld-set__select'));
            productSelect.setAttribute('aria-label', setLabels.product);
            options.forEach(function (option, i) {
                var o = el('option', null, option.name);
                o.value = String(i);
                productSelect.appendChild(o);
            });
        }
        var sizeSelect = body.appendChild(el('select', 'custom-select sp-nld-set__select'));
        sizeSelect.setAttribute('aria-label', setLabels.choose);

        var showOption = function (option) {
            state.option = option;
            state.variant = option.single ? option.id : 0;
            media.href = name.href = option.url || '#';
            img.src = option.image || '';
            media.hidden = !option.image;
            name.textContent = option.name;
            price.textContent = '';
            price.appendChild(el('strong', 'sp-nld-set__value', option.price));
            var instead = option.instead || (option.reduced ? option.shop : '');
            if (instead) {
                price.appendChild(document.createTextNode(' '));
                price.appendChild(el('s', 'sp-nld-set__instead', instead));
            }
            sizeSelect.textContent = '';
            sizeSelect.hidden = option.single;
            if (!option.single) {
                var first = el('option', null, setLabels.choose);
                first.value = '';
                sizeSelect.appendChild(first);
                option.variants.forEach(function (variant) {
                    var o = el('option', null, variant.label + (variant.available ? '' : ' – ' + setLabels.soldOut));
                    o.value = String(variant.id);
                    o.disabled = !variant.available;
                    sizeSelect.appendChild(o);
                });
                // Artikelseite einer Variante: diese Größe vorauswählen
                if (current && option.variants.some(function (v) { return v.id === current && v.available; })) {
                    sizeSelect.value = String(current);
                    state.variant = current;
                }
            }
            onChange();
        };
        if (productSelect) {
            productSelect.addEventListener('change', function () {
                showOption(options[+productSelect.value]);
            });
        }
        sizeSelect.addEventListener('change', function () {
            state.variant = +sizeSelect.value || 0;
            slot.classList.remove('is-missing');
            onChange();
        });
        // Artikelseite: passende Option (Vater oder Variante) vorauswählen
        var start = 0;
        options.forEach(function (option, i) {
            if (current && (option.id === current || option.variants.some(function (v) { return v.id === current; }))) {
                start = i;
            }
        });
        if (productSelect) {
            productSelect.value = String(start);
        }
        return {node: slot, state: state, show: function () { showOption(options[start]); }};
    };

    var renderSet = function (set, setLabels, current) {
        var card = el('section', 'sp-nld-set');
        var head = card.appendChild(el('div', 'sp-nld-set__head'));
        head.appendChild(el('span', 'sp-nld-set__kicker', setLabels.kicker));
        var title = head.appendChild(el('div', 'sp-nld-set__title', set.title));
        var total = title.appendChild(el('span', 'sp-nld-set__total'));
        var items = card.appendChild(el('div', 'sp-nld-set__items'));
        var actions = card.appendChild(el('div', 'sp-nld-set__actions'));
        var button = actions.appendChild(el('button', 'btn btn-primary sp-nld-set__add'));
        button.type = 'button';
        var msg = actions.appendChild(el('div', 'sp-nld-set__msg'));
        msg.setAttribute('role', 'alert');
        var partner, target;
        var update = function () {
            if (!partner || !target || !partner.state.option || !target.state.option) {
                return;
            }
            var sum = set.totals[partner.state.option.id + '-' + target.state.option.id] || '';
            total.textContent = sum ? ' – ' + setLabels.together + ' ' + sum : '';
            button.textContent = setLabels.add + (sum ? ' – ' + sum : '');
        };
        partner = buildSlot(set.partners, setLabels, current, update);
        target = buildSlot(set.targets, setLabels, current, update);
        items.appendChild(partner.node);
        items.appendChild(el('div', 'sp-nld-set__plus', '+'));
        items.appendChild(target.node);
        partner.show();
        target.show();

        button.addEventListener('click', function () {
            msg.textContent = '';
            var missing = false;
            [partner, target].forEach(function (slot) {
                if (!slot.state.variant) {
                    slot.node.classList.add('is-missing');
                    missing = true;
                }
            });
            if (missing) {
                msg.textContent = setLabels.missing;
                return;
            }
            button.disabled = true;
            io('startseitePlusDealToken', []).then(function (data) {
                if (!data || !data.token) {
                    throw new Error('token');
                }
                return io('startseitePlusNlDealSetAdd', [set.id, partner.state.variant, target.state.variant, data.token]);
            }).then(function (data) {
                if (data && data.ok && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                button.disabled = false;
                msg.textContent = (data && data.message) || setLabels.failed;
            }).catch(function (error) {
                console.error('startseite_plus Set-Konfigurator:', error);
                button.disabled = false;
                msg.textContent = setLabels.failed;
            });
        });
        return card;
    };

    var scanSets = function () {
        Array.prototype.forEach.call(document.querySelectorAll(SETS_SELECTOR), function (box) {
            box.setAttribute('data-sp-nld-done', '1');
            var deal = +box.getAttribute('data-sp-nld-sets-deal') || 0;
            var current = +box.getAttribute('data-sp-nld-sets-product') || 0;
            var context = deal
                ? {deal: deal}
                : {product: current, parent: +box.getAttribute('data-sp-nld-sets-parent') || 0};
            io('startseitePlusNlDealSets', [context]).then(function (data) {
                var list = (data && data.sets) || [];
                if (!list.length) {
                    return;
                }
                box.textContent = '';
                list.forEach(function (set) {
                    box.appendChild(renderSet(set, data.labels || {}, current));
                });
                box.hidden = false;
            }).catch(function (error) {
                console.error('startseite_plus Set-Konfigurator:', error);
            });
        });
    };

    var start = function () {
        scan();
        scanSets();
        if (window.MutationObserver) {
            new MutationObserver(function () {
                if (document.querySelector(SELECTOR)) {
                    scan();
                }
                if (document.querySelector(SETS_SELECTOR)) {
                    scanSets();
                }
            }).observe(document.body, {childList: true, subtree: true});
        }
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
