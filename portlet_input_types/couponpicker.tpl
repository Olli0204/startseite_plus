{*
    Startseite Plus – Kupon-Picker für OPC-Portlet-Eigenschaften (Einzelauswahl).

    Suche nach Name oder Code über die Admin-IO-Funktion "startseitePlusCouponSearch"
    (Deal/DealService::searchCoupons()); ohne Suchtext werden die neuesten Kupons angeboten.
    Zeigt Rabatt, Gültigkeit, Status und Anzahl hinterlegter Artikel.

    Konfiguration über die Property-Definition ($propdesc):
      placeholder (string) Platzhalter des Suchfelds

    Gespeichert wird der Kupon-Code als String (kompatibel zum früheren Textfeld).
*}
{$propval = $propval|default:''}

<div class="form-group sp-pp sp-cp" id="{$propname}-cp">
    <label for="{$propname}-cp-search"
            {if !empty($propdesc.desc)}
                data-toggle="tooltip" title="{$propdesc.desc|escape:'html'}" data-placement="auto"
            {/if}>
        {$propdesc.label}
        {if !empty($propdesc.desc)}<i class="fas fa-info-circle fa-fw"></i>{/if}
    </label>
    <input type="hidden" id="config-{$propname}" name="{$propname}" value="{$propval|escape:'html'}">

    <ul class="sp-pp-selected" id="{$propname}-cp-selected"></ul>

    <div class="sp-pp-searchbox">
        <i class="fas fa-search"></i>
        <input type="search" class="form-control" id="{$propname}-cp-search" autocomplete="off"
               placeholder="{$propdesc.placeholder|default:'Kupon suchen: Name oder Code'|escape:'html'}">
    </div>
    <p class="sp-pp-status" id="{$propname}-cp-status"></p>
    <ul class="sp-pp-results" id="{$propname}-cp-results"></ul>
</div>

