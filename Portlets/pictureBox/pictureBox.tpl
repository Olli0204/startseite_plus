<style>
    .big_bilder_box {
        display: block;
        padding-bottom: 20px;
    }

    .bilder_box {
        display: flex;
        flex-direction: row;
        justify-content: center;
        text-align: center;
        margin-bottom: 0px;
        margin-top: 0px;
    }

    .bilder_box div {
        margin-left: 1px;
        margin-right: 1px;
    }

    .bilder_box div img {
        width: 100%;
        height: auto;
        margin-left: 1px;
        margin-right: 1px;
        border-style: solid;
        border-width: 1px;
        border-color: #808080;
    }

    .bilder_box div div {
        position: relative;
        width: 200px;
        margin: -45px auto 0px auto;
        font-size: 20px;
        border-style: solid;
        border-width: 1px;
        border-color: transparent;
        border-radius: 5px;
        background-color: rgba(255, 255, 255, 0.75);
    }

    .bilder_box div a {
        text-decoration: none;
    }

    .heading-bilder {
        position: relative;
        margin: -215px auto 150px auto;
        font-size: 40px;
        background-color: rgba(255, 255, 255, 0.6);
        border-style: solid;
        border-width: 1px;
        border-color: transparent;
        border-radius: 9px;
        width: 300px;
    }

    @media only screen and (max-width: 1200px) {
        .bilder_box div img {
            margin-left: 0px;
            margin-right: 0px;
            width: 99%;
        }
        .bilder_box {
            flex-wrap: wrap;
        }
        .bilder_box div {
            margin-left: 0px;
            margin-right: 0px;
        }
    }
</style>
{$slides = $instance->getProperty('slides')}
{if $isPreview}
    <div class="text-center" style="color: #5cbcf6; display: flex; flex-direction: column; justify-content: center; height: 64px;">
        <div>
            <i class="far fa-object-group"></i>
            <span style="font-size: 12px; font-weight: bold; text-transform: uppercase;">Bilder Box</span>
        </div>
    </div>
{else}
<div class="big_bilder_box">
    {foreach $slides as $i => $slide}
        {if $i % 2 == 0}
            <div class="bilder_box" {if $isMobile} style="flex-wrap: wrap;" {/if}>
        {/if}
                <div>
                    {$imgAttribs = $instance->getImageAttributes($slide.url, $slide.alt, $slide.title)}
                    {image
                        src=$imgAttribs.src
                        alt=$imgAttribs.alt|escape:'html'
                        title=$slide.title|escape:'html'
                        data=['desc' => $slide.desc|escape:'html']}
                    <div style="margin-bottom: 18px;">
                        <a href="{$slide.link3}">{$slide.kat}</a><span> / </span><a href="{$slide.link2}">{$slide.kat2}</a>
                    </div>
                        <p class="heading-bilder">{$slide.title}</p>
                </div>
        {if $i % 2 != 0 || $i == $slides|count - 1}
            </div>
        {/if}
    {/foreach}
</div>
{/if}
