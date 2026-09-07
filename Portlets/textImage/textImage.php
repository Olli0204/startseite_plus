<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\textImage;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Text & Bild: zweispaltige Sektion (Bild links oder rechts) mit Kicker, Überschrift, Fließtext,
 * optionalen Kennzahlen ("4000+ Boards auf Lager") und Button – z. B. für "Über uns".
 */
class textImage extends Portlet
{
    use PortletHelper;

    /** @var array<string, string> */
    public const STAT_DEFAULTS = [
        'value' => '',
        'label' => '',
    ];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-columns');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getStats(PortletInstance $instance): array
    {
        return \array_values(\array_filter(
            $this->getItems($instance, 'stats', self::STAT_DEFAULTS),
            static fn(array $stat): bool => $stat['value'] !== ''
        ));
    }

    /**
     * Spaltenbreite des Bildes (von 12) für srcset/sizes.
     *
     * @return array{xs: int, sm: int, md: int, lg: int}
     */
    public function getImageDivisor(PortletInstance $instance): array
    {
        $width = $this->getNum($instance, 'image-width', 50, 40, 60);
        $cols  = (int)\round($width * 12 / 100);

        return ['xs' => 12, 'sm' => 12, 'md' => $cols, 'lg' => $cols];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'src'            => $this->propImage(\__('Bild'), 50),
            'alt'            => $this->propText(\__('Alternativtext (SEO)'), 50),
            'image-position' => $this->propSelect(
                \__('Bildposition'),
                ['left' => \__('Links'), 'right' => \__('Rechts')],
                'left',
                25
            ),
            'image-width'    => $this->propSelect(
                \__('Bildbreite'),
                ['40' => '40 %', '50' => '50 %', '60' => '60 %'],
                '50',
                25
            ),
            'image-style'    => $this->propSelect(
                \__('Bildstil'),
                [
                    'rounded' => \__('Abgerundet'),
                    'shadow'  => \__('Abgerundet mit Schatten'),
                    'frame'   => \__('Versetzter Rahmen in Akzentfarbe'),
                    'circle'  => \__('Kreis'),
                    'normal'  => \__('Ohne'),
                ],
                'rounded',
                25
            ),
            'vertical-align' => $this->propSelect(
                \__('Vertikale Ausrichtung'),
                ['center' => \__('Mittig'), 'top' => \__('Oben')],
                'center',
                25
            ),
            'kicker'         => $this->propText(\__('Kleine Zeile über der Überschrift'), 34, '', '', 'z. B. Seit 1996 in Münster'),
            'title'          => $this->propText(\__('Überschrift'), 66, '', '', 'z. B. Willkommen im Snowshop'),
            'title-tag'      => $this->propTitleTag('h2', 25),
            'title-size'     => $this->propTitleSize('md', 25),
            'bg'             => $this->propBackground('none', 25),
            'padding'        => $this->propSelect(
                \__('Innenabstand'),
                ['sm' => \__('Klein'), 'md' => \__('Mittel'), 'lg' => \__('Groß')],
                'md',
                25
            ),
            'accent-color'   => $this->propAccentColor(25),
            'width'          => $this->propWidthMode('container', 25),
            'text'           => $this->propRichText(\__('Text')),
            'btn-label'      => $this->propText(\__('Button – Text'), 34, '', '', 'z. B. Mehr über uns'),
            'btn-url'        => $this->propText(\__('Button – Link'), 33),
            'btn-style'      => $this->propButtonStyle(\__('Button – Stil'), 'primary', 33),
            'stats'          => $this->propRepeater(
                \__('Kennzahlen'),
                [
                    ['name' => 'value', 'label' => \__('Zahl'), 'width' => 35, 'placeholder' => 'z. B. 4000+'],
                    ['name' => 'label', 'label' => \__('Beschreibung'), 'width' => 65, 'placeholder' => 'z. B. Boards auf Lager'],
                ],
                false,
                \__('Kennzahl'),
                false,
                \__('Werden unter dem Text angezeigt, z. B. „4000+ Boards auf Lager“, „25 Jahre Erfahrung“.')
            ),
            'stats-style'    => $this->propSelect(
                \__('Darstellung der Kennzahlen'),
                ['inline' => \__('Nebeneinander'), 'boxed' => \__('In Kästchen')],
                'inline',
                50
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Kennzahlen') => ['stats', 'stats-style'],
            \__('Styles')     => 'styles',
            \__('Animation')  => 'animations',
        ];
    }
}
