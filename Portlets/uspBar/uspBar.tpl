{* Startseite Plus – Vorteile-Leiste *}
{$items     = $portlet->getUspItems($instance)}
{$count     = $items|count}
{$layout    = $portlet->getKey($instance, 'layout', 'strip')}
{$iconStyle = $portlet->getKey($instance, 'icon-style', 'circle')}
{$iconSize  = $portlet->getKey($instance, 'icon-size', 'md')}
{$align     = $portlet->getKey($instance, 'align', 'center')}
{$bg        = $portlet->getKey($instance, 'bg', 'none')}
{$divider   = $portlet->isTrue($instance, 'divider')}
{$widthMode = $portlet->getKey($instance, 'width', 'container')}
{$wrap      = !$inContainer && $widthMode === 'container'}
{$fullBleed = $inContainer && $widthMode === 'full'}
{$rootStyle = $portlet->rootStyle($instance, [
    'accent' => $portlet->getAccent($instance),
    'cols'   => $portlet->getColumnCount($instance)
])}

{if $count === 0}
    <div class="sp-placeholder" style="{$instance->getStyleString()}">
        <i class="fas fa-award"></i>
        <span>Vorteile-Leiste – bitte Einträge hinzufügen</span>
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    <div class="sp-usp sp-usp--{$layout} sp-usp--icon-{$iconStyle} sp-usp--isize-{$iconSize} sp-usp--{$align} sp-usp--bg-{$bg}{if $bg !== 'none'} sp-bg-{$bg}{/if}{if $divider} sp-usp--divider{/if}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
         style="{$rootStyle}"
         {$instance->getAnimationDataAttributeString()}>
        <div class="sp-usp__list">
            {foreach $items as $item}
                {$link   = $portlet->safeUrl($item.link)}
                {$icon   = $portlet->safeIconClass($item.icon)}
                {$isLink = $link !== '' && !$isPreview}
                {if $isLink}
                    <a class="sp-usp__item" href="{$link|escape:'html'}">
                {else}
                    <div class="sp-usp__item">
                {/if}
                    {if $item.url !== '' || $icon !== ''}
                        <span class="sp-usp__icon" aria-hidden="true">
                            {if $item.url !== ''}
                                {$img = $instance->getImageAttributes($item.url, '', '')}
                                {image src=$img.src alt="" class="sp-usp__img" lazy=true webp=true}
                            {else}
                                <i class="{$icon|escape:'html'}"></i>
                            {/if}
                        </span>
                    {/if}
                    <span class="sp-usp__body">
                        {if $item.title !== ''}
                            <span class="sp-usp__title">{$item.title|escape:'html'}</span>
                        {/if}
                        {if $item.text !== ''}
                            <span class="sp-usp__text">{$item.text|escape:'html'}</span>
                        {/if}
                    </span>
                {if $isLink}
                    </a>
                {else}
                    </div>
                {/if}
            {/foreach}
        </div>
    </div>
    {if $wrap}</div>{/if}
{/if}
