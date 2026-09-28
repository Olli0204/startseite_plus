{*
    Startseite Plus – Kupon-Picker für OPC-Portlet-Eigenschaften (Einzelauswahl, Logik in picker-core.tpl).
    Ohne Suchtext werden die neuesten geeigneten Kupons angeboten (ohne Einmal-, Kunden- und Massenkupons).

    Konfiguration über die Property-Definition ($propdesc):
      placeholder (string) Platzhalter des Suchfelds

    Gespeichert wird der Kupon-Code als String (kompatibel zum früheren Textfeld).
*}
{include file='./picker-core.tpl'}
<div class="form-group">
    <label{if !empty($propdesc.desc)} data-toggle="tooltip" title="{$propdesc.desc|escape:'html'}" data-placement="auto"{/if}>
        {$propdesc.label}
        {if !empty($propdesc.desc)}<i class="fas fa-info-circle fa-fw"></i>{/if}
    </label>
    <div class="sp-picker" id="{$propname}-picker" data-sp-picker="coupon"
         data-placeholder="{$propdesc.placeholder|default:''|escape:'html'}">
        <input type="hidden" class="sp-picker-value" id="config-{$propname}" name="{$propname}" value="{$propval|default:''|escape:'html'}">
        <div class="sp-picker-ui"></div>
    </div>
</div>
<script>window.spPicker.init(document.getElementById('{$propname}-picker'));</script>
