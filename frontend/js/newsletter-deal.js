/* Startseite Plus – Newsletter-Deal-Preise unter dem Shop-Preis nachladen.
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

    var start = function () {
        scan();
        if (window.MutationObserver) {
            new MutationObserver(function () {
                if (document.querySelector(SELECTOR)) {
                    scan();
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
