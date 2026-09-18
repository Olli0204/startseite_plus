{* Startseite Plus – Countdowns auf der Artikeldetailseite (Freigabe in der Countdown-Verwaltung) *}
{block name='productdetails-details-include-variation' append}
    {if !empty($spProductCountdowns)}
        <link rel="stylesheet" href="{$spCommonUrl}common.css?v={$spVersion|escape:'html'}">
        {foreach $spProductCountdowns as $cd}
            <div class="sp-cd-product" data-sp-countdown-id="{$cd.id|intval}">
                {include file="file:{$spCommonPath}countdown.tpl" cd=$cd dark=true heading=true}
            </div>
        {/foreach}
        <script src="{$spCommonUrl}countdown.js?v={$spVersion|escape:'html'}" defer></script>
    {/if}
{/block}
