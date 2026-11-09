{* Startseite Plus – Deal-/Set-Rabatt im Warenkorb unter dem Stückpreis statt als Zeile zwischen den Artikeldaten.
   Daten aus DealPricing::cartInfo() (Session, bei jeder Neuberechnung des Warenkorbs aktualisiert). Ohne Stückpreis-Spalte
   bleibt der Core-Hinweis in der Artikelliste. *}
{block name='basket-cart-items-price-single'}
    {if isset($smarty.session.startseitePlusNlCartInfo2[$oPosition->kArtikel])}{$spCartSet = $smarty.session.startseitePlusNlCartInfo2[$oPosition->kArtikel]}{else}{$spCartSet = null}{/if}
    {if empty($spCartSet) || $oPosition->nPosTyp !== $smarty.const.C_WARENKORBPOS_TYP_ARTIKEL
        || $Einstellungen.kaufabwicklung.bestellvorgang_einzelpreise_anzeigen !== 'Y'}
        {$smarty.block.parent}
    {else}
        {if empty($spCartSetCss)}
            {assign var=spCartSetCss value=true scope='global'}
            <style>
                .sp-cart-set { display: flex; flex-direction: column; align-items: flex-start; gap: .15rem; max-width: 100%;
                    margin: .25rem 0 .75rem; font-size: .8125rem; line-height: 1.35; white-space: normal; }
                .sp-cart-set__regular { color: #707070; text-decoration: line-through; }
                .sp-cart-set__badge { max-width: 100%; padding: .1rem .4rem; border-radius: 3px; background: var(--primary, #FFA54F);
                    color: #2b2b2b; font-weight: 600; overflow-wrap: anywhere; }
                .sp-cart-set__units { color: #707070; }
                .sp-cart-set > * { max-width: 100%; }
            </style>
        {/if}
        {col cols=$cols md=$mdRight xl=2 class="cart-items-single-price"}
            <strong class="cart-items-price-text">
                {lang key="pricePerUnit" section="productDetails"}:
            </strong>{$oPosition->cEinzelpreisLocalized[$NettoPreise][$smarty.session.cWaehrungName]}
            <div class="sp-cart-set"{if $spCartSet.title !== ''} title="{$spCartSet.title|escape:'html'}"{/if}>
                <span class="sp-cart-set__regular">{$spCartSet.regular|escape:'html'}</span>
                <span class="sp-cart-set__badge">{$spCartSet.badge|escape:'html'}</span>
                {if $spCartSet.units !== ''}<span class="sp-cart-set__units">{$spCartSet.units|escape:'html'}</span>{/if}
            </div>
        {/col}
    {/if}
{/block}
{block name='basket-cart-items-product-data-notice'}
    {if isset($smarty.session.startseitePlusNlCartInfo2[$oPosition->kArtikel])}{$spCartSet = $smarty.session.startseitePlusNlCartInfo2[$oPosition->kArtikel]}{else}{$spCartSet = null}{/if}
    {if empty($spCartSet) || $Einstellungen.kaufabwicklung.bestellvorgang_einzelpreise_anzeigen !== 'Y'}
        {$smarty.block.parent}
    {/if}
{/block}