<style>
    .sp-pp-selected, .sp-pp-results { list-style: none; margin: 0; padding: 0; }
    .sp-pp-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border: 1px solid #dfe3e8; border-radius: 4px; background: #fff; }
    .sp-pp-selected .sp-pp-item { margin: 0 0 6px; }
    .sp-pp-results .sp-pp-item { margin: 0 0 3px; cursor: pointer; }
    .sp-pp-results .sp-pp-item:hover { border-color: #5cbcf6; background: #f3faff; }
    .sp-pp-results .sp-pp-item.is-selected { border-color: #5cbcf6; background: #f3faff; cursor: default; }
    .sp-pp-thumb { flex: 0 0 36px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 3px; background: #f5f7fa; color: #b0b6bd; overflow: hidden; }
    .sp-pp-text { flex: 1 1 auto; min-width: 0; line-height: 1.25; }
    .sp-pp-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
    .sp-pp-meta { font-size: 11px; color: #6c757d; }
    .sp-pp-remove { border: 0; background: none; color: #9aa3ad; padding: 0 4px; }
    .sp-pp-remove:hover { color: #c0392b; }
    .sp-pp-searchbox { position: relative; }
    .sp-pp-searchbox .fa-search { position: absolute; left: 12px; top: 50%; z-index: 2; transform: translateY(-50%); color: #9aa3ad; pointer-events: none; }
    /* Das OPC-Admin-CSS setzt das Padding von .form-control mit höherer Spezifität – daher !important */
    .sp-pp .sp-pp-searchbox input.form-control { padding-left: 36px !important; }
    .sp-pp-status { margin: 4px 0; font-size: 11px; color: #6c757d; }
    .sp-pp-status:empty { display: none; }
    .sp-cp .sp-pp-results .sp-pp-item.is-selected { opacity: 1; }
    .sp-cp .sp-pp-thumb { color: #e8912d; background: #fff4e6; font-size: 15px; }
    .sp-cp-code { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; letter-spacing: .05em; }
    .sp-cp-state { margin-left: 4px; padding: 0 5px; border-radius: 3px; font-size: 10px; white-space: nowrap; }
    .sp-cp-state--active { background: #e3f4e8; color: #1e6b34; }
    .sp-cp-state--inactive, .sp-cp-state--expired, .sp-cp-state--used { background: #fdecea; color: #a12a1f; }
    .sp-cp-state--upcoming { background: #fff3cd; color: #7a5200; }
    .sp-cp-warn { margin: -2px 0 6px; font-size: 11px; color: #a12a1f; }
</style>

<script>
    (function () {
        var input    = document.getElementById('config-{$propname}');
        var search   = document.getElementById('{$propname}-cp-search');
        var selected = document.getElementById('{$propname}-cp-selected');
        var results  = document.getElementById('{$propname}-cp-results');
        var status   = document.getElementById('{$propname}-cp-status');
        var current  = null;
        var timer    = null;
        var requestNo = 0;
        var states   = {
            active: 'aktiv',
            inactive: 'inaktiv',
            expired: 'abgelaufen',
            upcoming: 'noch nicht gültig',
            used: 'aufgebraucht'
        };

        var call = function (query, exact) {
            return window.opc.io.ioCall('startseitePlusCouponSearch', query, exact).then(function (res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch (e) { res = []; }
                }
                if (!Array.isArray(res)) {
                    throw new Error(res && res.error ? res.error.message : 'Unerwartete Antwort');
                }
                return res;
            });
        };

        var el = function (tag, cls, text) {
            var node = document.createElement(tag);
            if (cls) { node.className = cls; }
            if (text !== undefined) { node.textContent = text; }
            return node;
        };

        var buildItem = function (coupon) {
            var li = el('li', 'sp-pp-item');
            li.setAttribute('data-code', coupon.code);
            var icon = el('span', 'sp-pp-thumb');
            icon.appendChild(el('i', 'fas fa-ticket-alt'));
            li.appendChild(icon);
            var text = el('span', 'sp-pp-text');
            var name = el('span', 'sp-pp-name');
            name.appendChild(el('span', 'sp-cp-code', coupon.code));
            name.appendChild(document.createTextNode(' · ' + coupon.name));
            text.appendChild(name);
            var parts = [coupon.value + ' Rabatt'];
            parts.push(coupon.until ? 'bis ' + coupon.until : 'unbegrenzt');
            if (coupon.articles > 0) {
                parts.push(coupon.articles + ' Artikel hinterlegt');
            }
            var meta = el('span', 'sp-pp-meta', parts.join(' · '));
            meta.appendChild(el('span', 'sp-cp-state sp-cp-state--' + coupon.state, states[coupon.state] || coupon.state));
            text.appendChild(meta);
            li.appendChild(text);
            return li;
        };

        var save = function (code) {
            input.value = code;
            input.setAttribute('value', code);
        };

        var renderSelected = function () {
            selected.innerHTML = '';
            var next = selected.nextElementSibling;
            if (next && next.classList.contains('sp-cp-warn')) {
                next.remove();
            }
            if (current === null) {
                return;
            }
            var li = buildItem(current);
            var remove = el('button', 'sp-pp-remove');
            remove.type = 'button';
            remove.title = 'Auswahl entfernen';
            remove.appendChild(el('i', 'fas fa-times'));
            remove.addEventListener('click', function () {
                current = null;
                save('');
                renderSelected();
                markResults();
            });
            li.appendChild(remove);
            selected.appendChild(li);
            if (current.state !== 'active') {
                selected.insertAdjacentElement('afterend',
                    el('p', 'sp-cp-warn', 'Dieser Kupon ist ' + (states[current.state] || current.state)
                        + ' – der Banner wird im Shop nicht angezeigt.'));
            }
        };

        var markResults = function () {
            results.querySelectorAll('.sp-pp-item').forEach(function (li) {
                li.classList.toggle('is-selected', current !== null && li.getAttribute('data-code') === current.code);
            });
        };

        var renderResults = function (list) {
            results.innerHTML = '';
            list.forEach(function (coupon) {
                var li = buildItem(coupon);
                li.addEventListener('click', function () {
                    current = coupon;
                    save(coupon.code);
                    renderSelected();
                    markResults();
                });
                results.appendChild(li);
            });
            markResults();
        };

        var runSearch = function () {
            var term = search.value.trim();
            var no = ++requestNo;
            status.textContent = 'Suche …';
            call(term, false).then(function (list) {
                if (no !== requestNo) { return; }
                if (!list.length) {
                    status.textContent = term
                        ? 'Keine Kupons gefunden (Einmal- und Massenkupons sind ausgeblendet).'
                        : 'Keine passenden Kupons – Einmal- und Massenkupons sind ausgeblendet.';
                } else {
                    status.textContent = (term ? list.length + ' Treffer' : 'Neueste Kupons') + ' – zum Auswählen anklicken. Einmal- und Massenkupons sind ausgeblendet.';
                }
                renderResults(list);
            }).catch(function (error) {
                if (no !== requestNo) { return; }
                status.textContent = 'Suche fehlgeschlagen: ' + error.message;
            });
        };

        search.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(runSearch, 250);
        });
        search.addEventListener('focus', function () {
            if (!results.children.length) {
                runSearch();
            }
        });
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(timer);
                runSearch();
            }
        });

        var initial = (input.value || '').trim();
        if (initial !== '') {
            status.textContent = 'Lade Kupon …';
            call(initial, true).then(function (list) {
                status.textContent = '';
                if (list.length) {
                    current = list[0];
                    renderSelected();
                } else {
                    status.textContent = 'Gespeicherter Code „' + initial + '“ wurde nicht gefunden – bitte neu auswählen.';
                }
            }).catch(function (error) {
                status.textContent = 'Kupon konnte nicht geladen werden: ' + error.message;
            });
        }
    })();
</script>
