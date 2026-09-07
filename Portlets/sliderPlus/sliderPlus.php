<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\sliderPlus;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Hero-Slider für die Startseite (Bootstrap-4-Carousel aus NOVA, kein zusätzliches JS).
 *
 * Features: festes Seitenverhältnis (Desktop/Mobil getrennt, object-fit: cover), Bildausschnitt pro Slide,
 * Overlays, Caption-Position/-Stil, Button-Stile, Slide/Fade/Ken-Burns, Touch-Swipe, Autoplay-Steuerung.
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
    ];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-images');
    }

    public function initInstance(PortletInstance $instance): void
    {
        // Version 1.x: "name" war die HTML-ID, "color" die Hover-Farbe
        $this->migrateLegacyProps($instance);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getSlides(PortletInstance $instance): array
    {
        return \array_values(\array_filter(
            $this->getItems($instance, 'slides', self::SLIDE_DEFAULTS),
            static fn(array $slide): bool => $slide['url'] !== ''
        ));
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
                ],
                true,
                \__('Slide'),
                true,
                \__('Empfohlene Bildgröße: mindestens 1920 px breit. Reihenfolge per Drag & Drop.')
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
