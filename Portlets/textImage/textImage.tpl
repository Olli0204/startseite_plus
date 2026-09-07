{* Startseite Plus – Text & Bild *}
{$src        = $portlet->getString($instance, 'src')}
{$alt        = $portlet->getString($instance, 'alt')}
{$hasImage   = $src !== ''}
{$imgPos     = $portlet->getKey($instance, 'image-position', 'left')}
{$imgWidth   = $portlet->getNum($instance, 'image-width', 50, 40, 60)}
{$imgStyle   = $portlet->getKey($instance, 'image-style', 'rounded')}
{$valign     = $portlet->getKey($instance, 'vertical-align', 'center')}
{$kicker     = $portlet->getString($instance, 'kicker')}
{$title      = $portlet->getString($instance, 'title')}
{$titleTag   = $portlet->getKey($instance, 'title-tag', 'h2')}
{$titleSize  = $portlet->getKey($instance, 'title-size', 'md')}
{$text       = $portlet->getString($instance, 'text')}
{$btnLabel   = $portlet->getString($instance, 'btn-label')}
{$btnUrl     = $portlet->safeUrl($instance->getProperty('btn-url'))}
{$btnStyle   = $portlet->getKey($instance, 'btn-style', 'primary')}
{$stats      = $portlet->getStats($instance)}
{$statsStyle = $portlet->getKey($instance, 'stats-style', 'inline')}
{$bg         = $portlet->getKey($instance, 'bg', 'none')}
{$padding    = $portlet->getKey($instance, 'padding', 'md')}
{$widthMode  = $portlet->getKey($instance, 'width', 'container')}
{$wrap       = !$inContainer && $widthMode === 'container'}
{$fullBleed  = $inContainer && $widthMode === 'full'}
{$hasContent = $kicker !== '' || $title !== '' || $text !== '' || $btnLabel !== '' || $stats|count > 0}
{$rootStyle  = $portlet->rootStyle($instance, ['accent' => $portlet->getAccent($instance)])}

{if $isPreview && !$hasImage && !$hasContent}
    <div class="sp-placeholder" style="{$instance->getStyleString()}">
        <i class="fas fa-columns"></i>
        <span>Text &amp; Bild – bitte Inhalte pflegen</span>
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    <section class="sp-ti sp-ti--img-{$imgPos} sp-ti--imgw-{$imgWidth} sp-ti--istyle-{$imgStyle} sp-ti--valign-{$valign} sp-ti--pad-{$padding}{if !$hasImage} sp-ti--no-image{/if}{if $bg !== 'none'} sp-ti--bg-{$bg} sp-bg-{$bg}{/if}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
             style="{$rootStyle}"
             {$instance->getAnimationDataAttributeString()}>
        <div class="sp-ti__grid">
            {if $hasImage}
                {$img = $instance->getImageAttributes($src, $alt, $title, $portlet->getImageDivisor($instance))}
                <div class="sp-ti__media">
                    <div class="sp-ti__frame">
                        {image src=$img.src
                               srcset=$img.srcset
                               sizes=$img.srcsizes
                               alt=$img.alt|escape:'html'
                               class="sp-ti__img"
                               width=$img.realWidth
                               height=$img.realHeight
                               lazy=true
                               webp=true}
                    </div>
                </div>
            {/if}
            <div class="sp-ti__body">
                {if $kicker !== ''}
                    <span class="sp-kicker sp-ti__kicker">{$kicker|escape:'html'}</span>
                {/if}
                {if $title !== ''}
                    <{$titleTag} class="sp-ti__title sp-ti__title--{$titleSize}">{$title|escape:'html'}</{$titleTag}>
                {/if}
                {if $text !== ''}
                    <div class="sp-ti__text">{$text}</div>
                {/if}
                {if $stats|count > 0}
                    <div class="sp-ti__stats sp-ti__stats--{$statsStyle}">
                        {foreach $stats as $stat}
                            <div class="sp-ti__stat">
                                <span class="sp-ti__stat-value">{$stat.value|escape:'html'}</span>
                                {if $stat.label !== ''}
                                    <span class="sp-ti__stat-label">{$stat.label|escape:'html'}</span>
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                {/if}
                {if $btnLabel !== ''}
                    <a class="sp-btn sp-btn--{$btnStyle} sp-ti__btn"{if $btnUrl !== '' && !$isPreview} href="{$btnUrl|escape:'html'}"{/if}>{$btnLabel|escape:'html'}</a>
                {/if}
            </div>
        </div>
    </section>
    {if $wrap}</div>{/if}
{/if}
