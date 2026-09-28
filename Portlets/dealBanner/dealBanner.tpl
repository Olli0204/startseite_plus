{* Startseite Plus – Deal-Banner (Kupon-/Bundle-Aktion) *}
{$deal      = $portlet->getDeal($instance)}
{$layout    = $portlet->getKey($instance, 'layout', 'bundle')}
{$bg        = $portlet->getKey($instance, 'bg', 'tint')}
{$rounded   = $portlet->getKey($instance, 'rounded', 'md')}
{$widthMode = $portlet->getKey($instance, 'width', 'container')}
{$wrap      = !$inContainer && $widthMode === 'container'}
{$fullBleed = $inContainer && $widthMode === 'full'}
{$rootStyle = $portlet->rootStyle($instance, ['accent' => $portlet->getAccent($instance)])}
{$cdTpl     = $portlet->getCountdownTemplate()}

{if $deal === null}
    {if $isPreview}
        <div class="sp-placeholder" style="{$instance->getStyleString()}">
            <i class="fas fa-tags"></i>
            <span>Deal-Banner – bitte Kupon-Code eintragen</span>
        </div>
    {/if}
{elseif !$deal.show && !$isPreview}
    {* Kupon ungültig, abgelaufen oder aufgebraucht – Banner wird nicht ausgegeben *}
{elseif !$deal.show && empty($deal.found)}
    <div class="sp-placeholder sp-deal-warn" style="{$instance->getStyleString()}">
        <i class="fas fa-exclamation-triangle"></i>
        {foreach $deal.problems as $problem}<span>{$problem|escape:'html'}</span>{/foreach}
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    {if $isPreview && !$deal.show}
        <div class="sp-deal-warn">
            <strong>Wird im Shop nicht angezeigt:</strong>
            {foreach $deal.problems as $problem} {$problem|escape:'html'}{/foreach}
        </div>
    {/if}
    {if $isPreview && !empty($deal.notes) && $portlet->isTrue($instance, 'show-button')}
        <div class="sp-deal-warn">
            {foreach $deal.notes as $note}<div>{$note|escape:'html'}</div>{/foreach}
        </div>
    {/if}
    <section class="sp-deal sp-deal--{$layout} sp-deal--r-{$rounded} sp-bg-{$bg}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
             style="{$rootStyle}"
             data-sp-deal
             {$instance->getAnimationDataAttributeString()}>

        {* ---------- Code mit Kopieren-Button (in allen Layouts) ---------- *}
        {capture name=spDealCode}
            <span class="sp-deal__code">
                <span class="sp-deal__code-value">{$deal.code|escape:'html'}</span>
                <button type="button" class="sp-deal__copy" data-sp-copy="{$deal.code|escape:'html'}"
                        data-sp-copied="{$deal.copiedLabel|escape:'html'}" title="{$deal.copyLabel|escape:'html'}">
                    <i class="far fa-copy" aria-hidden="true"></i>
                    <span class="sp-deal__copy-label">{$deal.copyLabel|escape:'html'}</span>
                </button>
            </span>
        {/capture}

        {capture name=spDealButton}
            {if $deal.canAdd}
                <button type="button" class="sp-btn sp-btn--primary sp-deal__add"
                        data-sp-deal-add="{$deal.ids|escape:'html'}"
                        data-sp-code="{$deal.code|escape:'html'}"
                        data-sp-token="{$deal.token|escape:'html'}"
                        {if $isPreview}disabled{/if}>
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                    <span>{$deal.btnLabel|escape:'html'}</span>
                </button>
            {/if}
        {/capture}

        {capture name=spDealMeta}
            {if $deal.showValid || $deal.countdown !== null}
                <div class="sp-deal__meta">
                    {if $deal.showValid}<span class="sp-deal__valid"><i class="far fa-clock" aria-hidden="true"></i> {$deal.validLabel|escape:'html'}</span>{/if}
                    {if $deal.countdown !== null && !$deal.countdown.expired}
                        {include file=$cdTpl cd=$deal.countdown dark=($bg !== 'dark' && $bg !== 'accent')}
                    {/if}
                </div>
            {/if}
        {/capture}

        {if $layout === 'strip'}
            <div class="sp-deal__icon" aria-hidden="true"><i class="fas fa-percent"></i></div>
            <div class="sp-deal__body">
                <div class="sp-deal__title">{$deal.title|escape:'html'}</div>
                <div class="sp-deal__sub">
                    {if $deal.text !== ''}{$deal.text|escape:'html'} · {/if}{$deal.autoHint|escape:'html'}{if $deal.showValid} · {$deal.validLabel|escape:'html'}{/if}
                </div>
                {if $deal.countdown !== null && !$deal.countdown.expired}
                    {include file=$cdTpl cd=$deal.countdown dark=($bg !== 'dark' && $bg !== 'accent')}
                {/if}
            </div>
            <div class="sp-deal__actions">
                {$smarty.capture.spDealCode}
                {$smarty.capture.spDealButton}
            </div>

        {elseif $layout === 'ticket'}
            <div class="sp-deal__main">
                {if $deal.kicker !== ''}<span class="sp-kicker sp-deal__kicker">{$deal.kicker|escape:'html'}</span>{/if}
                <div class="sp-deal__title">{$deal.title|escape:'html'}</div>
                {if $deal.text !== ''}<p class="sp-deal__text">{$deal.text|escape:'html'}</p>{/if}
                {if $deal.showPrices}
                    <div class="sp-deal__price">
                        <span class="sp-deal__total">{$deal.total|escape:'html'}</span>
                        <span class="sp-deal__sum">{$deal.insteadOf|escape:'html'} <s>{$deal.sum|escape:'html'}</s></span>
                    </div>
                {/if}
                {$smarty.capture.spDealMeta}
                {$smarty.capture.spDealButton}
            </div>
            <div class="sp-deal__stub">
                <span class="sp-deal__stub-label">{$deal.codeLabel|escape:'html'}</span>
                {$smarty.capture.spDealCode}
                <span class="sp-deal__stub-hint">{$deal.autoHint|escape:'html'}</span>
            </div>

        {else}
            {if $deal.count > 0}
                <div class="sp-deal__products">
                    {foreach $deal.items as $item}
                        {if !$item@first}<span class="sp-deal__plus" aria-hidden="true">+</span>{/if}
                        <a class="sp-deal__product"{if $item.url !== '' && !$isPreview} href="{$item.url|escape:'html'}"{/if} title="{$item.name|escape:'html'}">
                            {if $item.image !== ''}
                                <img src="{$item.image|escape:'html'}" alt="{$item.name|escape:'html'}" loading="lazy" width="96" height="96">
                            {else}
                                <i class="fas fa-box-open" aria-hidden="true"></i>
                            {/if}
                        </a>
                    {/foreach}
                </div>
            {/if}
            <div class="sp-deal__body">
                {if $deal.kicker !== ''}<span class="sp-kicker sp-deal__kicker">{$deal.kicker|escape:'html'}</span>{/if}
                <div class="sp-deal__title">{$deal.title|escape:'html'}</div>
                {if $deal.count > 0}
                    <div class="sp-deal__names">
                        {foreach $deal.items as $item}{if !$item@first} + {/if}{$item.name|escape:'html'}{/foreach}
                    </div>
                {/if}
                {if $deal.text !== ''}<p class="sp-deal__text">{$deal.text|escape:'html'}</p>{/if}
                {if $deal.showPrices}
                    <div class="sp-deal__price">
                        <s class="sp-deal__sum">{$deal.sum|escape:'html'}</s>
                        <span class="sp-deal__total">{$deal.total|escape:'html'}</span>
                        <span class="sp-deal__with">{$deal.withCode|escape:'html'}</span>
                    </div>
                {/if}
                {$smarty.capture.spDealMeta}
            </div>
            <div class="sp-deal__actions">
                {$smarty.capture.spDealButton}
                {$smarty.capture.spDealCode}
                <span class="sp-deal__hint">{$deal.autoHint|escape:'html'}</span>
            </div>
        {/if}
        <div class="sp-deal__msg" role="alert" hidden></div>
    </section>
    {if $wrap}</div>{/if}
{/if}
