{* Startseite Plus – Kopf der Newsletter-Deal-Seiten (versteckte Artikellisten, Daten aus HOOK_FILTER_PAGE).
   Auf allen anderen Artikellisten bleibt die NOVA-Überschrift unverändert. *}
{block name='productlist-header-heading'}
    {if !empty($spNlDeal)}
        {$spNlCdTpl = $spCommonPath|cat:'countdown.tpl'}
        <link rel="stylesheet" href="{$spCommonUrl}common.css?v={$spVersion|escape:'html'}">
        <link rel="stylesheet" href="{$spCommonUrl}deal.css?v={$spVersion|escape:'html'}">
        {foreach $spNlDeal.notes as $note}
            <div class="alert alert-warning">{$note|escape:'html'}</div>
        {/foreach}
        <h1>{$spNlDeal.title|escape:'html'}</h1>
        {if $spNlDeal.text !== ''}
            <p class="sp-nld-text">{$spNlDeal.text|escape:'html'|nl2br}</p>
        {/if}
        {if $spNlDeal.expired}
            <div class="alert alert-info sp-nld-ended">
                {$spNlDeal.expiredText|escape:'html'}
                <a href="{$spNlDeal.homeUrl|escape:'html'}" class="alert-link">{$spNlDeal.homeLabel|escape:'html'}</a>
            </div>
        {elseif $spNlDeal.deal !== null}
            {$deal = $spNlDeal.deal}
            <section class="sp-deal sp-deal--strip sp-deal--r-md sp-bg-tint sp-nld-deal" data-sp-deal>
                <div class="sp-deal__icon" aria-hidden="true"><i class="fas fa-percent"></i></div>
                <div class="sp-deal__body">
                    <span class="sp-kicker sp-deal__kicker">{$spNlDeal.kicker|escape:'html'}</span>
                    <div class="sp-deal__title">{$spNlDeal.discount|escape:'html'} {$spNlDeal.saveLabel|escape:'html'}</div>
                    <div class="sp-deal__sub">
                        {$spNlDeal.hint|escape:'html'}{if $spNlDeal.validLabel !== ''} · {$spNlDeal.validLabel|escape:'html'}{/if}
                    </div>
                    {if $spNlDeal.countdown !== null && !$spNlDeal.countdown.expired}
                        {include file=$spNlCdTpl cd=$spNlDeal.countdown dark=true}
                    {/if}
                </div>
                <div class="sp-deal__actions">
                    <span class="sp-deal__code">
                        <span class="sp-deal__code-value">{$deal.code|escape:'html'}</span>
                        <button type="button" class="sp-deal__copy" data-sp-copy="{$deal.code|escape:'html'}"
                                data-sp-copied="{$deal.copiedLabel|escape:'html'}" title="{$deal.copyLabel|escape:'html'}">
                            <i class="far fa-copy" aria-hidden="true"></i>
                            <span class="sp-deal__copy-label">{$deal.copyLabel|escape:'html'}</span>
                        </button>
                    </span>
                </div>
                <div class="sp-deal__msg" role="alert" hidden></div>
            </section>
            <script src="{$spCommonUrl}deal.js?v={$spVersion|escape:'html'}" defer></script>
            {if $spNlDeal.countdown !== null}
                <script src="{$spCommonUrl}countdown.js?v={$spVersion|escape:'html'}" defer></script>
            {/if}
        {elseif $spNlDeal.countdown !== null && !$spNlDeal.countdown.expired}
            <div class="sp-nld-countdown">
                {include file=$spNlCdTpl cd=$spNlDeal.countdown dark=true}
            </div>
            <script src="{$spCommonUrl}countdown.js?v={$spVersion|escape:'html'}" defer></script>
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
