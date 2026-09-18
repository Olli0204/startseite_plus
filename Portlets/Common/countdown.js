/* Startseite Plus – Countdown (Aktions-Banner und Artikeldetailseite) */
(function () {
    'use strict';
    if (window.spCountdownInit) {
        return;
    }
    window.spCountdownInit = function () {
        var pad = function (value) {
            return (value < 10 ? '0' : '') + value;
        };
        document.querySelectorAll('.sp-cd[data-sp-until]:not([data-sp-init])').forEach(function (el) {
            el.setAttribute('data-sp-init', '1');
            var until = new Date(el.getAttribute('data-sp-until')).getTime();
            if (isNaN(until)) {
                return;
            }
            var nums  = Object.create(null);
            var timer = null;
            el.querySelectorAll('[data-sp-unit]').forEach(function (node) {
                nums[node.getAttribute('data-sp-unit')] = node;
            });
            var tick = function () {
                var diff = until - Date.now();
                if (diff <= 0) {
                    clearInterval(timer);
                    var mode = el.getAttribute('data-sp-expired');
                    var host = el.closest('.sp-promo, .sp-cd-product');
                    if (mode === 'hide' && host) {
                        host.hidden = true;
                        return;
                    }
                    var units = el.querySelector('.sp-cd__units');
                    var label = el.querySelector('.sp-cd__label');
                    var text  = el.querySelector('.sp-cd__expired');
                    if (units) { units.hidden = true; }
                    if (label) { label.hidden = true; }
                    if (mode === 'keep' && host && host.classList.contains('sp-cd-product')) {
                        host.hidden = true;
                        return;
                    }
                    if (text && mode === 'text') { text.hidden = false; }
                    return;
                }
                var d = Math.floor(diff / 864e5);
                var h = Math.floor(diff % 864e5 / 36e5);
                var m = Math.floor(diff % 36e5 / 6e4);
                var s = Math.floor(diff % 6e4 / 1e3);
                if (nums.d) { nums.d.textContent = pad(d); }
                if (nums.h) { nums.h.textContent = pad(h); }
                if (nums.m) { nums.m.textContent = pad(m); }
                if (nums.s) { nums.s.textContent = pad(s); }
            };
            tick();
            timer = setInterval(tick, 1000);
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.spCountdownInit);
    } else {
        window.spCountdownInit();
    }
})();
