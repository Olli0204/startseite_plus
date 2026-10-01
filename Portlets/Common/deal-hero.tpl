{* Startseite Plus – Deal-Karte in einem Slide des Hero-Sliders.
   Parameter: deal (DealService::view()), isPreview, titleTag, portlet
   Zeigt Artikel mit Bild, Name, Variante und Preis, Bundle-Preis, Ersparnis, Gültigkeit und Code; der Button führt
   auf die gewählte Kategorie bzw. den Link (sonst "In den Warenkorb"). Was bei wenig Platz entfällt, regelt deal.css
   per Container Query (.sp-deal--hero). *}
<div class="sp-deal sp-deal--hero{if !empty($deal.hasAction)} sp-deal--has-action{/if}" data-sp-deal>
    {if $isPreview && !$deal.show}
        <div class="sp-deal-warn">
            <strong>Wird im Shop nicht angezeigt:</strong>
            {foreach $deal.problems as $problem} {$problem|escape:'html'}{/foreach}
        </div>
    {/if}
    {if $isPreview && !empty($deal.notes) && empty($deal.link)}
        <div class="sp-deal-warn">{foreach $deal.notes as $note}<div>{$note|escape:'html'}</div>{/foreach}</div>
    {/if}
    {if !empty($deal.found)}
        {if $deal.count > 0}
            <ul class="sp-deal__items">
                {foreach $deal.items as $item}
                    <li class="sp-deal__item">
                        <a class="sp-deal__item-link"{if $item.url !== '' && !$isPreview} href="{$item.url|escape:'html'}"{/if} title="{$item.name|escape:'html'}">
                            <span class="sp-deal__item-img">
                                {if $item.image !== ''}
                                    <img src="{$item.image|escape:'html'}" alt="{$item.name|escape:'html'}" loading="lazy" width="72" height="72">
                                {else}
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                {/if}
                            </span>
                            <span class="sp-deal__item-info">
                                <span class="sp-deal__item-name">{$item.name|escape:'html'}</span>
                                {if $item.variant !== ''}<span class="sp-deal__item-variant">{$item.variant|escape:'html'}</span>{/if}
                                {if $deal.showPrices}<span class="sp-deal__item-price">{$item.price|escape:'html'}</span>{/if}
                            </span>
                        </a>
                    </li>
                {/foreach}
            </ul>
        {/if}
        <div class="sp-deal__body">
            {if $deal.kicker !== ''}<span class="sp-kicker sp-deal__kicker">{$deal.kicker|escape:'html'}</span>{/if}
            <{$titleTag} class="sp-deal__title">{$deal.title|escape:'html'}</{$titleTag}>
            {if $deal.count > 0}
                <p class="sp-deal__names">{foreach $deal.items as $item}{if !$item@first}{$deal.namesSep|default:' + '}{/if}{$item.name|escape:'html'}{if $item.variant !== ''} ({$item.variant|escape:'html'}){/if}{/foreach}</p>
            {/if}
            {if $deal.text !== ''}<p class="sp-deal__text">{$deal.text|escape:'html'}</p>{/if}
            {if $deal.showPrices}
                <div class="sp-deal__price">
                    <s class="sp-deal__sum">{$deal.sum|escape:'html'}</s>
                    <span class="sp-deal__total">{$deal.total|escape:'html'}</span>
                    <span class="sp-deal__with">{$deal.withCode|escape:'html'}</span>
                    {if $deal.savingLabel !== ''}<span class="sp-deal__saving">{$deal.savingLabel|escape:'html'}</span>{/if}
                </div>
            {/if}
            {if $deal.showValid || $deal.code !== ''}
                <div class="sp-deal__meta">
                    {if $deal.code !== ''}<span class="sp-deal__meta-code"><i class="fas fa-tag" aria-hidden="true"></i> {$deal.codeLabel|escape:'html'}: <strong>{$deal.code|escape:'html'}</strong></span>{/if}
                    {if $deal.showValid}<span class="sp-deal__valid"><i class="far fa-clock" aria-hidden="true"></i> {$deal.validLabel|escape:'html'}</span>{/if}
                </div>
            {/if}
        </div>
        <div class="sp-deal__actions">
            {if $deal.link !== ''}
                <a class="sp-btn sp-btn--primary sp-deal__cta"{if !$isPreview} href="{$deal.link|escape:'html'}"{/if}>
                    <span>{$deal.linkLabel|escape:'html'}</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            {else}
                {include file=$portlet->getCommonTemplate('deal-button.tpl') deal=$deal isPreview=$isPreview}
            {/if}
            {include file=$portlet->getCommonTemplate('deal-code.tpl') deal=$deal}
            <span class="sp-deal__hint">{$deal.autoHint|escape:'html'}</span>
        </div>
    {/if}
    <div class="sp-deal__msg" role="alert" hidden></div>
</div>
