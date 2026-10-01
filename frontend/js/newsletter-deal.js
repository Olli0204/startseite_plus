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

    /* Zeile "Label 150,00 €" (Label normal, Preis fett) */
    var line = function (parts) {
        var div = el('div', 'sp-nld-line');
        parts.forEach(function (part, i) {
            if (i > 0) {
                div.appendChild(el('span', 'sp-nld-line__sep', '·'));
            }
            div.appendChild(el('span', 'sp-nld-line__label', part[0] + ' '));
            div.appendChild(el('strong', 'sp-nld-line__value', part[1]));
        });
        return div;
    };

    /* Preisblock (.price_wrapper) zum Platzhalter: der Platzhalter steht direkt dahinter (ggf. nach link/script) */
    var priceWrapper = function (box) {
        var node = box.previousElementSibling;
        while (node && !(node.classList && node.classList.contains('price_wrapper'))) {
            node = node.previousElementSibling;
        }
        return node;
    };

    var noteArea = function (wrapper) {
        var note = wrapper.querySelector('.price-note');
        if (!note) {
            note = wrapper.appendChild(el('div', 'price-note'));
        }
        return note;
    };

    /* Darstellung "Zeile im Preisblock" */
    var renderLine = function (wrapper, single, sets, isDetail) {
        var note = noteArea(wrapper);
        if (isDetail) {
            if (single) {
                note.appendChild(line([[labels.price, single]]));
            }
            sets.forEach(function (set) {
                note.appendChild(line([[set.label + ':', set.price]]));
            });
            return;
        }
        // Kachel: eine kurze Zeile, Set nur als "im Set 100,00 €"
        var parts = [];
        if (single) {
            parts.push([labels.price, single]);
        }
        if (sets.length) {
            parts.push([labels.inSet, sets[0].price]);
        }
        note.appendChild(line(parts));
    };

    /* Darstellung "Als Hauptpreis": Newsletter-Preis in den großen Preis, Shop-Preis in "Alter Preis" */
    var renderMain = function (wrapper, single, sets, isDetail) {
        var priceEl = wrapper.querySelector('.price');
        var span = priceEl ? priceEl.querySelector('span') : null;
        var textNode = null;
        if (span) {
            for (var i = 0; i < span.childNodes.length; i++) {
                if (span.childNodes[i].nodeType === 3 && span.childNodes[i].nodeValue.trim() !== '') {
                    textNode = span.childNodes[i];
                    break;
                }
            }
        }
        if (!single || !textNode) {
            renderLine(wrapper, single, sets, isDetail);
            return;
        }
        var shopPrice = textNode.nodeValue.trim();
        textNode.nodeValue = ' ' + single + ' ';
        priceEl.classList.add('special-price', 'sp-nld-main');
        priceEl.insertAdjacentElement('afterend', el('span', 'sp-nld-tag', labels.badge));
        var note = noteArea(wrapper);
        var old = wrapper.querySelector('.old-price-value');
        if (old) {
            old.textContent = shopPrice;
        } else if (isDetail) {
            var detailOld = el('div', 'text-danger text-stroke text-nowrap-util sp-nld-old', labels.oldPrice + ': ');
            detailOld.appendChild(el('span', 'old-price-value', shopPrice));
            note.appendChild(detailOld);
        } else {
            var tileOld = el('div', 'instead-of old-price sp-nld-old');
            var small = tileOld.appendChild(el('small', 'text-muted-util', labels.oldPrice + ': '));
            small.appendChild(el('del', 'value old-price-value', shopPrice));
            note.appendChild(tileOld);
        }
        if (isDetail) {
            sets.forEach(function (set) {
                note.appendChild(line([[set.label + ':', set.price]]));
            });
        }
    };

    /* Darstellung "Marke am Produktbild" (Kachel); auf der Artikelseite Zeile im Preisblock */
    var renderBadge = function (box, wrapper, single, sets, isDetail) {
        var tile = box.closest('.productbox');
        var image = tile ? tile.querySelector('.productbox-image') : null;
        if (isDetail || !image) {
            renderLine(wrapper, single, sets, isDetail);
            return;
        }
        var text = single ? labels.badge + ' ' + single : labels.setBadge + ' ' + sets[0].price;
        image.appendChild(el('span', 'sp-nld-ribbon', text));
    };

    var render = function (box) {
        var entry = cache[box.getAttribute('data-sp-nld-product')] || cache[box.getAttribute('data-sp-nld-parent')] || null;
        box.setAttribute('data-sp-nld-done', '1');
        if (!entry || !labels) {
            return;
        }
        // nur zeigen, was günstiger als der aktuelle Shop-Preis ist (z. B. nicht 120 € bei Sale-Preis 118,95 €)
        var current = parseFloat(box.getAttribute('data-sp-nld-current') || '0') || 0;
        var cheaper = function (value) {
            return typeof value === 'number' && (current <= 0 || value < current - 0.005);
        };
        var single = entry.price && cheaper(entry.value) ? entry.price : '';
        var sets = (entry.sets || []).filter(function (set) {
            return cheaper(set.value);
        });
        if (!single && !sets.length) {
            return;
        }
        var wrapper = priceWrapper(box);
        if (!wrapper) {
            return;
        }
        var isDetail = box.classList.contains('sp-nld-price--detail');
        wrapper.classList.add('sp-nld-has-deal');
        if (entry.mode === 'price') {
            renderMain(wrapper, single, sets, isDetail);
        } else if (entry.mode === 'badge') {
            renderBadge(box, wrapper, single, sets, isDetail);
        } else {
            renderLine(wrapper, single, sets, isDetail);
        }
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
