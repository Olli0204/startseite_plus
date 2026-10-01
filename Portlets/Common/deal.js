/* Startseite Plus – Deal-Banner und Deal-Slides: Code kopieren, Artikel in den Warenkorb legen und Code einlösen */
(function () {
    'use strict';
    if (window.spDealInit) {
        return;
    }

    var copyText = function (text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var field = document.createElement('textarea');
            field.value = text;
            field.setAttribute('readonly', '');
            field.style.position = 'absolute';
            field.style.left = '-9999px';
            document.body.appendChild(field);
            field.select();
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(field);
            if (ok) {
                resolve();
            } else {
                reject();
            }
        });
    };

    var ioUrl = function () {
        var el = document.getElementById('jtl-io-path');
        var base = el ? (el.getAttribute('data-path') || '') : '';
        return base.replace(/\/$/, '') + '/io';
    };

    var showMessage = function (deal, text) {
        var box = deal ? deal.querySelector('.sp-deal__msg') : null;
        if (!box) {
            return;
        }
        box.textContent = text;
        box.hidden = text === '';
    };

    var onCopy = function (button) {
        var label = button.querySelector('.sp-deal__copy-label');
        var original = label ? label.textContent : '';
        copyText(button.getAttribute('data-sp-copy') || '').then(function () {
            button.classList.add('is-copied');
            if (label) {
                label.textContent = button.getAttribute('data-sp-copied') || original;
            }
            setTimeout(function () {
                button.classList.remove('is-copied');
                if (label) {
                    label.textContent = original;
                }
            }, 2000);
        }, function () {
            // Kopieren nicht möglich: Code zum manuellen Kopieren markieren
            var value = button.parentNode.querySelector('.sp-deal__code-value');
            if (value && window.getSelection) {
                var range = document.createRange();
                range.selectNodeContents(value);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
            }
        });
    };

    var ioCall = function (name, params, token) {
        var body = 'io=' + encodeURIComponent(JSON.stringify({name: name, params: params}));
        if (token) {
            body += '&jtl_token=' + encodeURIComponent(token);
        }
        return fetch(ioUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body
        }).then(function (response) {
            return response.json();
        });
    };

    /* Das CSRF-Token steht nicht im HTML: Seiten kommen aus dem LiteSpeed-Seitencache und würden sonst das
       Token einer fremden Sitzung enthalten. Deshalb vor jedem Klick das Token der eigenen Sitzung holen. */
    var onAdd = function (button) {
        var deal = button.closest('[data-sp-deal]');
        var ids = (button.getAttribute('data-sp-deal-add') || '').split(',').filter(Boolean);
        var code = button.getAttribute('data-sp-code') || '';
        button.classList.add('is-loading');
        showMessage(deal, '');
        ioCall('startseitePlusDealToken', []).then(function (data) {
            var token = data && typeof data.token === 'string' ? data.token : '';
            if (token === '') {
                throw new Error('kein Sitzungs-Token erhalten');
            }
            return ioCall('startseitePlusDeal', [ids, code, token], token);
        }).then(function (data) {
            if (data && data.ok && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            button.classList.remove('is-loading');
            if (!data || typeof data.ok === 'undefined') {
                // keine Antwort der Plugin-Funktion (z. B. IO-Fehler) – Details für die Fehlersuche
                console.error('startseite_plus Deal-Banner: unerwartete IO-Antwort', data);
            }
            showMessage(deal, (data && data.message) || 'Die Artikel konnten nicht in den Warenkorb gelegt werden.');
        }).catch(function (error) {
            console.error('startseite_plus Deal-Banner:', error);
            button.classList.remove('is-loading');
            showMessage(deal, 'Die Artikel konnten nicht in den Warenkorb gelegt werden.');
        });
    };

    /* Hero-Slider: Deal-Slide entfernen, sobald der Kupon abläuft (ein ausgeblendetes aktives Item
       würde das Bootstrap-Karussell anhalten), inkl. Punkt-Navigation; ohne Slides bleibt der Slider leer. */
    var removeDealSlide = function (item) {
        var carousel = item.closest('.carousel');
        if (!carousel || !item.parentNode) {
            return;
        }
        var items = Array.prototype.slice.call(carousel.querySelectorAll('.carousel-item'));
        var index = items.indexOf(item);
        var wasActive = item.classList.contains('active');
        var dots = carousel.querySelectorAll('.carousel-indicators li');
        item.parentNode.removeChild(item);
        if (dots[index]) {
            dots[index].parentNode.removeChild(dots[index]);
        }
        var rest = carousel.querySelectorAll('.carousel-item');
        if (!rest.length) {
            carousel.hidden = true;
            return;
        }
        Array.prototype.forEach.call(carousel.querySelectorAll('.carousel-indicators li'), function (dot, i) {
            dot.setAttribute('data-slide-to', i);
        });
        if (wasActive) {
            rest[0].classList.add('active');
            var firstDot = carousel.querySelector('.carousel-indicators li');
            if (firstDot) {
                firstDot.classList.add('active');
            }
        }
        if (rest.length < 2) {
            carousel.querySelectorAll('.carousel-indicators, .carousel-control-prev, .carousel-control-next')
                .forEach(function (el) { el.hidden = true; });
        }
    };

    var watchDealSlides = function () {
        document.querySelectorAll('.carousel-item[data-sp-deal-until]:not([data-sp-deal-watch])').forEach(function (item) {
            item.setAttribute('data-sp-deal-watch', '1');
            var until = parseInt(item.getAttribute('data-sp-deal-until'), 10) * 1000;
            if (!until) {
                return;
            }
            var wait = until - Date.now();
            if (wait <= 0) {
                removeDealSlide(item);
            } else if (wait < 2147483647) {
                setTimeout(function () { removeDealSlide(item); }, wait);
            }
        });
    };

    /* Karussell anhalten, solange man in einer Deal-Karte tippt oder per Tastatur darin ist */
    var pauseCarousel = function (event) {
        var card = event.target.closest && event.target.closest('.carousel .sp-deal');
        if (card && window.jQuery) {
            window.jQuery(card.closest('.carousel')).carousel('pause');
        }
    };

    window.spDealInit = true;
    document.addEventListener('focusin', pauseCarousel);
    document.addEventListener('touchstart', pauseCarousel, {passive: true});
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchDealSlides);
    } else {
        watchDealSlides();
    }
    document.addEventListener('click', function (event) {
        var copy = event.target.closest('.sp-deal__copy[data-sp-copy]');
        if (copy) {
            event.preventDefault();
            onCopy(copy);
            return;
        }
        var add = event.target.closest('[data-sp-deal-add]');
        if (add && !add.disabled) {
            event.preventDefault();
            onAdd(add);
        }
    });
})();
