{*
    Startseite Plus – Artikel-Picker für OPC-Portlet-Eigenschaften.

    Suche nach Name, Artikelnummer oder GTIN über die Admin-IO-Funktion "startseitePlusProductSearch"
    (Deal/DealService::searchProducts()); ausgewählte Artikel lassen sich sortieren und entfernen.

    Konfiguration über die Property-Definition ($propdesc):
      max         (int)    höchstens so viele Artikel (Standard 4)
      emptyText   (string) Hinweis, solange nichts ausgewählt ist
      placeholder (string) Platzhalter des Suchfelds

    Gespeichert wird ein String mit Artikel-IDs: "101;102".
*}
{$max       = $propdesc.max|default:4}
{$emptyText = $propdesc.emptyText|default:'Noch keine Artikel ausgewählt.'}
{$propval   = $propval|default:''}

<div class="form-group sp-pp" id="{$propname}-pp" data-max="{$max}">
    <label for="{$propname}-pp-search"
            {if !empty($propdesc.desc)}
                data-toggle="tooltip" title="{$propdesc.desc|escape:'html'}" data-placement="auto"
            {/if}>
        {$propdesc.label}
        {if !empty($propdesc.desc)}<i class="fas fa-info-circle fa-fw"></i>{/if}
    </label>
    <input type="hidden" id="config-{$propname}" name="{$propname}" value="{$propval|escape:'html'}">

    <ul class="sp-pp-selected" id="{$propname}-pp-selected"></ul>
    <p class="sp-pp-empty" id="{$propname}-pp-empty">{$emptyText|escape:'html'}</p>

    <div class="sp-pp-searchbox">
        <i class="fas fa-search"></i>
        <input type="search" class="form-control" id="{$propname}-pp-search" autocomplete="off"
               placeholder="{$propdesc.placeholder|default:'Artikel suchen: Name, Artikelnummer oder GTIN'|escape:'html'}">
    </div>
    <p class="sp-pp-status" id="{$propname}-pp-status"></p>
    <ul class="sp-pp-results" id="{$propname}-pp-results"></ul>
</div>

