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
        'type'     => '',
        'deal'     => '',
        'products' => '',
        'deal-bg'  => 'tint',
        'deal-category' => '',
        'deal-button'   => '',
        'deal-link'     => '',
    ];

    /** Sichtbarkeit der Slide-Felder je Slide-Typ (Repeater-Option showIf) */
    private const SHOW_DEAL  = ['field' => 'type', 'values' => 'deal'];
    private const SHOW_IMAGE = ['field' => 'type', 'values' => 'image'];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-images');
    }

    public function initInstance(PortletInstance $instance, bool $isFrontend = true): void
    {
        // Version 1.x: "name" war die HTML-ID, "color" die Hover-Farbe
        $this->migrateLegacyProps($instance);
        // 2.6.x: Slides ohne Typ – mit Kupon als Deal-Slide, sonst als Bild-Slide übernehmen
        $slides = $instance->getProperty('slides');
        if (\is_array($slides)) {
            $changed = false;
            foreach ($slides as $i => $slide) {
                if (\is_array($slide) && ($slide['type'] ?? '') === '') {
                    $slides[$i]['type'] = !empty($slide['deal']) ? 'deal' : 'image';
                    $changed            = true;
                }
            }
            if ($changed) {
                $instance->setProperty('slides', $slides);
            }
        }
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
            $isDeal            = $slide['type'] === 'deal' || ($slide['type'] === '' && $slide['deal'] !== '');
            if ($isDeal) {
                if ($slide['deal'] === '') {
                    if (!$isPreview) {
                        continue;
                    }
                    $view = ['found' => false, 'show' => false, 'problems' => ['Bitte einen Kupon auswählen.']];
                } else {
                    $deals ??= DealService::create();
                    $link    = $deals->categoryUrl((int)$slide['deal-category']);
                    if ($link === '') {
                        $link = self::safeUrl($slide['deal-link']);
                    }
                    $view = $deals->view($slide['deal'], '', $slide['products'], [
                        'kicker'    => $slide['kicker'],
                        'title'     => $slide['title'],
                        'text'      => $slide['desc'],
                        'link'      => $link,
                        'linkLabel' => $slide['deal-button'],
                    ]);
                }
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
     * Seitenverhältnis des ersten Bild-Slides als CSS-Wert ("1920 / 480") – Slides ohne Bild und Slider mit Deal-Slide
     * übernehmen es, damit alle Slides gleich hoch sind. Leer, wenn es kein Bild oder keine Maße gibt.
     *
     * @param array<int, array<string, mixed>> $slides
     */
    public function getHeroRatio(PortletInstance $instance, array $slides): string
    {
        foreach ($slides as $slide) {
            if (($slide['url'] ?? '') === '') {
                continue;
            }
            try {
                $img = $instance->getImageAttributes((string)$slide['url'], '', '');
            } catch (\Throwable) {
                return '';
            }
            $width  = (int)($img['realWidth'] ?? 0);
            $height = (int)($img['realHeight'] ?? 0);

            return $width > 0 && $height > 0 ? $width . ' / ' . $height : '';
        }

        return '';
    }

    /**
     * @param array<int, array<string, mixed>> $slides
     */
    public function hasDealSlide(array $slides): bool
    {
        foreach ($slides as $slide) {
            if (($slide['dealView'] ?? null) !== null) {
                return true;
            }
        }

        return false;
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
                    [
                        'name'    => 'type',
                        'label'   => \__('Slide-Typ'),
                        'type'    => 'select',
                        'width'   => 100,
                        'options' => [
                            'image' => \__('Bild-Slide – Bild mit Text und Button'),
                            'deal'  => \__('Deal-Slide – Kupon mit Artikeln und Warenkorb-Button'),
                        ],
                        'default' => 'image',
                    ],
                    [
                        'name'   => 'deal',
                        'label'  => \__('Kupon'),
                        'type'   => 'coupon',
                        'width'  => 100,
                        'showIf' => self::SHOW_DEAL,
                    ],
                    [
                        'name'      => 'products',
                        'label'     => \__('Artikel (optional)'),
                        'type'      => 'products',
                        'width'     => 100,
                        'max'       => DealService::MAX_PRODUCTS,
                        'emptyText' => \__('Keine Artikel ausgewählt – es werden die im Kupon hinterlegten Artikel angezeigt.'),
                        'showIf'    => self::SHOW_DEAL,
                    ],
                    ['name' => 'title', 'label' => \__('Überschrift'), 'width' => 60],
                    ['name' => 'kicker', 'label' => \__('Kleine Zeile über der Überschrift'), 'width' => 40],
                    ['name' => 'desc', 'label' => \__('Text / Untertitel'), 'type' => 'textarea', 'width' => 100],
                    [
                        'name'   => 'deal-hint',
                        'label'  => '',
                        'type'   => 'hint',
                        'width'  => 100,
                        'help'   => \__('Überschrift, Kicker und Text sind beim Deal-Slide optional – leer werden sie automatisch aus dem Kupon erzeugt. Das Bild ist optional; ohne Bild gilt die Hintergrundfarbe.'),
                        'showIf' => self::SHOW_DEAL,
                    ],
                    [
                        'name'   => 'deal-category',
                        'label'  => \__('Button verlinkt auf Kategorie'),
                        'type'   => 'category',
                        'width'  => 100,
                        'showIf' => self::SHOW_DEAL,
                    ],
                    [
                        'name'        => 'deal-button',
                        'label'       => \__('Button-Text'),
                        'width'       => 40,
                        'placeholder' => \__('Button-Text – leer: „Zur Aktion“'),
                        'showIf'      => self::SHOW_DEAL,
                    ],
                    [
                        'name'        => 'deal-link',
                        'label'       => \__('Oder eigener Link (URL)'),
                        'width'       => 60,
                        'placeholder' => \__('Oder eigener Link (URL), falls keine Kategorie gewählt ist'),
                        'help'        => \__('Ohne Kategorie und Link zeigt die Karte stattdessen „In den Warenkorb“ mit automatischer Code-Einlösung.'),
                        'showIf'      => self::SHOW_DEAL,
                    ],
                    ['name' => 'button', 'label' => \__('Button-Text'), 'width' => 40, 'showIf' => self::SHOW_IMAGE],
                    [
                        'name'        => 'link',
                        'label'       => \__('Link (URL)'),
                        'width'       => 60,
                        'placeholder' => \__('Link – ohne Button-Text ist das ganze Bild verlinkt'),
                        'showIf'      => self::SHOW_IMAGE,
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
                        'name'    => 'deal-bg',
                        'label'   => \__('Hintergrund, wenn kein Bild gewählt ist'),
                        'type'    => 'select',
                        'width'   => 60,
                        'options' => [
                            'tint'   => \__('Akzentfarbe (sehr hell)'),
                            'light'  => \__('Hellgrau'),
                            'dark'   => \__('Dunkel'),
                            'accent' => \__('Akzentfarbe'),
                        ],
                        'default' => 'tint',
                        'showIf'  => self::SHOW_DEAL,
                    ],
                ],
                true,
                \__('Slide'),
                false,
                \__('Empfohlene Bildgröße: mindestens 1920 px breit. Reihenfolge per Drag & Drop. Deal-Slides zeigen eine Karte mit Kupon, Artikeln und Warenkorb-Button.')
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
