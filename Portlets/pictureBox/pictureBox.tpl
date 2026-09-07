{* Startseite Plus – Kategorie-Kacheln *}
{$tiles      = $portlet->getTiles($instance)}
{$count      = $tiles|count}
{$cols       = $portlet->getColumns($instance)}
{$divisor    = $portlet->getImageDivisor($instance)}
{$aspect     = $portlet->getKey($instance, 'aspect', '3-2')}
{$gap        = $portlet->getKey($instance, 'gap', 'sm')}
{$hover      = $portlet->getKey($instance, 'hover', 'zoom')}
{$overlay    = $portlet->getKey($instance, 'overlay', 'gradient-bottom')}
{$titlePos   = $portlet->getKey($instance, 'title-position', 'center')}
{$titleSize  = $portlet->getKey($instance, 'title-size', 'md')}
{$titleStyle = $portlet->getKey($instance, 'title-style', 'plain')}
{$linksStyle = $portlet->getKey($instance, 'links-style', 'pills')}
{$rounded    = $portlet->getKey($instance, 'rounded', 'sm')}
{$widthMode  = $portlet->getKey($instance, 'width', 'container')}
{$wrap       = !$inContainer && $widthMode === 'container'}
{$fullBleed  = $inContainer && $widthMode === 'full'}
{$rootStyle  = $portlet->rootStyle($instance, [
    'accent'  => $portlet->getAccent($instance),
    'cols-xs' => $cols.xs,
    'cols-sm' => $cols.sm,
    'cols'    => $cols.lg
])}

{if $count === 0}
    <div class="sp-placeholder" style="{$instance->getStyleString()}">
        <i class="fas fa-th-large"></i>
        <span>Kategorie-Kacheln – bitte Kacheln hinzufügen</span>
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    <div class="sp-tiles sp-tiles--aspect-{$aspect} sp-tiles--gap-{$gap} sp-tiles--hover-{$hover} sp-tiles--overlay-{$overlay} sp-tiles--title-{$titlePos} sp-tiles--size-{$titleSize} sp-tiles--tstyle-{$titleStyle} sp-tiles--links-{$linksStyle} sp-tiles--r-{$rounded}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
         style="{$rootStyle}"
         {$instance->getAnimationDataAttributeString()}>
        {foreach $tiles as $tile}
            {$main = $portlet->safeUrl($tile.link)}
            {if $main === ''}{$main = $portlet->safeUrl($tile.link3)}{/if}
            {if $main === ''}{$main = $portlet->safeUrl($tile.link2)}{/if}
            {$link1       = $portlet->safeUrl($tile.link3)}
            {$link2       = $portlet->safeUrl($tile.link2)}
            {$mediaIsLink = $main !== '' && !$isPreview}
            {$img         = $instance->getImageAttributes($tile.url, $tile.alt, $tile.title, $divisor)}
            <div class="sp-tile">
                {if $mediaIsLink}
                    <a class="sp-tile__media" href="{$main|escape:'html'}"{if $tile.title !== ''} aria-label="{$tile.title|escape:'html'}"{/if}>
                {else}
                    <div class="sp-tile__media">
                {/if}
                    {image src=$img.src
                           srcset=$img.srcset
                           sizes=$img.srcsizes
                           alt=$img.alt|escape:'html'
                           class="sp-tile__img"
                           width=$img.realWidth
                           height=$img.realHeight
                           lazy=true
                           webp=true}
                {if $mediaIsLink}
                    </a>
                {else}
                    </div>
                {/if}
                <div class="sp-tile__overlay" aria-hidden="true"></div>
                <div class="sp-tile__content">
                    {if $tile.title !== '' || $tile.desc !== ''}
                        <div class="sp-tile__head">
                            {if $tile.title !== ''}
                                <h3 class="sp-tile__title">
                                    {if $mediaIsLink}<a href="{$main|escape:'html'}">{/if}{$tile.title|escape:'html'}{if $mediaIsLink}</a>{/if}
                                </h3>
                            {/if}
                            {if $tile.desc !== ''}
                                <p class="sp-tile__desc">{$tile.desc|escape:'html'}</p>
                            {/if}
                        </div>
                    {/if}
                    {if $tile.kat !== '' || $tile.kat2 !== ''}
                        <div class="sp-tile__links">
                            {if $tile.kat !== ''}
                                <a class="sp-tile__link"{if $link1 !== '' && !$isPreview} href="{$link1|escape:'html'}"{/if}>{$tile.kat|escape:'html'}</a>
                            {/if}
                            {if $tile.kat !== '' && $tile.kat2 !== ''}
                                <span class="sp-tile__sep" aria-hidden="true">/</span>
                            {/if}
                            {if $tile.kat2 !== ''}
                                <a class="sp-tile__link"{if $link2 !== '' && !$isPreview} href="{$link2|escape:'html'}"{/if}>{$tile.kat2|escape:'html'}</a>
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>
    {if $wrap}</div>{/if}
{/if}
