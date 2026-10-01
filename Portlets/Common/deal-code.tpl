{* Startseite Plus – Rabattcode mit Kopieren-Button (Deal-Banner, Deal-Slides). Parameter: deal (ohne Code – z. B. Slide „Newsletter-Aktion“ – entfällt der Chip) *}
{if $deal.code|default:'' !== ''}
<span class="sp-deal__code">
    <span class="sp-deal__code-value">{$deal.code|escape:'html'}</span>
    <button type="button" class="sp-deal__copy" data-sp-copy="{$deal.code|escape:'html'}"
            data-sp-copied="{$deal.copiedLabel|escape:'html'}" title="{$deal.copyLabel|escape:'html'}">
        <i class="far fa-copy" aria-hidden="true"></i>
        <span class="sp-deal__copy-label">{$deal.copyLabel|escape:'html'}</span>
    </button>
</span>
{/if}
