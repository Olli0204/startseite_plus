{*
    Startseite Plus – generischer Listen-Editor ("repeater") für OPC-Portlet-Eigenschaften.

    Konfiguration über die Property-Definition ($propdesc):
      useImage     (bool)   Bildauswahl pro Eintrag; gespeichert im Feld "url"
      requireImage (bool)   Einträge ohne Bild werden beim Speichern verworfen
      entryLabel   (string) Bezeichnung eines Eintrags, z. B. "Slide"
      fields       (array)  Felder je Eintrag: name, label, type (text|textarea|select|checkbox|number|color),
                            width (Prozent), options (select), help, placeholder, default, maxlength

    Gespeichert wird ein Array von Einträgen: [ ['url' => '…', '<feld>' => '…', …], … ]
    Ein leerer Eintrag (alle Felder leer) wird beim Speichern verworfen.
*}
{$useImage     = $propdesc.useImage|default:false}
{$requireImage = $propdesc.requireImage|default:false}
{$entryLabel   = $propdesc.entryLabel|default:'Eintrag'}
{$fields       = $propdesc.fields|default:[]}
{$propval      = $propval|default:[]}
{$emptyEntry   = ['url' => '']}
{foreach $fields as $field}
    {$emptyEntry[$field.name] = $field.default|default:''}
{/foreach}

{function spRepeaterEntry entry=[]}
    <div class="slide-entry sp-rep-entry">
        <div class="slide-btns">
            <span class="btn-slide-mover" title="{__('entryMove')}" style="cursor: move">
                <i class="fas fa-arrows-alt fa-fw"></i>
            </span>
            <button type="button" onclick="spRepClone_{$propname}(this)" title="Kopieren">
                <i class="far fa-clone fa-fw"></i>
            </button>
            <hr>
            <button type="button" onclick="spRepRemove_{$propname}(this)" title="{__('entryDelete')}">
                <i class="far fa-trash-alt fa-fw"></i>
            </button>
        </div>
        {if $useImage}
            {if empty($entry.url)}
                {$imgUrl = 'opc/gfx/upload-stub.png'}
            {else}
                {$imgUrl = \JTL\Shop::getURL()|cat:'/'|cat:$smarty.const.STORAGE_OPC|cat:$entry.url}
            {/if}
            <div class="slide-image-col">
                <div style="background-image: url('{$imgUrl}')" class="slide-image-btn" title="Bild auswählen"
                     onclick="opc.gui.openElFinder(spRepImage_{$propname}.bind(this), 'Bilder')"></div>
                <input type="hidden" name="{$propname}[#SORT#][url]" value="{$entry.url|default:''|escape:'html'}">
            </div>
        {/if}
        <div class="slide-props sp-rep-fields">
            {foreach $fields as $field}
                {$fname  = $field.name}
                {$ftype  = $field.type|default:'text'}
                {$fwidth = $field.width|default:100}
                {$flabel = $field.label|default:$fname}
                {$fval   = $entry[$fname]|default:''}
                {$iname  = "`$propname`[#SORT#][`$fname`]"}
                <div class="sp-rep-field" style="flex-basis: {$fwidth}%; max-width: {$fwidth}%">
                    {if $ftype === 'textarea'}
                        <textarea class="form-control" rows="2" name="{$iname}" placeholder="{$flabel|escape:'html'}"
                                  title="{$flabel|escape:'html'}">{$fval|escape:'html'}</textarea>
                    {elseif $ftype === 'select'}
                        <label class="sp-rep-label">{$flabel}</label>
                        <select class="form-control" name="{$iname}" title="{$flabel|escape:'html'}" onchange="spRepSyncSelect(this)">
                            {foreach $field.options|default:[] as $ovalue => $olabel}
                                <option value="{$ovalue|escape:'html'}" {if "$ovalue" === "$fval"}selected{/if}>{$olabel}</option>
                            {/foreach}
                        </select>
                    {elseif $ftype === 'checkbox'}
                        <label class="sp-rep-check">
                            <input type="hidden" name="{$iname}" value="0">
                            <input type="checkbox" name="{$iname}" value="1" {if $fval == '1'}checked{/if}
                                   onchange="spRepSyncCheckbox(this)">
                            {$flabel}
                        </label>
                    {elseif $ftype === 'color'}
                        <input type="text" class="form-control sp-rep-color" name="{$iname}" value="{$fval|escape:'html'}"
                               placeholder="{$flabel|escape:'html'}" title="{$flabel|escape:'html'}" oninput="spRepSyncInput(this)">
                    {else}
                        <input type="{if $ftype === 'number'}number{else}text{/if}" class="form-control" name="{$iname}"
                               value="{$fval|escape:'html'}" title="{$flabel|escape:'html'}"
                               placeholder="{$field.placeholder|default:$flabel|escape:'html'}"
                               {if !empty($field.maxlength)}maxlength="{$field.maxlength}"{/if} oninput="spRepSyncInput(this)">
                    {/if}
                    {if !empty($field.help)}
                        <small class="form-text text-muted sp-rep-help">{$field.help}</small>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
{/function}

