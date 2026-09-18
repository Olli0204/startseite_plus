{* Startseite Plus – gemeinsames Countdown-Snippet
   Parameter: cd (View-Array aus CountdownService), dark (bool: dunkle Ziffern auf hellem Grund), heading (bool: Label als Überschrift) *}
{$dark    = $dark|default:false}
{$heading = $heading|default:false}
{if !empty($cd)}
<div class="sp-cd sp-cd--{$cd.style}{if $dark} sp-cd--dark{/if}{if $heading} sp-cd--stacked{/if}"
     data-sp-until="{$cd.until|escape:'html'}"
     data-sp-expired="{$cd.mode}">
    {if !$cd.expired}
        {if $cd.label !== ''}
            <span class="sp-cd__label{if $heading} sp-cd__label--heading{/if}">{$cd.label|escape:'html'}</span>
        {/if}
        <span class="sp-cd__units">
            <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="d">00</span><span class="sp-cd__lbl">{$cd.units.d|escape:'html'}</span></span>
            <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="h">00</span><span class="sp-cd__lbl">{$cd.units.h|escape:'html'}</span></span>
            <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="m">00</span><span class="sp-cd__lbl">{$cd.units.m|escape:'html'}</span></span>
            <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="s">00</span><span class="sp-cd__lbl">{$cd.units.s|escape:'html'}</span></span>
        </span>
    {/if}
    <span class="sp-cd__expired"{if !$cd.expired || $cd.mode !== 'text'} hidden{/if}>{$cd.text|escape:'html'}</span>
</div>
{/if}
