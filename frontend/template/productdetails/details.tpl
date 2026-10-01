{* Startseite Plus – Countdowns auf der Artikeldetailseite (Freigabe in der Countdown-Verwaltung) *}
{block name='productdetails-details-include-variation' append}
    {if !empty($spProductCountdowns)}
        <link rel="stylesheet" href="{$spCommonUrl}common.css?v={$spVersion|escape:'html'}">
        {$spCdTpl = $spCommonPath|cat:'countdown.tpl'}
        {foreach $spProductCountdowns as $cd}
            <div class="sp-cd-product" data-sp-countdown-id="{$cd.id|intval}">
                {include file=$spCdTpl cd=$cd dark=true heading=true}
            </div>
        {/foreach}
        <script src="{$spCommonUrl}countdown.js?v={$spVersion|escape:'html'}" defer></script>
    {/if}
{/block}

{* Startseite Plus – Set-Konfigurator der Newsletter-Deals unter der Kaufbox. Nur ein leerer Platzhalter für Artikel aus
   Set-Regeln laufender Deals (für alle Besucher gleich, cachebar); die Karte lädt newsletter-deal.js per IO nur für
   Sitzungen mit freigeschaltetem Deal. *}
{block name='productdetails-details-include-basket' append}
    {if !empty($spNlSetIDs) && isset($Artikel) && !empty($Artikel->kArtikel)
        && (isset($spNlSetIDs[$Artikel->kArtikel]) || (!empty($Artikel->kVaterArtikel) && isset($spNlSetIDs[$Artikel->kVaterArtikel])))}
        {if empty($spNlDealAssetsDone)}
            <link rel="stylesheet" href="{$spNlDealCss|escape:'html'}">
            <script src="{$spNlDealJs|escape:'html'}" defer></script>
            {assign var=spNlDealAssetsDone value=true scope='global'}
        {/if}
        <div class="sp-nld-sets sp-nld-sets--product" hidden
             data-sp-nld-sets-product="{$Artikel->kArtikel|intval}" data-sp-nld-sets-parent="{$Artikel->kVaterArtikel|default:0|intval}"></div>
    {/if}
{/block}