<style>
    .sp-rep-fields { display: flex; flex-wrap: wrap; margin: 0 -3px; }
    .sp-rep-field { flex: 1 1 100%; box-sizing: border-box; padding: 0 3px 4px; min-width: 0; }
    .sp-rep-field .form-control { margin: 0; }
    .sp-rep-label, .sp-rep-check { display: block; margin: 0 0 2px; font-size: 11px; }
    .sp-rep-check input { margin-right: 4px; }
    .sp-rep-help { margin: 1px 0 0; font-size: 10px; line-height: 1.2; }
    .sp-rep-desc { margin: 0 0 6px; font-size: 11px; }
</style>

<label>{$propdesc.label}</label>
{if !empty($propdesc.desc)}
    <p class="text-muted sp-rep-desc">{$propdesc.desc}</p>
{/if}

<div class="slides-container sp-rep-container" id="{$propname}-container">
    <div id="{$propname}-entries">
        {foreach $propval as $entry}
            {if is_array($entry)}
                {spRepeaterEntry entry=$entry}
            {/if}
        {/foreach}
        {if $propval|count === 0}
            {spRepeaterEntry entry=$emptyEntry}
        {/if}
    </div>
    <div style="display: none" id="{$propname}-blueprint">
        {spRepeaterEntry entry=$emptyEntry}
    </div>
</div>

<button type="button" class="opc-btn-primary add-slide-btn" onclick="spRepAdd_{$propname}()" title="{$entryLabel} hinzufügen">
    <i class="fas fa-plus fa-fw"></i>
</button>

<script>
    (function () {
        if (window.spRepSyncEntry) {
            return;
        }
        // Aktuelle Werte in Attribute spiegeln, damit clone() (jQuery/cloneNode) die Eingaben übernimmt.
        window.spRepSyncInput = function (el) {
            el.setAttribute('value', el.value);
        };
        window.spRepSyncCheckbox = function (el) {
            if (el.checked) {
                el.setAttribute('checked', 'checked');
            } else {
                el.removeAttribute('checked');
            }
        };
        window.spRepSyncSelect = function (el) {
            Array.prototype.forEach.call(el.options, function (option) {
                if (option.selected) {
                    option.setAttribute('selected', 'selected');
                } else {
                    option.removeAttribute('selected');
                }
            });
        };
        window.spRepSyncEntry = function (entry) {
            entry.querySelectorAll('input[type=text], input[type=number], input[type=hidden]').forEach(window.spRepSyncInput);
            entry.querySelectorAll('textarea').forEach(function (textarea) {
                textarea.textContent = textarea.value;
            });
            entry.querySelectorAll('select').forEach(window.spRepSyncSelect);
            entry.querySelectorAll('input[type=checkbox]').forEach(window.spRepSyncCheckbox);
        };
    })();

    opc.once('save-config', spRepSave_{$propname});

    $(function () {
        $('#{$propname}-entries').sortable({
            handle: '.btn-slide-mover'
        });
    });

    function spRepImage_{$propname}(file)
    {
        let url = file.url.slice(file.baseUrl.length);
        $(this).css('background-image', 'url("' + file.url + '")');
        $(this).siblings('input').val(url).attr('value', url);
    }

    function spRepAdd_{$propname}()
    {
        $('#{$propname}-entries').append($('#{$propname}-blueprint').children().clone());
        let container = $('#{$propname}-container')[0];
        container.scrollTo(0, container.scrollHeight);
    }

    function spRepRemove_{$propname}(btn)
    {
        $(btn).closest('.sp-rep-entry').remove();
    }

    function spRepClone_{$propname}(btn)
    {
        let entry = $(btn).closest('.sp-rep-entry');
        window.spRepSyncEntry(entry[0]);
        entry.clone().insertAfter(entry);
    }

    function spRepIsEmpty_{$propname}(entry)
    {
        let filled = false;
        entry.find('input[type=text], input[type=number], input[type=hidden], textarea').each(function (i, el) {
            if (el.value !== '' && el.value !== '0') {
                filled = true;
            }
        });
        if (entry.find('input[type=checkbox]:checked').length > 0) {
            filled = true;
        }
        return !filled;
    }

    function spRepSave_{$propname}()
    {
        $('#{$propname}-entries').children().each(function (i, entry) {
            entry = $(entry);
            window.spRepSyncEntry(entry[0]);
            {if $requireImage}
            if (entry.find('input[name="{$propname}[#SORT#][url]"]').val() === '') {
                entry.remove();
                return;
            }
            {/if}
            if (spRepIsEmpty_{$propname}(entry)) {
                entry.remove();
                return;
            }
            entry.find('input, select, textarea').each(function (j, el) {
                let name = el.getAttribute('name');
                if (name) {
                    el.setAttribute('name', name.replace('#SORT#', i));
                }
            });
        });
        $('#{$propname}-blueprint').remove();
    }
</script>
