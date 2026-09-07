{* Startseite Plus – Aktions-Banner *}
{$layout      = $portlet->getKey($instance, 'layout', 'overlay')}
{$isSplit     = $layout !== 'overlay'}
{$aspect      = $portlet->getKey($instance, 'aspect', '21-9')}
{$aspectM     = $portlet->getKey($instance, 'aspect-mobile', '4-3')}
{$src         = $portlet->getString($instance, 'src')}
{$srcM        = $portlet->getString($instance, 'src-mobile')}
{$alt         = $portlet->getString($instance, 'alt')}
{$hasImage    = $src !== ''}
{$hasImageM   = $srcM !== ''}
{$focus       = $portlet->getKey($instance, 'focus', 'center')}
{$overlay     = $portlet->getKey($instance, 'overlay', 'gradient-left')}
{$align       = $portlet->getKey($instance, 'text-align', 'left')}
{$textColor   = $portlet->getKey($instance, 'text-color', 'light')}
{$bg          = $portlet->getKey($instance, 'bg', 'none')}
{$rounded     = $portlet->getKey($instance, 'rounded', 'md')}
{$widthMode   = $portlet->getKey($instance, 'width', 'container')}
{$wrap        = !$inContainer && $widthMode === 'container'}
{$fullBleed   = $inContainer && $widthMode === 'full'}
{$kicker      = $portlet->getString($instance, 'kicker')}
{$title       = $portlet->getString($instance, 'title')}
{$titleTag    = $portlet->getKey($instance, 'title-tag', 'h2')}
{$titleSize   = $portlet->getKey($instance, 'title-size', 'md')}
{$text        = $portlet->getString($instance, 'text')}
{$btn1Label   = $portlet->getString($instance, 'btn1-label')}
{$btn1Url     = $portlet->safeUrl($instance->getProperty('btn1-url'))}
{$btn1Style   = $portlet->getKey($instance, 'btn1-style', 'primary')}
{$btn2Label   = $portlet->getString($instance, 'btn2-label')}
{$btn2Url     = $portlet->safeUrl($instance->getProperty('btn2-url'))}
{$btn2Style   = $portlet->getKey($instance, 'btn2-style', 'outline-light')}
{$hasCd       = $portlet->getCountdownTimestamp($instance) > 0}
{$cdExpired   = $portlet->isCountdownExpired($instance)}
{$cdMode      = $portlet->getKey($instance, 'countdown-expired', 'hide')}
{$cdStyle     = $portlet->getKey($instance, 'countdown-style', 'boxes')}
{$cdLabel     = $portlet->getString($instance, 'countdown-label')}
{$cdText      = $portlet->getString($instance, 'countdown-expired-text')}
{$showCd      = $hasCd && (!$cdExpired || $cdMode === 'text')}
{$hideBanner  = $hasCd && $cdExpired && $cdMode === 'hide' && !$isPreview}
{$hasContent  = $kicker !== '' || $title !== '' || $text !== '' || $btn1Label !== '' || $btn2Label !== '' || $showCd}
{$rootStyle   = $portlet->rootStyle($instance, ['accent' => $portlet->getAccent($instance)])}

