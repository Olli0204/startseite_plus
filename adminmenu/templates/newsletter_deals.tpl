{* Startseite Plus – Newsletter-Deals: versteckte Aktionsseiten, nur über den geheimen Link erreichbar *}
<style>
    .sp-nld .sp-nld-url { display: flex; align-items: center; gap: .35rem; min-width: 0; }
    .sp-nld .sp-nld-url code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--body-color, #212529); }
    .sp-nld .sp-nld-prefix { white-space: nowrap; color: var(--secondary, #6c757d); }
    .sp-nld .sp-nld-muted { color: var(--body-color, #212529); opacity: .65; font-size: .85em; }
    .sp-nld .sp-pp-item { background: var(--card-bg, #fff); border-color: var(--border-color, #dfe3e8); color: var(--body-color, #212529); }
    .sp-nld .sp-pp-thumb { background: var(--body-bg, #f5f7fa); }
    .sp-nld .sp-pp-results { max-height: 22rem; overflow-y: auto; }
    .sp-nld .sp-nld-rule { margin: 0 0 .75rem; padding: .75rem; border: 1px solid var(--border-color, #dfe3e8); border-radius: 6px; background: var(--card-bg, #fff); }
    .sp-nld .sp-nld-rule-head { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .5rem; }
    .sp-nld .sp-nld-rule-type { flex: 1 1 14rem; max-width: 20rem; }
    .sp-nld .sp-nld-rule-price { flex: 0 1 13rem; }
    .sp-nld .sp-nld-rule-remove { margin-left: auto; }
    .sp-nld .sp-nld-rule-text { margin: 0 0 .35rem; font-size: .85em; color: var(--body-color, #212529); opacity: .7; }
    .sp-nld .sp-nld-rule-partners { margin-top: .6rem; }
    .sp-nld .sp-nld-rule[data-type="price"] .sp-nld-rule-partners,
    .sp-nld .sp-nld-rule[data-type="price"] [data-for="set"],
    .sp-nld .sp-nld-rule[data-type="set"] [data-for="price"] { display: none; }
    .sp-nld .sp-nld-rule-add { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .35rem; }
</style>
<div class="sp-nld">
    {if $nldFlash !== ''}
        <div class="alert alert-success">{$nldFlash|escape:'html'}</div>
    {/if}
    {if $nldError !== ''}
        <div class="alert alert-danger">{$nldError|escape:'html'}</div>
    {/if}

{if $nldForm === null}
    <div class="card">
        <div class="card-header">
            <div class="subheading1">Newsletter-Deals</div>
            <hr class="mb-n3">
        </div>
        <div class="card-body">
            <p class="text-muted">
                Jede Deal-Seite ist eine normale Artikelliste (Filter, Sortierung, Seiten) mit den gewählten Artikeln und dem
                Kupon-Code als Kopfzeile. Sie steht in keinem Menü, wird von Suchmaschinen nicht indexiert und ist nur über
                ihren geheimen Link erreichbar – diesen Link in den Newsletter einfügen. Inaktive und geplante Seiten sehen
                nur angemeldete Admins (als Vorschau). Serverzeit: <strong>{$nldNow}</strong>
            </p>
            {if $nldPages|count === 0}
                <p class="mb-0"><em>Noch keine Deal-Seite angelegt.</em></p>
            {else}
                <div class="table-responsive">
                    <table class="list table table-align-top">
                        <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Geheimer Link</th>
                            <th class="text-left">Zeitraum</th>
                            <th class="text-left">Code / Kupon</th>
                            <th class="text-center">Deal-Preise</th>
                            <th class="text-center">Artikel</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aktionen</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $nldPages as $page}
                            <tr>
                                <td>
                                    <strong>{$page.name|escape:'html'}</strong><br>
                                    <span class="sp-nld-muted">{$page.title|escape:'html'}</span>
                                </td>
                                <td style="max-width: 22rem">
                                    {foreach [['DE', $page.url, $page.previewUrl], ['EN', $page.urlEn, $page.previewUrlEn]] as $link}
                                        <div class="sp-nld-url">
                                            <span class="badge badge-light">{$link[0]}</span>
                                            <code title="{$link[1]|escape:'html'}">{$link[1]|escape:'html'}</code>
                                            <button type="button" class="btn btn-link px-1" data-sp-nld-copy="{$link[1]|escape:'html'}" title="Link kopieren">
                                                <span class="fal fa-copy"></span>
                                            </button>
                                            <a class="btn btn-link px-1" href="{$link[2]|escape:'html'}" target="_blank" rel="noopener" title="Seite öffnen (mit Admin-Vorschau)">
                                                <span class="fal fa-external-link"></span>
                                            </a>
                                        </div>
                                    {/foreach}
                                </td>
                                <td>{$page.period|escape:'html'}</td>
                                <td>
                                    {if $page.code !== ''}<span class="sp-nld-muted">Code</span> <code>{$page.code|escape:'html'}</code><br>{/if}
                                    {if $page.coupon !== ''}<span class="sp-nld-muted">Kupon</span> <code>{$page.coupon|escape:'html'}</code>{/if}
                                    {if $page.code === '' && $page.coupon === ''}<span class="sp-nld-muted">–</span>{/if}
                                </td>
                                <td class="text-center">{if $page.rules > 0}{$page.rules}{else}<span class="sp-nld-muted">–</span>{/if}</td>
                                <td class="text-center">{if $page.count > 0}{$page.count}{elseif $page.rules > 0}<span class="sp-nld-muted" title="Artikel der Deal-Preise">aus Preisen</span>{else}<span class="sp-nld-muted" title="Artikel aus dem Kupon">Kupon</span>{/if}</td>
                                <td class="text-center"><span class="badge badge-{$page.statusClass}">{$page.statusLabel|escape:'html'}</span></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a class="btn btn-link px-2" href="{$nldBaseUrl|escape:'html'}&amp;nld=edit&amp;nld_id={$page.id}" title="Bearbeiten">
                                            <span class="icon-hover"><span class="fal fa-edit"></span><span class="fas fa-edit"></span></span>
                                        </a>
                                        <form method="post" action="{$nldBaseUrl|escape:'html'}" class="d-inline">
                                            {$jtl_token}
                                            <input type="hidden" name="kPluginAdminMenu" value="{$nldMenuID}">
                                            <input type="hidden" name="nld_id" value="{$page.id}">
                                            <button type="submit" name="nld_action" value="toggle" class="btn btn-link px-2"
                                                    title="{if $page.active}Deaktivieren{else}Aktivieren{/if}">
                                                <span class="icon-hover">
                                                    <span class="fal {if $page.active}fa-toggle-on{else}fa-toggle-off{/if}"></span>
                                                    <span class="fas {if $page.active}fa-toggle-on{else}fa-toggle-off{/if}"></span>
                                                </span>
                                            </button>
                                            <button type="submit" name="nld_action" value="delete" class="btn btn-link px-2 delete-confirm"
                                                    title="Löschen" onclick="return confirm('Deal-Seite „{$page.name|escape:'javascript'|escape:'html'}“ wirklich löschen? Der Link funktioniert danach nicht mehr.');">
                                                <span class="icon-hover"><span class="fal fa-trash-alt"></span><span class="fas fa-trash-alt"></span></span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
        <div class="card-footer save-wrapper">
            <div class="row">
                <div class="ml-auto col-sm-6 col-xl-auto submit">
                    <a class="btn btn-primary btn-block" href="{$nldBaseUrl|escape:'html'}&amp;nld=new">
                        <i class="fa fa-plus"></i> Neue Deal-Seite
                    </a>
                </div>
            </div>
        </div>
    </div>
{else}
    {include file=$nldPickerCore}
    <form method="post" action="{$nldBaseUrl|escape:'html'}" id="sp-nld-form">
        {$jtl_token}
        <input type="hidden" name="kPluginAdminMenu" value="{$nldMenuID}">
        <input type="hidden" name="nld_id" value="{$nldForm.id}">
        <div class="card">
            <div class="card-header">
                <div class="subheading1">{if $nldForm.id > 0}Deal-Seite bearbeiten{else}Neue Deal-Seite{/if}</div>
                <hr class="mb-n3">
            </div>
            <div class="card-body">
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_name">Name (intern):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="nld_name" name="nld_name" maxlength="100" required
                               value="{$nldForm.name|escape:'html'}" placeholder="z. B. Newsletter KW 41">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_slug">Geheimer Link (DE):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text sp-nld-prefix">{$nldShopUrl|escape:'html'}</span></div>
                            <input type="text" class="form-control" id="nld_slug" name="nld_slug" maxlength="120" required
                                   pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{$nldForm.slug|escape:'html'}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary" id="sp-nld-newslug" title="Neuen zufälligen Link erzeugen">
                                    <i class="fal fa-random"></i> Neu
                                </button>
                                {if $nldForm.url !== ''}
                                    <button type="button" class="btn btn-outline-primary" data-sp-nld-copy="{$nldForm.url|escape:'html'}" title="Link kopieren">
                                        <i class="fal fa-copy"></i>
                                    </button>
                                    <a class="btn btn-outline-primary" href="{$nldForm.previewUrl|escape:'html'}" target="_blank" rel="noopener" title="Seite öffnen (mit Admin-Vorschau)">
                                        <i class="fal fa-external-link"></i>
                                    </a>
                                {/if}
                            </div>
                        </div>
                        <small class="text-muted">Nur Kleinbuchstaben, Ziffern und Bindestriche. Die Zufallsendung macht den Link
                            unerratbar. Wird der Link geändert, funktioniert der alte nicht mehr.</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_slug_en">Geheimer Link (EN):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text sp-nld-prefix">{$nldShopUrl|escape:'html'}</span></div>
                            <input type="text" class="form-control" id="nld_slug_en" name="nld_slug_en" maxlength="120"
                                   pattern="[a-z0-9]+(-[a-z0-9]+)*" value="{$nldForm.slug_en|escape:'html'}" placeholder="leer = deutscher Link + „-en“">
                            {if $nldForm.urlEn !== ''}
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" data-sp-nld-copy="{$nldForm.urlEn|escape:'html'}" title="Link kopieren">
                                        <i class="fal fa-copy"></i>
                                    </button>
                                    <a class="btn btn-outline-primary" href="{$nldForm.previewUrlEn|escape:'html'}" target="_blank" rel="noopener" title="Seite öffnen (mit Admin-Vorschau)">
                                        <i class="fal fa-external-link"></i>
                                    </a>
                                </div>
                            {/if}
                        </div>
                        <small class="text-muted">Für den englischen Newsletter: zeigt die Seite auf Englisch (Überschrift/Text EN,
                            Artikelnamen, Preise und Hinweise). Wie bei Kategorien bestimmt der Link die Sprache; der
                            Sprachumschalter im Shop wechselt zwischen beiden Links.{if !$nldForm.hasEnglish}
                            <strong>Hinweis: Im Shop ist keine englische Sprache aktiv.</strong>{/if}</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_active">Aktiv:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox" id="nld_active" name="nld_active" value="1"{if $nldForm.active} checked{/if}>
                            <label class="custom-control-label" for="nld_active"></label>
                        </div>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_valid_from">Start (optional):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="datetime-local" class="form-control" id="nld_valid_from" name="nld_valid_from" step="60"
                               value="{$nldForm.valid_from|escape:'html'}">
                        <small class="text-muted">Vorher sehen Kunden eine 404-Seite, Admins eine Vorschau.</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_valid_until">Ende (optional):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="datetime-local" class="form-control" id="nld_valid_until" name="nld_valid_until" step="60"
                               value="{$nldForm.valid_until|escape:'html'}">
                        <small class="text-muted">Danach zeigt die Seite „Aktion beendet“ statt der Artikel. Leer = Ende des
                            Kupons (falls gesetzt) für den Countdown.</small>
                    </div>
                </div>

                <hr>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_title">Überschrift (DE):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="nld_title" name="nld_title" maxlength="150" required
                               value="{$nldForm.title|escape:'html'}" placeholder="z. B. Deine Newsletter-Deals">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_title_en">Überschrift (EN):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="nld_title_en" name="nld_title_en" maxlength="150"
                               value="{$nldForm.title_en|escape:'html'}" placeholder="optional, sonst DE">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_text">Text (DE):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <textarea class="form-control" id="nld_text" name="nld_text" rows="2" maxlength="2000"
                                  placeholder="optional, z. B. Nur für kurze Zeit und nur für dich">{$nldForm.text|escape:'html'}</textarea>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_text_en">Text (EN):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <textarea class="form-control" id="nld_text_en" name="nld_text_en" rows="2" maxlength="2000"
                                  placeholder="optional, sonst DE">{$nldForm.text_en|escape:'html'}</textarea>
                    </div>
                </div>

                <hr>
                <div class="form-group form-row">
                    <label class="col col-sm-4 col-form-label text-sm-right">Deal-Preise:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div id="sp-nld-rules">
                            {foreach $nldForm.rules as $rule}
                                {include file=$nldRuleTpl rule=$rule idx=$rule@index}
                            {/foreach}
                        </div>
                        <template id="sp-nld-rule-template">
                            {include file=$nldRuleTpl rule=['type' => 'price', 'products' => '', 'partners' => '', 'price' => ''] idx='__i__'}
                        </template>
                        <div class="sp-nld-rule-add">
                            <button type="button" class="btn btn-outline-primary" data-sp-nld-add="price"><i class="fal fa-plus"></i> Festpreis</button>
                            <button type="button" class="btn btn-outline-primary" data-sp-nld-add="set"><i class="fal fa-plus"></i> Set-Preis</button>
                        </div>
                        <small class="text-muted">Bruttopreise je Stück. Sie gelten im Warenkorb für Kunden, die den Link besucht oder
                            den Deal-Code eingegeben haben, und nur, solange sie günstiger als der Shop-Preis sind. Beispiel:
                            Festpreis 250 € für die Boards, Festpreis 120 € für die Upshot und Set-Preis 100 € für die Upshot mit
                            dem Odyssey als Set-Partner. Ohne eigene Artikelauswahl zeigt die Seite alle Artikel der Deal-Preise.</small>
                    </div>
                </div>
                <div class="form-group form-row">
                    <label class="col col-sm-4 col-form-label text-sm-right">Darstellung des Newsletter-Preises:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        {foreach [
                            'line'  => ['Zeile im Preisblock', 'Der Shop-Preis bleibt groß; darunter eine schlichte Zeile „Newsletter-Preis 150,00 €“ (bei Set-Artikeln in der Kachel kurz „im Set 100,00 €“).'],
                            'price' => ['Als Hauptpreis', 'Der Newsletter-Preis wird zum großen Preis, der aktuelle Shop-Preis rutscht in „Alter Preis“, dazu eine kleine Marke „Newsletter“. Die Kachel wird nicht höher.'],
                            'badge' => ['Marke am Produktbild', 'Preisblock unverändert; oben rechts am Produktbild eine Marke „Newsletter 150 €“. Auf der Artikelseite eine Zeile im Preisblock.']
                        ] as $mode => $info}
                            <div class="custom-control custom-radio mb-2">
                                <input class="custom-control-input" type="radio" id="nld_display_{$mode}" name="nld_display" value="{$mode}"{if $nldForm.display === $mode} checked{/if}>
                                <label class="custom-control-label" for="nld_display_{$mode}">
                                    <strong>{$info[0]}</strong><br><small class="sp-nld-muted">{$info[1]}</small>
                                </label>
                            </div>
                        {/foreach}
                        <small class="text-muted">Gilt nur für Kunden mit freigeschaltetem Deal; alle anderen sehen den normalen Shop.
                            Der Hinweis erscheint nur, wenn der Newsletter-Preis unter dem aktuellen Shop-Preis liegt.</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_code">Deal-Code (optional):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="nld_code" name="nld_code" maxlength="50"
                               value="{$nldForm.code|escape:'html'}" placeholder="z. B. SNOW4DAYS" style="text-transform: uppercase">
                        <small class="text-muted">Für den Newsletter: schaltet die Deal-Preise im Kupon-Feld des Warenkorbs frei,
                            z. B. auf einem anderen Gerät. Der Besuch des Links schaltet sie automatisch frei. Kein bestehender
                            JTL-Kupon-Code, Groß-/Kleinschreibung egal.</small>
                    </div>
                </div>

                <hr>
                <div class="form-group form-row">
                    <label class="col col-sm-4 col-form-label text-sm-right">Zusätzlicher Kupon (optional):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="sp-picker" data-sp-picker="coupon">
                            <input type="hidden" class="sp-picker-value" name="nld_coupon" value="{$nldForm.coupon|escape:'html'}">
                            <div class="sp-picker-ui"></div>
                        </div>
                        <small class="text-muted">Der Code erscheint oben auf der Seite zum Kopieren. Damit der Rabatt nur für
                            diese Artikel gilt, im Kupon (Marketing › Kupons) die Artikel hinterlegen.</small>
                    </div>
                </div>
                <div class="form-group form-row">
                    <label class="col col-sm-4 col-form-label text-sm-right">Artikel der Seite (optional):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="sp-picker" data-sp-picker="products" data-max="200" data-parents-ok
                             data-empty="Keine Artikel gewählt – dann zeigt die Seite die Artikel der Deal-Preise bzw. des Kupons.">
                            <input type="hidden" class="sp-picker-value" name="nld_products" value="{$nldForm.products|escape:'html'}">
                            <div class="sp-picker-ui"></div>
                        </div>
                        <small class="text-muted">Vaterartikel zeigen alle Varianten; eine gewählte Variante bringt ihren
                            Vaterartikel in die Liste. Die Reihenfolge bestimmt die Sortierung des Shops.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer save-wrapper">
                <div class="row first-ml-auto">
                    <div class="col-sm-6 col-xl-auto">
                        <a class="btn btn-outline-primary btn-block" href="{$nldBaseUrl|escape:'html'}">{__('cancelWithIcon')}</a>
                    </div>
                    <div class="col-sm-6 col-xl-auto">
                        <button type="submit" name="nld_action" value="save_continue" class="btn btn-outline-primary btn-block">
                            <i class="fal fa-save"></i> {__('saveAndContinue')}
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-auto">
                        <button type="submit" name="nld_action" value="save" class="btn btn-primary btn-block">
                            <i class="far fa-save"></i> {__('save')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
{/if}
</div>
<script>
    (function () {
        var root = document.querySelector('.sp-nld');
        if (!root) {
            return;
        }
        if (window.spPicker) {
            window.spPicker.initAll(root);
        }
        var newSlug = document.getElementById('sp-nld-newslug');
        if (newSlug) {
            newSlug.addEventListener('click', function () {
                var bytes = new Uint8Array(4);
                window.crypto.getRandomValues(bytes);
                var hex = Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
                document.getElementById('nld_slug').value = '{$nldPrefix|escape:'javascript'}-' + hex;
                document.getElementById('nld_slug_en').value = '{$nldPrefix|escape:'javascript'}-' + hex + '-en';
            });
        }
        var rules = document.getElementById('sp-nld-rules');
        var ruleTpl = document.getElementById('sp-nld-rule-template');
        var ruleNo = 0;
        root.addEventListener('click', function (event) {
            var add = event.target.closest('[data-sp-nld-add]');
            if (add && rules && ruleTpl) {
                var wrap = document.createElement('div');
                wrap.innerHTML = ruleTpl.innerHTML.replace(/__i__/g, 'n' + Date.now() + '' + (ruleNo++));
                var row = wrap.firstElementChild;
                var type = add.getAttribute('data-sp-nld-add');
                row.setAttribute('data-type', type);
                row.querySelector('.sp-nld-rule-type').value = type;
                rules.appendChild(row);
                if (window.spPicker) {
                    window.spPicker.initAll(row, true);
                }
                return;
            }
            var remove = event.target.closest('.sp-nld-rule-remove');
            if (remove) {
                remove.closest('.sp-nld-rule').remove();
                return;
            }
        });
        root.addEventListener('change', function (event) {
            if (event.target.classList.contains('sp-nld-rule-type')) {
                event.target.closest('.sp-nld-rule').setAttribute('data-type', event.target.value);
            }
        });
        root.addEventListener('click', function (event) {
            var button = event.target.closest('[data-sp-nld-copy]');
            if (!button) {
                return;
            }
            var text = button.getAttribute('data-sp-nld-copy');
            var done = function () {
                button.classList.add('text-success');
                setTimeout(function () { button.classList.remove('text-success'); }, 1500);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                window.prompt('Link kopieren:', text);
            }
        });
    })();
</script>
