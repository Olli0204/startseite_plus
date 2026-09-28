/* Startseite Plus – Deal-Banner: Code kopieren, Artikel in den Warenkorb legen und Code einlösen */
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

    var onAdd = function (button) {
        var deal = button.closest('[data-sp-deal]');
        var ids = (button.getAttribute('data-sp-deal-add') || '').split(',').filter(Boolean);
        var token = button.getAttribute('data-sp-token') || '';
        var request = {
            name: 'startseitePlusDeal',
            params: [ids, button.getAttribute('data-sp-code') || '', token]
        };
        button.classList.add('is-loading');
        showMessage(deal, '');
        fetch(ioUrl(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: 'io=' + encodeURIComponent(JSON.stringify(request)) + '&jtl_token=' + encodeURIComponent(token)
        }).then(function (response) {
            return response.json();
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

    window.spDealInit = true;
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
