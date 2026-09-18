{* Startseite Plus – Countdown-Verwaltung *}
{if $step === 'overview'}
    <p class="text-muted">
        Countdowns stehen im Aktions-Banner (OnPage Composer) zur Auswahl und können zusätzlich auf Artikeldetailseiten
        angezeigt werden. Zeiten gelten in der Shop-Zeitzone. Aktuelle Serverzeit: <strong>{$now}</strong>
    </p>
    {include file='tpl_inc/model_list.tpl'
        items=$models
        includeHeader=false
        create=true
        tabs=false
        select=true
        edit=true
        search=false
        delete=true}
{elseif $step === 'detail'}
<div id="detail-wrapper">
    <form id="model-detail" name="model_detail" method="post" action="{$action}">
        {$jtl_token}
        <input type="hidden" name="id" value="{$item->id|intval}">
        <div class="card">
            <div class="card-header">
                <div class="subheading1">Countdown</div>
                <hr class="mb-n3">
            </div>
            <div class="card-body">
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="name">Name (intern):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="name" name="name" maxlength="100" required
                               value="{$item->name|default:''|escape:'html'}" placeholder="z. B. Black Weekend 2026">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="until">Endzeitpunkt:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="datetime-local" class="form-control" id="until" name="until" required step="60"
                               value="{$item->getUntilInput()|escape:'html'}">
                        <small class="text-muted">Shop-Zeitzone, Serverzeit jetzt: {$now}</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="active">Aktiv:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox" id="active" name="active" value="1"
                                   {if ($item->id|intval) === 0 || ($item->active|intval) === 1}checked{/if}>
                            <label class="custom-control-label" for="active"></label>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="label">Beschriftung (DE):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="label" name="label" maxlength="150"
                               value="{$item->label|default:''|escape:'html'}" placeholder="z. B. Nur noch">
                        <small class="text-muted">Text vor den Ziffern; auf der Artikelseite die Überschrift der Box.</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="label_en">Beschriftung (EN):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="label_en" name="label_en" maxlength="150"
                               value="{$item->label_en|default:''|escape:'html'}" placeholder="optional, sonst DE">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="style">Darstellung:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select class="custom-select" id="style" name="style">
                            {foreach $styles|default:[] as $k => $v}
                                <option value="{$k}"{if $item->style === $k} selected{/if}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <hr>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="expired_mode">Nach Ablauf:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select class="custom-select" id="expired_mode" name="expired_mode">
                            {foreach $modes|default:[] as $k => $v}
                                <option value="{$k}"{if $item->expired_mode === $k} selected{/if}>{$v}</option>
                            {/foreach}
                        </select>
                        <small class="text-muted">„Ausblenden“ blendet auch den Aktions-Banner aus, der diesen Countdown nutzt.</small>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="expired_text">Hinweistext nach Ablauf (DE):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="expired_text" name="expired_text" maxlength="255"
                               value="{$item->expired_text|default:''|escape:'html'}" placeholder="z. B. Die Aktion ist beendet.">
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="expired_text_en">Hinweistext nach Ablauf (EN):</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input type="text" class="form-control" id="expired_text_en" name="expired_text_en" maxlength="255"
                               value="{$item->expired_text_en|default:''|escape:'html'}" placeholder="optional, sonst DE">
                    </div>
                </div>

                <hr>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="product_page">Auf Artikeldetailseiten anzeigen:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select class="custom-select" id="product_page" name="product_page">
                            {foreach $productPages|default:[] as $k => $v}
                                <option value="{$k}"{if $item->product_page === $k} selected{/if}>{$v}</option>
                            {/foreach}
                        </select>
                        <small class="text-muted">Erscheint oberhalb der Variationen/Kaufbox; „Nur bei aktivem Sonderpreis“ zeigt den Countdown ausschließlich bei reduzierten Artikeln.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer save-wrapper">
            <div class="row first-ml-auto">
                <div class="col-sm-6 col-xl-auto">
                    <button type="submit" name="go-back" value="1" class="btn btn-outline-primary btn-block" formnovalidate>
                        {__('cancelWithIcon')}
                    </button>
                </div>
                <div class="col-sm-6 col-xl-auto">
                    <button type="submit" name="save-model-continue" value="1" class="btn btn-outline-primary btn-block">
                        <i class="fal fa-save"></i> {__('saveAndContinue')}
                    </button>
                </div>
                <div class="col-sm-6 col-xl-auto">
                    <button type="submit" name="save-model" value="1" class="btn btn-primary btn-block">
                        <i class="far fa-save"></i> {__('save')}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
{/if}
