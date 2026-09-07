{* Startseite Plus – Hero-Slider (Bootstrap-4-Carousel aus NOVA) *}
{$uid        = $instance->getUid()}
{$slides     = $portlet->getSlides($instance)}
{$count      = $slides|count}
{$aspect     = $portlet->getKey($instance, 'aspect', 'natural')}
{$aspectM    = $portlet->getKey($instance, 'aspect-mobile', 'same')}
{$overlay    = $portlet->getKey($instance, 'overlay', 'none')}
{$capPos     = $portlet->getKey($instance, 'caption-position', 'center')}
{$capStyle   = $portlet->getKey($instance, 'caption-style', 'plain')}
{$titleSize  = $portlet->getKey($instance, 'title-size', 'md')}
{$titleTag   = $portlet->getKey($instance, 'title-tag', 'h2')}
{$btnStyle   = $portlet->getKey($instance, 'button-style', 'pill')}
{$transition = $portlet->getKey($instance, 'transition', 'slide')}
{$interval   = $portlet->getNum($instance, 'interval', 6000, 1000)}
{$autoplay   = $portlet->isTrue($instance, 'autoplay')}
{$pauseHover = $portlet->isTrue($instance, 'pause-hover')}
{$showArrows = $portlet->isTrue($instance, 'show-arrows') && $count > 1}
{$showDots   = $portlet->isTrue($instance, 'show-dots') && $count > 1}
{$widthMode  = $portlet->getKey($instance, 'width', 'full')}
{$wrap       = !$inContainer && $widthMode === 'container'}
{$fullBleed  = $inContainer && $widthMode === 'full'}
{$rootStyle  = $portlet->rootStyle($instance, ['accent' => $portlet->getAccent($instance), 'interval' => $interval|cat:'ms'])}

{if $count === 0}
    <div class="sp-placeholder" style="{$instance->getStyleString()}">
        <i class="fas fa-images"></i>
        <span>Hero-Slider – bitte Slides hinzufügen</span>
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    <div id="{$uid}"
         class="sp-hero carousel slide sp-hero--aspect-{$aspect} sp-hero--m-aspect-{$aspectM} sp-hero--overlay-{$overlay} sp-hero--cap-{$capPos} sp-hero--capstyle-{$capStyle} sp-hero--size-{$titleSize}{if $transition !== 'slide'} carousel-fade{/if}{if $transition === 'kenburns'} sp-hero--kenburns{/if}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
         style="{$rootStyle}"
         {$instance->getAnimationDataAttributeString()}
         {if !$isPreview && $autoplay && $count > 1}data-ride="carousel"{/if}
         data-interval="{if $isPreview || !$autoplay}false{else}{$interval}{/if}"
         data-pause="{if $pauseHover}hover{else}false{/if}"
         data-touch="true"
         data-keyboard="true">
        {if $showDots}
            <ol class="carousel-indicators sp-hero__dots">
                {foreach $slides as $i => $slide}
                    <li data-target="#{$uid}" data-slide-to="{$i}"{if $i === 0} class="active"{/if}></li>
                {/foreach}
            </ol>
        {/if}
        <div class="carousel-inner">
            {foreach $slides as $i => $slide}
                {$link        = $portlet->safeUrl($slide.link)}
                {$hasButton   = $slide.button !== ''}
                {$mediaIsLink = $link !== '' && !$hasButton && !$isPreview}
                {$hasCaption  = $slide.title !== '' || $slide.kicker !== '' || $slide.desc !== '' || $hasButton}
                {$focus       = $portlet->cssKey($slide.focus, 'center')}
                {$lazy        = $i > 0}
                {$img         = $instance->getImageAttributes($slide.url, $slide.alt, $slide.title)}
                <div class="carousel-item{if $i === 0} active{/if}">
                    {if $mediaIsLink}
                        <a class="sp-hero__media" href="{$link|escape:'html'}"{if $slide.title !== ''} title="{$slide.title|escape:'html'}"{/if}>
                    {else}
                        <div class="sp-hero__media">
                    {/if}
                        {image src=$img.src
                               srcset=$img.srcset
                               sizes=$img.srcsizes
                               alt=$img.alt|escape:'html'
                               class="sp-hero__img sp-hero__img--focus-{$focus}"
                               width=$img.realWidth
                               height=$img.realHeight
                               lazy=$lazy
                               webp=true}
                    {if $mediaIsLink}
                        </a>
                    {else}
                        </div>
                    {/if}
                    {if $overlay !== 'none'}
                        <div class="sp-hero__overlay" aria-hidden="true"></div>
                    {/if}
                    {if $hasCaption}
                        <div class="sp-hero__caption">
                            <div class="sp-hero__caption-inner">
                                {if $slide.kicker !== ''}
                                    <span class="sp-kicker sp-hero__kicker">{$slide.kicker|escape:'html'}</span>
                                {/if}
                                {if $slide.title !== ''}
                                    <{$titleTag} class="sp-hero__title">{$slide.title|escape:'html'}</{$titleTag}>
                                {/if}
                                {if $slide.desc !== ''}
                                    <p class="sp-hero__text">{$slide.desc|escape:'html'}</p>
                                {/if}
                                {if $hasButton}
                                    <a class="sp-btn sp-btn--{$btnStyle} sp-hero__btn"{if $link !== '' && !$isPreview} href="{$link|escape:'html'}"{/if}>{$slide.button|escape:'html'}</a>
                                {/if}
                            </div>
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
        {if $showArrows}
            <a class="carousel-control-prev sp-hero__arrow" href="#{$uid}" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="sr-only">Zurück</span>
            </a>
            <a class="carousel-control-next sp-hero__arrow" href="#{$uid}" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">Weiter</span>
            </a>
        {/if}
    </div>
    {if $wrap}</div>{/if}
{/if}
