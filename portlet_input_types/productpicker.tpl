{*
    Startseite Plus – Artikel-Picker für OPC-Portlet-Eigenschaften (Logik in picker-core.tpl).

    Konfiguration über die Property-Definition ($propdesc):
      max         (int)    höchstens so viele Artikel (Standard 4)
      emptyText   (string) Hinweis, solange nichts ausgewählt ist
      placeholder (string) Platzhalter des Suchfelds

    Gespeichert wird ein String mit Artikel-IDs: "101;102".
*}
{include file='./picker-core.tpl'}
<div class="form-group">
    <label{if !empty($propdesc.desc)} data-toggle="tooltip" title="{$propdesc.desc|escape:'html'}" data-placement="auto"{/if}>
        {$propdesc.label}
        {if !empty($propdesc.desc)}<i class="fas fa-info-circle fa-fw"></i>{/if}
    </label>
    <div class="sp-picker" id="{$propname}-picker" data-sp-picker="products" data-max="{$propdesc.max|default:4}"
         data-empty="{$propdesc.emptyText|default:'Noch keine Artikel ausgewählt.'|escape:'html'}"
         data-placeholder="{$propdesc.placeholder|default:''|escape:'html'}">
        <input type="hidden" class="sp-picker-value" id="config-{$propname}" name="{$propname}" value="{$propval|default:''|escape:'html'}">
        <div class="sp-picker-ui"></div>
    </div>
</div>
<script>window.spPicker.init(document.getElementById('{$propname}-picker'));</script>