<style>
    .sp-pp-selected, .sp-pp-results { list-style: none; margin: 0; padding: 0; }
    .sp-pp-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border: 1px solid #dfe3e8; border-radius: 4px; background: #fff; }
    .sp-pp-selected .sp-pp-item { margin: 0 0 4px; }
    .sp-pp-results .sp-pp-item { margin: 0 0 3px; cursor: pointer; }
    .sp-pp-results .sp-pp-item:hover { border-color: #5cbcf6; background: #f3faff; }
    .sp-pp-results .sp-pp-item.is-selected { opacity: .55; cursor: default; }
    .sp-pp-thumb { flex: 0 0 36px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 3px; background: #f5f7fa; color: #b0b6bd; overflow: hidden; }
    .sp-pp-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .sp-pp-text { flex: 1 1 auto; min-width: 0; line-height: 1.25; }
    .sp-pp-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
    .sp-pp-meta { font-size: 11px; color: #6c757d; }
    .sp-pp-badge { margin-left: 4px; padding: 0 4px; border-radius: 3px; background: #fff3cd; color: #7a5200; font-size: 10px; }
    .sp-pp-handle { cursor: move; color: #9aa3ad; }
    .sp-pp-remove { border: 0; background: none; color: #9aa3ad; padding: 0 4px; }
    .sp-pp-remove:hover { color: #c0392b; }
    .sp-pp-empty { margin: 0 0 6px; font-size: 12px; color: #6c757d; }
    .sp-pp-searchbox { position: relative; }
    .sp-pp-searchbox .fa-search { position: absolute; left: 12px; top: 50%; z-index: 2; transform: translateY(-50%); color: #9aa3ad; pointer-events: none; }
    /* Das OPC-Admin-CSS setzt das Padding von .form-control mit höherer Spezifität – daher !important */
    .sp-pp .sp-pp-searchbox input.form-control { padding-left: 36px !important; }
    .sp-pp-status { margin: 4px 0; font-size: 11px; color: #6c757d; }
    .sp-pp-status:empty { display: none; }
</style>

<script>
    (function () {
        var root     = document.getElementById('{$propname}-pp');
        var input    = document.getElementById('config-{$propname}');
        var search   = document.getElementById('{$propname}-pp-search');
        var selected = document.getElementById('{$propname}-pp-selected');
        var empty    = document.getElementById('{$propname}-pp-empty');
        var results  = document.getElementById('{$propname}-pp-results');
        var status   = document.getElementById('{$propname}-pp-status');
        var max      = parseInt(root.getAttribute('data-max'), 10) || 4;
        var items    = [];
        var timer    = null;
        var requestNo = 0;

        var call = function (query) {
            return window.opc.io.ioCall('startseitePlusProductSearch', query).then(function (res) {
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

        var buildItem = function (item, withHandle) {
            var li = el('li', 'sp-pp-item');
            li.setAttribute('data-id', item.id);
            if (withHandle) {
                li.appendChild(el('i', 'fas fa-grip-vertical sp-pp-handle'));
            }
            var thumb = el('span', 'sp-pp-thumb');
            if (item.thumb) {
                var img = el('img');
                img.src = item.thumb;
                img.alt = '';
                img.loading = 'lazy';
                thumb.appendChild(img);
            } else {
                thumb.appendChild(el('i', 'fas fa-box-open'));
            }
            li.appendChild(thumb);
            var text = el('span', 'sp-pp-text');
            text.appendChild(el('span', 'sp-pp-name', item.name));
            var meta = el('span', 'sp-pp-meta', 'Art.-Nr. ' + (item.artNr || '–'));
            if (item.variations) {
                meta.appendChild(el('span', 'sp-pp-badge', 'Variationen'));
            }
            text.appendChild(meta);
            li.appendChild(text);
            return li;
        };

        var save = function () {
            var value = items.map(function (item) { return item.id; }).join(';');
            input.value = value;
            input.setAttribute('value', value);
        };

        var renderSelected = function () {
            selected.innerHTML = '';
            items.forEach(function (item) {
                var li = buildItem(item, true);
                var remove = el('button', 'sp-pp-remove');
                remove.type = 'button';
                remove.title = 'Entfernen';
                remove.appendChild(el('i', 'fas fa-times'));
                remove.addEventListener('click', function () {
                    items = items.filter(function (other) { return other.id !== item.id; });
                    save();
                    renderSelected();
                    markResults();
                });
                li.appendChild(remove);
                selected.appendChild(li);
            });
            empty.style.display = items.length ? 'none' : '';
        };

        var markResults = function () {
            results.querySelectorAll('.sp-pp-item').forEach(function (li) {
                var id = parseInt(li.getAttribute('data-id'), 10);
                li.classList.toggle('is-selected', items.some(function (item) { return item.id === id; }));
            });
        };

        var renderResults = function (list) {
            results.innerHTML = '';
            list.forEach(function (item) {
                var li = buildItem(item, false);
                li.addEventListener('click', function () {
                    if (items.some(function (other) { return other.id === item.id; })) {
                        return;
                    }
                    if (items.length >= max) {
                        status.textContent = 'Höchstens ' + max + ' Artikel – entferne zuerst einen.';
                        return;
                    }
                    items.push(item);
                    save();
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
            if (term.length < 2) {
                results.innerHTML = '';
                status.textContent = term.length ? 'Mindestens 2 Zeichen eingeben.' : '';
                return;
            }
            status.textContent = 'Suche …';
            call(term).then(function (list) {
                if (no !== requestNo) { return; }
                status.textContent = list.length ? list.length + ' Treffer – zum Hinzufügen anklicken' : 'Keine Artikel gefunden.';
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
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(timer);
                runSearch();
            }
        });

        if (window.jQuery && jQuery.fn.sortable) {
            jQuery(selected).sortable({
                handle: '.sp-pp-handle',
                update: function () {
                    var order = Array.prototype.map.call(selected.children, function (li) {
                        return parseInt(li.getAttribute('data-id'), 10);
                    });
                    items.sort(function (a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
                    save();
                }
            });
        }

        var initial = (input.value || '').split(/[;,\s]+/).map(function (id) { return parseInt(id, 10); })
            .filter(function (id) { return id > 0; });
        renderSelected();
        if (initial.length) {
            status.textContent = 'Lade Artikel …';
            call(initial).then(function (list) {
                items = list;
                status.textContent = '';
                renderSelected();
            }).catch(function (error) {
                status.textContent = 'Artikel konnten nicht geladen werden: ' + error.message;
            });
        }
    })();
</script>
