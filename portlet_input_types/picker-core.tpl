{*
    Startseite Plus – gemeinsamer Kern für Kupon- und Artikel-Picker (kein eigener Eingabetyp).
    Eingebunden von couponpicker.tpl, productpicker.tpl und repeater.tpl (Feldtypen "coupon" / "products").

    Markup eines Pickers:
      <div class="sp-picker" data-sp-picker="coupon|products|category" data-max="4" data-empty="…" data-placeholder="…">
          <input type="hidden" class="sp-picker-value" name="…" value="…">
          <div class="sp-picker-ui"></div>
      </div>
    window.spPicker.init(root, force) baut die Oberfläche in .sp-picker-ui auf (force = neu aufbauen, z. B. nach
    dem Kopieren eines Listeneintrags). Wert: Kupon-Code, Artikel-IDs "101;102" bzw. Kategorie-ID.
    Suche über die Admin-IO-Funktionen startseitePlusCouponSearch / startseitePlusProductSearch /
    startseitePlusCategorySearch.
*}
<style>
    .sp-pp-selected, .sp-pp-results { list-style: none; margin: 0; padding: 0; }
    .sp-pp-item { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border: 1px solid #dfe3e8; border-radius: 4px; background: #fff; }
    .sp-pp-selected .sp-pp-item { margin: 0 0 4px; }
    .sp-pp-results .sp-pp-item { margin: 0 0 3px; cursor: pointer; }
    .sp-pp-results .sp-pp-item:hover { border-color: #5cbcf6; background: #f3faff; }
    .sp-pp-results .sp-pp-item.is-selected { border-color: #5cbcf6; background: #f3faff; cursor: default; }
    .sp-picker--products .sp-pp-results .sp-pp-item.is-selected { opacity: .55; }
    .sp-pp-thumb { flex: 0 0 36px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 3px; background: #f5f7fa; color: #b0b6bd; overflow: hidden; }
    .sp-pp-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .sp-picker--coupon .sp-pp-thumb { color: #e8912d; background: #fff4e6; font-size: 15px; }
    .sp-picker--category .sp-pp-thumb { color: #1b5d8f; background: #e7f3fe; font-size: 15px; }
    .sp-pp-text { flex: 1 1 auto; min-width: 0; line-height: 1.25; text-align: left; }
    .sp-pp-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; }
    .sp-pp-meta { font-size: 11px; color: #6c757d; }
    .sp-pp-badge { margin-left: 4px; padding: 0 4px; border-radius: 3px; background: #fff3cd; color: #7a5200; font-size: 10px; }
    .sp-pp-badge--variant { background: #e7f3fe; color: #1b5d8f; }
    .sp-pp-results .sp-pp-item--child { margin-left: 18px; }
    .sp-pp-handle { cursor: move; color: #9aa3ad; }
    .sp-pp-remove { border: 0; background: none; color: #9aa3ad; padding: 0 4px; }
    .sp-pp-remove:hover { color: #c0392b; }
    .sp-pp-empty { margin: 0 0 6px; font-size: 12px; color: #6c757d; }
    .sp-pp-searchbox { position: relative; }
    .sp-pp-searchbox .fa-search { position: absolute; left: 12px; top: 50%; z-index: 2; transform: translateY(-50%); color: #9aa3ad; pointer-events: none; }
    /* Das OPC-Admin-CSS setzt das Padding von .form-control mit höherer Spezifität – daher !important */
    .sp-picker .sp-pp-searchbox input.form-control { padding-left: 36px !important; }
    .sp-pp-status { margin: 4px 0; font-size: 11px; color: #6c757d; }
    .sp-pp-status:empty { display: none; }
    .sp-cp-code { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; letter-spacing: .05em; }
    .sp-cp-state { margin-left: 4px; padding: 0 5px; border-radius: 3px; font-size: 10px; white-space: nowrap; }
    .sp-cp-state--active { background: #e3f4e8; color: #1e6b34; }
    .sp-cp-state--inactive, .sp-cp-state--expired, .sp-cp-state--used { background: #fdecea; color: #a12a1f; }
    .sp-cp-state--upcoming { background: #fff3cd; color: #7a5200; }
    .sp-cp-warn { margin: -2px 0 6px; font-size: 11px; color: #a12a1f; }
    .sp-cp-warn:empty { display: none; }
</style>

<script>
    (function () {
        if (window.spPicker) {
            return;
        }
        var STATES = {
            active: 'aktiv',
            inactive: 'inaktiv',
            expired: 'abgelaufen',
            upcoming: 'noch nicht gültig',
            used: 'aufgebraucht'
        };
        var HIDDEN_HINT = 'Einmal- und Massenkupons sind ausgeblendet.';

        var el = function (tag, cls, text) {
            var node = document.createElement(tag);
            if (cls) { node.className = cls; }
            if (text !== undefined) { node.textContent = text; }
            return node;
        };

        var call = function (name, args) {
            return window.opc.io.ioCall.apply(window.opc.io, [name].concat(args)).then(function (res) {
                if (typeof res === 'string') {
                    try { res = JSON.parse(res); } catch (e) { res = []; }
                }
                if (!Array.isArray(res)) {
                    throw new Error(res && res.error ? res.error.message : 'Unerwartete Antwort');
                }
                return res;
            });
        };

        var productItem = function (item, withHandle) {
            var li = el('li', 'sp-pp-item');
            li.setAttribute('data-key', item.id);
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
            var meta = el('span', 'sp-pp-meta', 'Art.-Nr. ' + (item.artNr || '–') + (item.variant ? ' · ' + item.variant : ''));
            if (item.variations) {
                meta.appendChild(el('span', 'sp-pp-badge', 'Vaterartikel – Variante wählen'));
            } else if (item.child) {
                meta.appendChild(el('span', 'sp-pp-badge sp-pp-badge--variant', 'Variante'));
            }
            if (item.child) {
                li.classList.add('sp-pp-item--child');
            }
            text.appendChild(meta);
            li.appendChild(text);
            return li;
        };

        var couponItem = function (coupon) {
            var li = el('li', 'sp-pp-item');
            li.setAttribute('data-key', coupon.code);
            var icon = el('span', 'sp-pp-thumb');
            icon.appendChild(el('i', 'fas fa-ticket-alt'));
            li.appendChild(icon);
            var text = el('span', 'sp-pp-text');
            var name = el('span', 'sp-pp-name');
            name.appendChild(el('span', 'sp-cp-code', coupon.code));
            if (coupon.name && coupon.name.toLowerCase() !== coupon.code.toLowerCase()) {
                name.appendChild(document.createTextNode(' · ' + coupon.name));
            }
            text.appendChild(name);
            var parts = [coupon.value + ' Rabatt', coupon.until ? 'bis ' + coupon.until : 'unbegrenzt'];
            if (coupon.articles > 0) {
                parts.push(coupon.articles + ' Artikel hinterlegt');
            }
            var meta = el('span', 'sp-pp-meta', parts.join(' · '));
            meta.appendChild(el('span', 'sp-cp-state sp-cp-state--' + coupon.state, STATES[coupon.state] || coupon.state));
            text.appendChild(meta);
            li.appendChild(text);
            return li;
        };

        var categoryItem = function (cat) {
            var li = el('li', 'sp-pp-item');
            li.setAttribute('data-key', cat.id);
            var icon = el('span', 'sp-pp-thumb');
            icon.appendChild(el('i', 'fas fa-folder-open'));
            li.appendChild(icon);
            var text = el('span', 'sp-pp-text');
            text.appendChild(el('span', 'sp-pp-name', cat.name));
            text.appendChild(el('span', 'sp-pp-meta', (cat.path ? cat.path + ' › ' : '') + cat.name + (cat.seo ? '  ·  /' + cat.seo : '')));
            li.appendChild(text);
            return li;
        };

        var init = function (root, force) {
            if (!root || (root.hasAttribute('data-sp-picker-ready') && !force)) {
                return;
            }
            root.setAttribute('data-sp-picker-ready', '1');
            var mode    = root.getAttribute('data-sp-picker');
            mode = mode === 'coupon' || mode === 'category' ? mode : 'products';
            var isCoupon = mode === 'coupon';
            var isCategory = mode === 'category';
            var single  = isCoupon || isCategory;
            var max     = single ? 1 : (parseInt(root.getAttribute('data-max'), 10) || 4);
            var buildItem = function (item, withHandle) {
                if (isCoupon) { return couponItem(item); }
                if (isCategory) { return categoryItem(item); }
                return productItem(item, withHandle);
            };
            var input   = root.querySelector('.sp-picker-value');
            var ui      = root.querySelector('.sp-picker-ui');
            var items   = [];
            var timer   = null;
            var requestNo = 0;
            root.classList.add('sp-picker--' + mode);
            ui.innerHTML = '';

            var selected = ui.appendChild(el('ul', 'sp-pp-selected'));
            var warn     = ui.appendChild(el('p', 'sp-cp-warn'));
            var empty    = ui.appendChild(el('p', 'sp-pp-empty', root.getAttribute('data-empty') || ''));
            var box      = ui.appendChild(el('div', 'sp-pp-searchbox'));
            box.appendChild(el('i', 'fas fa-search'));
            var search   = box.appendChild(el('input', 'form-control'));
            search.type = 'search';
            search.autocomplete = 'off';
            search.placeholder = root.getAttribute('data-placeholder')
                || (isCoupon ? 'Kupon suchen: Name oder Code'
                    : (isCategory ? 'Kategorie suchen: Name' : 'Artikel suchen: Name, Artikelnummer oder GTIN'));
            var status   = ui.appendChild(el('p', 'sp-pp-status'));
            var results  = ui.appendChild(el('ul', 'sp-pp-results'));
            var keyOf    = function (item) { return isCoupon ? item.code : String(item.id); };
            var searchFn = isCoupon ? 'startseitePlusCouponSearch'
                : (isCategory ? 'startseitePlusCategorySearch' : 'startseitePlusProductSearch');

            var save = function () {
                var value = items.map(keyOf).join(';');
                input.value = value;
                input.setAttribute('value', value);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };

            var renderSelected = function () {
                selected.innerHTML = '';
                warn.textContent = '';
                items.forEach(function (item) {
                    var li = buildItem(item, true);
                    var remove = el('button', 'sp-pp-remove');
                    remove.type = 'button';
                    remove.title = 'Entfernen';
                    remove.appendChild(el('i', 'fas fa-times'));
                    remove.addEventListener('click', function () {
                        items = items.filter(function (other) { return keyOf(other) !== keyOf(item); });
                        save();
                        renderSelected();
                        markResults();
                    });
                    li.appendChild(remove);
                    selected.appendChild(li);
                    if (isCoupon && item.state !== 'active') {
                        warn.textContent = 'Dieser Kupon ist ' + (STATES[item.state] || item.state)
                            + ' – der Deal wird im Shop nicht angezeigt.';
                    }
                });
                empty.style.display = items.length || !empty.textContent ? 'none' : '';
                box.style.display = single && items.length ? 'none' : '';
                if (single && items.length) {
                    results.innerHTML = '';
                    status.textContent = '';
                }
            };

            var markResults = function () {
                results.querySelectorAll('.sp-pp-item').forEach(function (li) {
                    var key = li.getAttribute('data-key');
                    li.classList.toggle('is-selected', items.some(function (item) { return keyOf(item) === key; }));
                });
            };

            var renderResults = function (list) {
                results.innerHTML = '';
                list.forEach(function (item) {
                    var li = buildItem(item, false);
                    li.addEventListener('click', function () {
                        if (items.some(function (other) { return keyOf(other) === keyOf(item); })) {
                            return;
                        }
                        if (single) {
                            items = [item];
                        } else if (items.length >= max) {
                            status.textContent = 'Höchstens ' + max + ' Artikel – entferne zuerst einen.';
                            return;
                        } else {
                            items.push(item);
                        }
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
                if (!isCoupon && term.length < 2) {
                    results.innerHTML = '';
                    status.textContent = term.length ? 'Mindestens 2 Zeichen eingeben.' : '';
                    return;
                }
                status.textContent = 'Suche …';
                var request = call(searchFn, isCoupon ? [term, false] : [term]);
                request.then(function (list) {
                    if (no !== requestNo) { return; }
                    if (isCoupon) {
                        status.textContent = list.length
                            ? (term ? list.length + ' Treffer' : 'Neueste Kupons') + ' – zum Auswählen anklicken. ' + HIDDEN_HINT
                            : 'Keine passenden Kupons gefunden. ' + HIDDEN_HINT;
                    } else {
                        status.textContent = list.length
                            ? list.length + ' Treffer – zum ' + (single ? 'Auswählen' : 'Hinzufügen') + ' anklicken'
                            : (isCategory ? 'Keine Kategorie gefunden.' : 'Keine Artikel gefunden.');
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
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    clearTimeout(timer);
                    runSearch();
                }
            });
            if (isCoupon) {
                search.addEventListener('focus', function () {
                    if (!results.children.length) {
                        runSearch();
                    }
                });
            } else if (window.jQuery && jQuery.fn.sortable) {
                jQuery(selected).sortable({
                    handle: '.sp-pp-handle',
                    update: function () {
                        var order = Array.prototype.map.call(selected.children, function (li) {
                            return li.getAttribute('data-key');
                        });
                        items.sort(function (a, b) { return order.indexOf(keyOf(a)) - order.indexOf(keyOf(b)); });
                        save();
                    }
                });
            }

            var initial = (input.value || '').split(/[;,\s]+/).filter(Boolean);
            renderSelected();
            if (initial.length) {
                status.textContent = 'Lade Auswahl …';
                var load = isCoupon
                    ? call(searchFn, [initial[0], true])
                    : call(searchFn, [(single ? initial.slice(0, 1) : initial).map(Number)]);
                load.then(function (list) {
                    status.textContent = '';
                    if (list.length) {
                        items = list;
                        renderSelected();
                    } else if (isCoupon) {
                        status.textContent = 'Gespeicherter Code „' + initial[0] + '“ wurde nicht gefunden – bitte neu auswählen.';
                    } else if (isCategory) {
                        status.textContent = 'Gespeicherte Kategorie wurde nicht gefunden – bitte neu auswählen.';
                    }
                }).catch(function (error) {
                    status.textContent = 'Auswahl konnte nicht geladen werden: ' + error.message;
                });
            }
        };

        window.spPicker = {
            init: init,
            initAll: function (container, force) {
                if (!container) {
                    return;
                }
                container.querySelectorAll('.sp-picker').forEach(function (root) {
                    init(root, force);
                });
            }
        };
    })();
</script>
