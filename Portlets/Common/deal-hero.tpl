{* Startseite Plus – Deal-Karte in einem Slide des Hero-Sliders.
   Parameter: deal (DealService::view()), isPreview, titleTag, portlet
   Smartphone: kompakte Karte (Titel, Preis, Button) – siehe .sp-deal--hero in deal.css *}
<div class="sp-deal sp-deal--hero{if !empty($deal.canAdd)} sp-deal--has-add{/if}" data-sp-deal>
    {if $isPreview && !$deal.show}
        <div class="sp-deal-warn">
            <strong>Wird im Shop nicht angezeigt:</strong>
            {foreach $deal.problems as $problem} {$problem|escape:'html'}{/foreach}
        </div>
    {/if}
    {if $isPreview && !empty($deal.notes)}
        <div class="sp-deal-warn">{foreach $deal.notes as $note}<div>{$note|escape:'html'}</div>{/foreach}</div>
    {/if}
    {if !empty($deal.found)}
        {if $deal.count > 0}
            <div class="sp-deal__products">
                {foreach $deal.items as $item}
                    {if !$item@first}<span class="sp-deal__plus" aria-hidden="true">+</span>{/if}
                    <a class="sp-deal__product"{if $item.url !== '' && !$isPreview} href="{$item.url|escape:'html'}"{/if} title="{$item.name|escape:'html'}">
                        {if $item.image !== ''}
                            <img src="{$item.image|escape:'html'}" alt="{$item.name|escape:'html'}" loading="lazy" width="80" height="80">
                        {else}
                            <i class="fas fa-box-open" aria-hidden="true"></i>
                        {/if}
                    </a>
                {/foreach}
            </div>
        {/if}
        <div class="sp-deal__body">
            {if $deal.kicker !== ''}<span class="sp-kicker sp-deal__kicker">{$deal.kicker|escape:'html'}</span>{/if}
            <{$titleTag} class="sp-deal__title">{$deal.title|escape:'html'}</{$titleTag}>
            {if $deal.text !== ''}<p class="sp-deal__text">{$deal.text|escape:'html'}</p>{/if}
            {if $deal.showPrices}
                <div class="sp-deal__price">
                    <s class="sp-deal__sum">{$deal.sum|escape:'html'}</s>
                    <span class="sp-deal__total">{$deal.total|escape:'html'}</span>
                    <span class="sp-deal__with">{$deal.withCode|escape:'html'}</span>
                </div>
            {/if}
            {if $deal.showValid}
                <div class="sp-deal__meta"><span class="sp-deal__valid"><i class="far fa-clock" aria-hidden="true"></i> {$deal.validLabel|escape:'html'}</span></div>
            {/if}
        </div>
        <div class="sp-deal__actions">
            {include file=$portlet->getCommonTemplate('deal-button.tpl') deal=$deal isPreview=$isPreview}
            {include file=$portlet->getCommonTemplate('deal-code.tpl') deal=$deal}
            <span class="sp-deal__hint">{$deal.autoHint|escape:'html'}</span>
        </div>
    {/if}
    <div class="sp-deal__msg" role="alert" hidden></div>
</div>
