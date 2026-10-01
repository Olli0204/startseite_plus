{* Startseite Plus – Newsletter-Deals: versteckte Aktionsseiten, nur über den geheimen Link erreichbar *}
<style>
    .sp-nld .sp-nld-url { display: flex; align-items: center; gap: .35rem; min-width: 0; }
    .sp-nld .sp-nld-url code { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--body-color, #212529); }
    .sp-nld .sp-nld-prefix { white-space: nowrap; color: var(--secondary, #6c757d); }
    .sp-nld .sp-nld-muted { color: var(--secondary, #6c757d); font-size: .85em; }
    .sp-nld .sp-pp-item { background: var(--card-bg, #fff); border-color: var(--border-color, #dfe3e8); color: var(--body-color, #212529); }
    .sp-nld .sp-pp-thumb { background: var(--body-bg, #f5f7fa); }
    .sp-nld .sp-pp-results { max-height: 22rem; overflow-y: auto; }
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
                            <th class="text-left">Kupon</th>
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
                                    <div class="sp-nld-url">
                                        <code title="{$page.url|escape:'html'}">{$page.url|escape:'html'}</code>
                                        <button type="button" class="btn btn-link px-1" data-sp-nld-copy="{$page.url|escape:'html'}" title="Link kopieren">
                                            <span class="fal fa-copy"></span>
                                        </button>
                                        <a class="btn btn-link px-1" href="{$page.previewUrl|escape:'html'}" target="_blank" rel="noopener" title="Seite öffnen (mit Admin-Vorschau)">
                                            <span class="fal fa-external-link"></span>
                                        </a>
                                    </div>
                                </td>
                                <td>{$page.period|escape:'html'}</td>
                                <td>{if $page.coupon !== ''}<code>{$page.coupon|escape:'html'}</code>{else}<span class="sp-nld-muted">–</span>{/if}</td>
                                <td class="text-center">{if $page.count > 0}{$page.count}{else}<span class="sp-nld-muted" title="Artikel aus dem Kupon">Kupon</span>{/if}</td>
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
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nld_slug">Geheimer Link:</label>
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
                    <label class="col col-sm-4 col-form-label text-sm-right">Kupon:</label>
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
                    <label class="col col-sm-4 col-form-label text-sm-right">Artikel:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="sp-picker" data-sp-picker="products" data-max="200" data-parents-ok
                             data-empty="Keine Artikel gewählt – dann zeigt die Seite die Artikel, die im Kupon hinterlegt sind.">
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
            });
        }
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
