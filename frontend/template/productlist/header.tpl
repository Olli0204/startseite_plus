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
        {else}
            {$spNlShowCd = $spNlDeal.countdown !== null && !$spNlDeal.countdown.expired}
            {if $spNlDeal.prices}
                {* Deal-Preise: per Link freigeschaltet; der Code hilft auf anderen Geräten *}
                <section class="sp-deal sp-deal--strip sp-deal--r-md sp-bg-tint sp-nld-deal sp-nld-deal--prices" data-sp-deal>
                    <div class="sp-deal__icon" aria-hidden="true"><i class="fas fa-check"></i></div>
                    <div class="sp-deal__body">
                        <span class="sp-kicker sp-deal__kicker">{$spNlDeal.kicker|escape:'html'}</span>
                        <div class="sp-deal__title">{$spNlDeal.pricesTitle|escape:'html'}</div>
                        <div class="sp-deal__sub">
                            {$spNlDeal.pricesHint|escape:'html'}{if $spNlDeal.validLabel !== ''} · {$spNlDeal.validLabel|escape:'html'}{/if}
                        </div>
                        {if $spNlShowCd}
                            {include file=$spNlCdTpl cd=$spNlDeal.countdown dark=true}
                        {/if}
                    </div>
                    {if $spNlDeal.code !== ''}
                        <div class="sp-deal__actions">
                            <span class="sp-deal__hint">{$spNlDeal.codeHint|escape:'html'}</span>
                            <span class="sp-deal__code">
                                <span class="sp-deal__code-value">{$spNlDeal.code|escape:'html'}</span>
                                <button type="button" class="sp-deal__copy" data-sp-copy="{$spNlDeal.code|escape:'html'}"
                                        data-sp-copied="{$spNlDeal.copiedLabel|escape:'html'}" title="{$spNlDeal.copyLabel|escape:'html'}">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span class="sp-deal__copy-label">{$spNlDeal.copyLabel|escape:'html'}</span>
                                </button>
                            </span>
                        </div>
                    {/if}
                </section>
            {/if}
            {if $spNlDeal.hasSets}
                {* Set-Konfigurator: Karten lädt newsletter-deal.js per IO (Deal-Seite schaltet die Sitzung frei) *}
                <link rel="stylesheet" href="{$spNlDeal.assetsCss|escape:'html'}">
                <script src="{$spNlDeal.assetsJs|escape:'html'}" defer></script>
                {assign var=spNlDealAssetsDone value=true scope='global'}
                <div class="sp-nld-sets sp-nld-sets--deal" hidden data-sp-nld-sets-deal="{$spNlDeal.id|intval}"></div>
            {/if}
            {if $spNlDeal.deal !== null}
                {* zusätzlicher JTL-Kupon (z. B. Rabatt auf alles) *}
                {$deal = $spNlDeal.deal}
                <section class="sp-deal sp-deal--strip sp-deal--r-md sp-bg-tint sp-nld-deal" data-sp-deal>
                    <div class="sp-deal__icon" aria-hidden="true"><i class="fas fa-percent"></i></div>
                    <div class="sp-deal__body">
                        <span class="sp-kicker sp-deal__kicker">{$spNlDeal.kicker|escape:'html'}</span>
                        <div class="sp-deal__title">{$spNlDeal.discount|escape:'html'} {$spNlDeal.saveLabel|escape:'html'}</div>
                        <div class="sp-deal__sub">
                            {$spNlDeal.hint|escape:'html'}{if $spNlDeal.validLabel !== ''} · {$spNlDeal.validLabel|escape:'html'}{/if}
                        </div>
                        {if $spNlShowCd && !$spNlDeal.prices}
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
                </section>
            {/if}
            {if !$spNlDeal.prices && $spNlDeal.deal === null && $spNlShowCd}
                <div class="sp-nld-countdown">
                    {include file=$spNlCdTpl cd=$spNlDeal.countdown dark=true}
                </div>
            {/if}
            {if $spNlDeal.prices || $spNlDeal.deal !== null}
                <script src="{$spCommonUrl}deal.js?v={$spVersion|escape:'html'}" defer></script>
            {/if}
            {if $spNlShowCd}
                <script src="{$spCommonUrl}countdown.js?v={$spVersion|escape:'html'}" defer></script>
            {/if}
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
