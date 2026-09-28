<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\sliderPlus;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Deal\DealService;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Hero-Slider für die Startseite (Bootstrap-4-Carousel aus NOVA, kein zusätzliches JS).
 *
 * Features: festes Seitenverhältnis (Desktop/Mobil getrennt, object-fit: cover), Bildausschnitt pro Slide,
 * Overlays, Caption-Position/-Stil, Button-Stile, Slide/Fade/Ken-Burns, Touch-Swipe, Autoplay-Steuerung.
 *
 * Deal-Slides: Ist bei einem Slide ein Kupon gewählt, zeigt er statt der Beschriftung eine Deal-Karte
 * (Artikel und Rabatt aus dem Kupon, Warenkorb-Button mit automatischer Code-Einlösung, siehe DealService).
 * Deal-Slides dürfen ohne Bild sein (Hintergrundfarbe); ungültige Kupons blenden den Slide im Shop aus.
 */
class sliderPlus extends Portlet
{
    use PortletHelper;

    /** @var array<string, string> */
    public const SLIDE_DEFAULTS = [
        'url'    => '',
        'title'  => '',
        'kicker' => '',
        'desc'   => '',
        'button' => '',
        'link'   => '',
        'alt'    => '',
        'focus'  => 'center',
        'deal'    => '',
        'deal-bg' => 'tint',
    ];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-images');
    }

    public function initInstance(PortletInstance $instance, bool $isFrontend = true): void
    {
        // Version 1.x: "name" war die HTML-ID, "color" die Hover-Farbe
        $this->migrateLegacyProps($instance);
    }

    /**
     * Slides mit Bild oder Kupon. Deal-Slides bekommen die fertige Anzeige unter "dealView";
     * ist der Kupon ungültig, entfällt der Slide im Shop (im OPC-Editor bleibt er mit Hinweis sichtbar).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSlides(PortletInstance $instance, bool $isPreview = false): array
    {
        $slides = [];
        $deals  = null;
        foreach ($this->getItems($instance, 'slides', self::SLIDE_DEFAULTS) as $slide) {
            $slide['dealView'] = null;
            if ($slide['deal'] !== '') {
                $deals ??= DealService::create();
                $view    = $deals->view($slide['deal'], '', '', [
                    'kicker' => $slide['kicker'],
                    'title'  => $slide['title'],
                    'text'   => $slide['desc'],
                ]);
                if (!$view['show'] && !$isPreview) {
                    continue;
                }
                $slide['dealView'] = $view;
                $slide['deal-bg']  = self::cssKey($slide['deal-bg'], 'tint');
            } elseif ($slide['url'] === '') {
                continue;
            }
            $slides[] = $slide;
        }

        return $slides;
    }

    /**
     * @return string[]
     */
    protected function getSharedCssFiles(): array
    {
        return ['deal.css'];
    }

    /**
     * @return string[]
     */
    public function getExtraJsFiles(): array
    {
        return [$this->getCommonUrl() . 'deal.js?v=' . \rawurlencode($this->getPluginVersion())];
    }

    /**
     * Kupon-Auswahl für die Slides; getPropertyDesc() läuft bei jedem Render, daher einmal pro Request.
     *
     * @return array<string, string>
     */
    private function dealOptions(): array
    {
        static $options = null;
        if ($options === null) {
            try {
                $options = DealService::create()->couponOptions();
            } catch (\Throwable) {
                $options = [];
            }
        }

        return ['' => \__('– kein Deal (normaler Bild-Slide) –')] + $options;
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'aspect'           => $this->propAspect(\__('Seitenverhältnis (Desktop)'), 'natural', 25),
            'aspect-mobile'    => $this->propAspect(\__('Seitenverhältnis (Mobil)'), '3-2', 25, true),
            'transition'       => $this->propSelect(
                \__('Übergang'),
                [
                    'slide'    => \__('Schieben'),
                    'fade'     => \__('Überblenden'),
                    'kenburns' => \__('Überblenden + langsamer Zoom (Ken Burns)'),
                ],
                'slide',
                25
            ),
            'interval'         => $this->propNumber(
                \__('Wechselintervall (ms)'),
                6000,
                25,
                \__('Zeit pro Slide in Millisekunden, mindestens 1000.')
            ),
            'autoplay'         => $this->propYesNo(\__('Automatisch wechseln'), true, 25),
            'pause-hover'      => $this->propYesNo(\__('Bei Mouseover pausieren'), true, 25),
            'show-arrows'      => $this->propYesNo(\__('Pfeile anzeigen'), true, 25),
            'show-dots'        => $this->propYesNo(\__('Punkt-Navigation anzeigen'), true, 25),
            'overlay'          => $this->propSelect(
                \__('Abdunkelung'),
                [
                    'none'            => \__('Keine'),
                    'soft'            => \__('Leicht'),
                    'strong'          => \__('Stark'),
                    'gradient-bottom' => \__('Verlauf von unten'),
                    'gradient-left'   => \__('Verlauf von links'),
                ],
                'none',
                25,
                \__('Verbessert die Lesbarkeit von Text auf hellen Bildern.')
            ),
            'caption-position' => $this->propSelect(
                \__('Textposition'),
                [
                    'center'        => \__('Mitte'),
                    'left'          => \__('Links'),
                    'right'         => \__('Rechts'),
                    'bottom-left'   => \__('Unten links'),
                    'bottom-center' => \__('Unten mittig'),
                ],
                'center',
                25
            ),
            'caption-style'    => $this->propSelect(
                \__('Textstil'),
                [
                    'plain' => \__('Freistehend (mit Schatten)'),
                    'box'   => \__('Dunkler Kasten'),
                    'light' => \__('Heller Kasten'),
                ],
                'plain',
                25
            ),
            'title-size'       => $this->propTitleSize('md', 25),
            'title-tag'        => $this->propTitleTag('h2', 25),
            'button-style'     => $this->propButtonStyle(\__('Button-Stil'), 'pill', 25),
            'accent-color'     => $this->propAccentColor(25),
            'width'            => $this->propWidthMode('full', 25),
            'slides'           => $this->propRepeater(
                \__('Slides'),
                [
                    ['name' => 'title', 'label' => \__('Überschrift'), 'width' => 60],
                    ['name' => 'kicker', 'label' => \__('Kleine Zeile über der Überschrift'), 'width' => 40],
                    ['name' => 'desc', 'label' => \__('Text / Untertitel'), 'type' => 'textarea', 'width' => 100],
                    ['name' => 'button', 'label' => \__('Button-Text'), 'width' => 40],
                    [
                        'name'        => 'link',
                        'label'       => \__('Link (URL)'),
                        'width'       => 60,
                        'placeholder' => \__('Link – ohne Button-Text ist das ganze Bild verlinkt'),
                    ],
                    ['name' => 'alt', 'label' => \__('Alternativtext (SEO)'), 'width' => 60],
                    [
                        'name'    => 'focus',
                        'label'   => \__('Bildausschnitt'),
                        'type'    => 'select',
                        'width'   => 40,
                        'options' => $this->focusOptions(),
                        'default' => 'center',
                    ],
                    [
                        'name'    => 'deal',
                        'label'   => \__('Deal (Kupon)'),
                        'type'    => 'select',
                        'width'   => 60,
                        'options' => $this->dealOptions(),
                        'default' => '',
                        'help'    => \__('Mit Kupon wird der Slide zur Deal-Karte: Artikel und Rabatt aus dem Kupon, Button „In den Warenkorb“ mit automatischer Code-Einlösung. Überschrift, Kicker und Text oben ersetzen die automatischen Texte. Bild optional.'),
                    ],
                    [
                        'name'    => 'deal-bg',
                        'label'   => \__('Hintergrund ohne Bild'),
                        'type'    => 'select',
                        'width'   => 40,
                        'options' => [
                            'tint'   => \__('Akzentfarbe (sehr hell)'),
                            'light'  => \__('Hellgrau'),
                            'dark'   => \__('Dunkel'),
                            'accent' => \__('Akzentfarbe'),
                        ],
                        'default' => 'tint',
                    ],
                ],
                true,
                \__('Slide'),
                false,
                \__('Empfohlene Bildgröße: mindestens 1920 px breit. Reihenfolge per Drag & Drop. Slides ohne Bild werden nur mit Deal (Kupon) angezeigt.')
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Slides')    => ['slides'],
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