{if $hideBanner}
    {* Countdown abgelaufen – Banner wird nicht ausgegeben *}
{elseif $isPreview && !$hasImage && !$hasContent}
    <div class="sp-placeholder" style="{$instance->getStyleString()}">
        <i class="fas fa-bullhorn"></i>
        <span>Aktions-Banner – bitte Bild und Text pflegen</span>
    </div>
{else}
    {if $wrap}<div class="container">{/if}
    <section class="sp-promo sp-promo--{$layout}{if $isSplit} sp-promo--split{/if} sp-promo--aspect-{$aspect} sp-promo--m-aspect-{$aspectM} sp-promo--overlay-{$overlay} sp-promo--align-{$align} sp-promo--text-{$textColor} sp-promo--r-{$rounded}{if $isSplit && $bg !== 'none'} sp-bg-{$bg}{/if}{if !$hasImage} sp-promo--no-image{/if}{if $fullBleed} sp-full-bleed{/if} {$portlet->rootClasses($instance)}"
             style="{$rootStyle}"
             {$instance->getAnimationDataAttributeString()}>
        {if $isSplit}<div class="sp-promo__grid">{/if}
        <div class="sp-promo__media{if $hasImageM} sp-promo__media--has-mobile{/if}">
            {if $hasImage}
                {$img = $instance->getImageAttributes($src, $alt, $title)}
                <div class="sp-promo__pic sp-promo__pic--desktop">
                    {image src=$img.src
                           srcset=$img.srcset
                           sizes=$img.srcsizes
                           alt=$img.alt|escape:'html'
                           class="sp-promo__img sp-promo__img--focus-{$focus}"
                           width=$img.realWidth
                           height=$img.realHeight
                           lazy=true
                           webp=true}
                </div>
                {if $hasImageM}
                    {$imgM = $instance->getImageAttributes($srcM, $alt, $title)}
                    <div class="sp-promo__pic sp-promo__pic--mobile">
                        {image src=$imgM.src
                               srcset=$imgM.srcset
                               sizes=$imgM.srcsizes
                               alt=$imgM.alt|escape:'html'
                               class="sp-promo__img sp-promo__img--focus-{$focus}"
                               width=$imgM.realWidth
                               height=$imgM.realHeight
                               lazy=true
                               webp=true}
                    </div>
                {/if}
            {/if}
        </div>
        {if !$isSplit && $overlay !== 'none'}
            <div class="sp-promo__overlay" aria-hidden="true"></div>
        {/if}
        <div class="sp-promo__content">
            <div class="sp-promo__inner">
                {if $kicker !== ''}
                    <span class="sp-kicker sp-promo__kicker">{$kicker|escape:'html'}</span>
                {/if}
                {if $title !== ''}
                    <{$titleTag} class="sp-promo__title sp-promo__title--{$titleSize}">{$title|escape:'html'}</{$titleTag}>
                {/if}
                {if $text !== ''}
                    <div class="sp-promo__text">{$text}</div>
                {/if}
                {if $showCd}
                    <div class="sp-cd sp-cd--{$cdStyle}{if $textColor === 'dark'} sp-cd--dark{/if}"
                         data-sp-until="{$portlet->getCountdownIso($instance)}"
                         data-sp-expired="{$cdMode}">
                        {if !$cdExpired}
                            {if $cdLabel !== ''}
                                <span class="sp-cd__label">{$cdLabel|escape:'html'}</span>
                            {/if}
                            <span class="sp-cd__units">
                                <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="d">00</span><span class="sp-cd__lbl">Tage</span></span>
                                <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="h">00</span><span class="sp-cd__lbl">Std.</span></span>
                                <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="m">00</span><span class="sp-cd__lbl">Min.</span></span>
                                <span class="sp-cd__unit"><span class="sp-cd__num" data-sp-unit="s">00</span><span class="sp-cd__lbl">Sek.</span></span>
                            </span>
                        {/if}
                        <span class="sp-cd__expired"{if !$cdExpired} hidden{/if}>{$cdText|escape:'html'}</span>
                    </div>
                {/if}
                {if $btn1Label !== '' || $btn2Label !== ''}
                    <div class="sp-promo__actions">
                        {if $btn1Label !== ''}
                            <a class="sp-btn sp-btn--{$btn1Style}"{if $btn1Url !== '' && !$isPreview} href="{$btn1Url|escape:'html'}"{/if}>{$btn1Label|escape:'html'}</a>
                        {/if}
                        {if $btn2Label !== ''}
                            <a class="sp-btn sp-btn--{$btn2Style}"{if $btn2Url !== '' && !$isPreview} href="{$btn2Url|escape:'html'}"{/if}>{$btn2Label|escape:'html'}</a>
                        {/if}
                    </div>
                {/if}
            </div>
        </div>
        {if $isSplit}</div>{/if}
    </section>
    {if $wrap}</div>{/if}

    {if $showCd && !$cdExpired}
        {inline_script}<script>
        {literal}
        (function () {
            if (!window.spCountdownInit) {
                window.spCountdownInit = function () {
                    var pad = function (value) {
                        return (value < 10 ? '0' : '') + value;
                    };
                    document.querySelectorAll('.sp-cd[data-sp-until]:not([data-sp-init])').forEach(function (el) {
                        el.setAttribute('data-sp-init', '1');
                        var until = new Date(el.getAttribute('data-sp-until')).getTime();
                        if (isNaN(until)) {
                            return;
                        }
                        var nums  = Object.create(null);
                        var timer = null;
                        el.querySelectorAll('[data-sp-unit]').forEach(function (node) {
                            nums[node.getAttribute('data-sp-unit')] = node;
                        });
                        var tick = function () {
                            var diff = until - Date.now();
                            if (diff <= 0) {
                                clearInterval(timer);
                                var mode  = el.getAttribute('data-sp-expired');
                                var promo = el.closest('.sp-promo');
                                if (mode === 'hide' && promo) {
                                    promo.hidden = true;
                                    return;
                                }
                                var units = el.querySelector('.sp-cd__units');
                                var label = el.querySelector('.sp-cd__label');
                                var text  = el.querySelector('.sp-cd__expired');
                                if (units) { units.hidden = true; }
                                if (label) { label.hidden = true; }
                                if (text && mode === 'text') { text.hidden = false; }
                                return;
                            }
                            var d = Math.floor(diff / 864e5);
                            var h = Math.floor(diff % 864e5 / 36e5);
                            var m = Math.floor(diff % 36e5 / 6e4);
                            var s = Math.floor(diff % 6e4 / 1e3);
                            if (nums.d) { nums.d.textContent = pad(d); }
                            if (nums.h) { nums.h.textContent = pad(h); }
                            if (nums.m) { nums.m.textContent = pad(m); }
                            if (nums.s) { nums.s.textContent = pad(s); }
                        };
                        tick();
                        timer = setInterval(tick, 1000);
                    });
                };
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', window.spCountdownInit);
            } else {
                window.spCountdownInit();
            }
        })();
        {/literal}
        </script>{/inline_script}
    {/if}
{/if}
