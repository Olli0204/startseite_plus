{* Startseite Plus – Überschrift *}
{$text      = $portlet->getString($instance, 'text')}
{$level     = $portlet->getNum($instance, 'level', 2, 1, 6)}
{$tag       = 'h'|cat:$level}
{$align     = $portlet->getKey($instance, 'align', 'center')}
{$style     = $portlet->getKey($instance, 'style', 'lines')}
{$size      = $portlet->getKey($instance, 'size', 'lg')}
{$upper     = $portlet->isTrue($instance, 'uppercase')}
{$kicker    = $portlet->getString($instance, 'kicker')}
{$subtitle  = $portlet->getString($instance, 'subtitle')}
{$url       = $portlet->safeUrl($instance->getProperty('url'))}
{$isLink    = $url !== '' && !$isPreview}
{$rootStyle = $portlet->rootStyle($instance, ['accent' => $portlet->getAccent($instance)])}
{if $text === '' && $isPreview}
    {$text = 'Überschrift'}
{/if}

{if !$inContainer}<div class="container">{/if}
<div class="sp-heading sp-heading--{$style} sp-heading--{$align} sp-heading--{$size}{if $upper} sp-heading--upper{/if} {$portlet->rootClasses($instance)}"
     style="{$rootStyle}"
     {$instance->getAnimationDataAttributeString()}>
    {if $kicker !== ''}
        <span class="sp-kicker sp-heading__kicker">{$kicker|escape:'html'}</span>
    {/if}
    <{$tag} class="sp-heading__title">
        {if $isLink}<a class="sp-heading__link" href="{$url|escape:'html'}">{/if}
        <span class="sp-heading__text">{$text|escape:'html'}</span>
        {if $isLink}</a>{/if}
    </{$tag}>
    {if $subtitle !== ''}
        <p class="sp-heading__subtitle">{$subtitle|escape:'html'}</p>
    {/if}
</div>
{if !$inContainer}</div>{/if}
