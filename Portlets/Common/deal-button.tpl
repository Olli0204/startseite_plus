{* Startseite Plus – "In den Warenkorb" inkl. Code-Einlösung (Deal-Banner, Deal-Slides). Parameter: deal, isPreview *}
{if $deal.canAdd}
    <button type="button" class="sp-btn sp-btn--primary sp-deal__add"
            data-sp-deal-add="{$deal.ids|escape:'html'}"
            data-sp-code="{$deal.code|escape:'html'}"
            {if $isPreview}disabled{/if}>
        <i class="fas fa-shopping-cart" aria-hidden="true"></i>
        <span>{$deal.btnLabel|escape:'html'}</span>
    </button>
{/if}
