<style>
    .button-slider-index {
        background-color: rgba(82, 82, 82, 0.8);
        color: white;
        borderstyle: solid;
        border: 1px;
        padding: 5px 20px 5px 20px;
        font-size: 20px;
        border-radius: 25px;
    }

    .slider-text h2 {
        font-size: 32px;
    }

    @media only screen and (max-width: 1200px) {
    .slider-text h2 {
        font-size: 18px;
    }
    .button-slider-index {
        padding: 2px 10px 2px 10px;
        font-size: 14px;
    }
    .head-banner-main {
        padding-top: -20px !important;
        transform: translateX(-50%);
        width: 200% !important;
    }
    }

    .slider-button-text:hover {
        color: {$instance->getProperty('color')} !important;
    }
</style>
{if $isPreview}
    {$slides = $instance->getProperty('slides')}
    {if $slides|count > 0}
        {$imgAttribs = $instance->getImageAttributes($slides[0].url, $slides[0].alt, $slides[0].title)}
    {/if}
    <div class="text-center" style="color: #5cbcf6; display: flex; flex-direction: column; justify-content: center; height: 64px;">
        <div>
            {file_get_contents($portlet->getBasePath()|cat:'icon.svg')}
            <span style="font-size: 12px; font-weight: bold; text-transform: uppercase;">Slider-Plus</span>
        </div>
    </div>
    
{else}
    {$slides = $instance->getProperty('slides')}
    <div id="{$instance->getProperty('name')}-slider" class="carousel slide" data-ride="carousel" data-interval="8000" data-pause="hover">
        <ol class="carousel-indicators">
            {if $slides|count > 0}
                {foreach $instance->getProperty('slides') as $i => $slide}
                    <li data-target="#{$instance->getProperty('name')}-slider" data-slide-to="{$i}" {if $i == 0}class="active"{/if}></li>
                {/foreach}
            {/if}
        </ol>
        <div class="carousel-inner">
            {if $slides|count > 0}
                {foreach $instance->getProperty('slides') as $i => $slide}
                    <div class="carousel-item{if $i == 0} active{/if}">
                        {$imgAttribs = $instance->getImageAttributes($slide.url, $slide.alt, $slide.title)}
                        {image
                            src=$imgAttribs.src
                            alt=$imgAttribs.alt|escape:'html'
                            title=$slideTitle|escape:'html'
                            class="head-banner-main"
                            style="width: 100%;"
                            data=['desc' => $slide.desc|escape:'html']}
                        <div class="carousel-caption slider-text">
                            <h2 style="color: #fff; font-type: bold;">{$slide.title}</h2>                        
                            <button class="button-slider-index" type="button">
                                <a href="{$slide.link}" class="slider-button-text" style="color: white; text-decoration: none;">{$slide.button}</a>
                            </button>                   
                        </div>
                    </div>
                {/foreach}
            {/if}
        </div>
        <a class="carousel-control-prev" href="#{$instance->getProperty('name')}-slider" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#{$instance->getProperty('name')}-slider" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
        </a>
    </div>
{/if}