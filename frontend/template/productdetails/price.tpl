{* Startseite Plus – Newsletter-Deal-Preis unter dem Preis (Artikelliste, Artikelseite). Nur sichtbar, wenn der Kunde
   einen laufenden Deal freigeschaltet hat ($spNlDealPrices aus HOOK_LETZTERINCLUDE_INC / HOOK_IO_HANDLE_REQUEST). *}
{block name='productdetails-price' append}
    {if !empty($spNlDealPrices) && isset($Artikel) && !empty($Artikel->kArtikel)}
        {$spNlEntry = null}
        {if isset($spNlDealPrices[$Artikel->kArtikel])}
            {$spNlEntry = $spNlDealPrices[$Artikel->kArtikel]}
        {elseif !empty($Artikel->kVaterArtikel) && isset($spNlDealPrices[$Artikel->kVaterArtikel])}
            {$spNlEntry = $spNlDealPrices[$Artikel->kVaterArtikel]}
        {/if}
        {if $spNlEntry !== null}
            {if empty($spNlDealCssDone)}
                <link rel="stylesheet" href="{$spNlDealCss|escape:'html'}">
                {assign var=spNlDealCssDone value=true scope='global'}
            {/if}
            <div class="sp-nld-price{if ($tplscope|default:'') === 'detail'} sp-nld-price--detail{/if}">
                {if $spNlEntry.price !== ''}
                    <div class="sp-nld-price__row">
                        <span class="sp-nld-price__label">{$spNlDealLabels.price|escape:'html'}</span>
                        <strong class="sp-nld-price__value">{$spNlEntry.price|escape:'html'}</strong>
                    </div>
                {/if}
                {foreach $spNlEntry.sets as $spNlSet}
                    <div class="sp-nld-price__row sp-nld-price__row--set">
                        <span class="sp-nld-price__label">{$spNlSet.label|escape:'html'}</span>
                        <strong class="sp-nld-price__value">{$spNlSet.price|escape:'html'}</strong>
                    </div>
                {/foreach}
            </div>
        {/if}
    {/if}
{/block}
