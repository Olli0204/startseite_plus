<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\pictureBox;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Kategorie-Kacheln: Bild-Grid mit Titel, Untertitel, Hauptlink und bis zu zwei Unterlinks (z. B. Männer / Frauen).
 *
 * Die Feldnamen kat/link3/kat2/link2 stammen aus Version 1.x und bleiben aus Kompatibilitätsgründen erhalten.
 */
class pictureBox extends Portlet
{
    use PortletHelper;

    /** @var array<string, string> */
    public const TILE_DEFAULTS = [
        'url'   => '',
        'title' => '',
        'desc'  => '',
        'alt'   => '',
        'link'  => '',
        'kat'   => '',
        'link3' => '',
        'kat2'  => '',
        'link2' => '',
    ];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-th-large');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getTiles(PortletInstance $instance): array
    {
        return \array_values(\array_filter(
            $this->getItems($instance, 'slides', self::TILE_DEFAULTS),
            static fn(array $tile): bool => $tile['url'] !== ''
        ));
    }

    /**
     * Spaltenzahl je Breakpoint (xs / sm / ab lg).
     *
     * @return array{xs: int, sm: int, lg: int}
     */
    public function getColumns(PortletInstance $instance): array
    {
        $cols   = $this->getNum($instance, 'columns', 2, 1, 4);
        $colsXs = $this->getNum($instance, 'columns-xs', 1, 1, 2);

        return ['xs' => $colsXs, 'sm' => \min(2, $cols), 'lg' => $cols];
    }

    /**
     * Spaltenbreiten (von 12) für srcset/sizes.
     *
     * @return array{xs: int, sm: int, md: int, lg: int}
     */
    public function getImageDivisor(PortletInstance $instance): array
    {
        $cols = $this->getColumns($instance);

        return [
            'xs' => (int)(12 / $cols['xs']),
            'sm' => (int)(12 / $cols['sm']),
            'md' => (int)(12 / $cols['lg']),
            'lg' => (int)(12 / $cols['lg']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'columns'        => $this->propSelect(
                \__('Spalten (Desktop)'),
                ['2' => '2', '3' => '3', '4' => '4'],
                '2',
                25
            ),
            'columns-xs'     => $this->propSelect(
                \__('Spalten (Smartphone)'),
                ['1' => '1', '2' => '2'],
                '1',
                25
            ),
            'aspect'         => $this->propSelect(
                \__('Seitenverhältnis'),
                [
                    '3-2'     => '3:2',
                    '4-3'     => '4:3',
                    '1-1'     => '1:1 (quadratisch)',
                    '16-9'    => '16:9',
                    '4-5'     => '4:5 (hochkant)',
                    'natural' => \__('Originalhöhe des Bildes'),
                ],
                '3-2',
                25
            ),
            'gap'            => $this->propSelect(
                \__('Abstand'),
                ['none' => \__('Keiner'), 'sm' => \__('Klein'), 'md' => \__('Mittel'), 'lg' => \__('Groß')],
                'sm',
                25
            ),
            'hover'          => $this->propSelect(
                \__('Hover-Effekt'),
                ['zoom' => \__('Bild zoomt'), 'lift' => \__('Kachel hebt sich'), 'none' => \__('Keiner')],
                'zoom',
                25
            ),
            'overlay'        => $this->propSelect(
                \__('Abdunkelung'),
                [
                    'gradient-bottom' => \__('Verlauf von unten'),
                    'dark'            => \__('Gleichmäßig'),
                    'light'           => \__('Leicht aufhellen'),
                    'none'            => \__('Keine'),
                ],
                'gradient-bottom',
                25
            ),
            'title-position' => $this->propSelect(
                \__('Textposition'),
                [
                    'center'        => \__('Mitte'),
                    'bottom-left'   => \__('Unten links'),
                    'bottom-center' => \__('Unten mittig'),
                    'top-left'      => \__('Oben links'),
                ],
                'center',
                25
            ),
            'title-size'     => $this->propTitleSize('md', 25),
            'title-style'    => $this->propSelect(
                \__('Titelstil'),
                [
                    'plain'    => \__('Freistehend (mit Schatten)'),
                    'box'      => \__('Heller Kasten'),
                    'dark-box' => \__('Dunkler Kasten'),
                ],
                'plain',
                25
            ),
            'links-style'    => $this->propSelect(
                \__('Stil der Unterlinks'),
                [
                    'pills'   => \__('Helle Pill-Buttons'),
                    'buttons' => \__('Buttons in Akzentfarbe'),
                    'text'    => \__('Text mit Schrägstrich'),
                ],
                'pills',
                25
            ),
            'rounded'        => $this->propRounded('sm', 25),
            'accent-color'   => $this->propAccentColor(25),
            'width'          => $this->propWidthMode('container', 25),
            'slides'         => $this->propRepeater(
                \__('Kacheln'),
                [
                    ['name' => 'title', 'label' => \__('Titel'), 'width' => 50],
                    ['name' => 'desc', 'label' => \__('Untertitel (optional)'), 'width' => 50],
                    [
                        'name'        => 'link',
                        'label'       => \__('Link der Kachel'),
                        'width'       => 60,
                        'placeholder' => \__('Link der Kachel (leer = erster Unterlink)'),
                    ],
                    ['name' => 'alt', 'label' => \__('Alternativtext (SEO)'), 'width' => 40],
                    ['name' => 'kat', 'label' => \__('Unterlink 1 – Text'), 'width' => 30, 'placeholder' => 'z. B. Männer'],
                    ['name' => 'link3', 'label' => \__('Unterlink 1 – URL'), 'width' => 70],
                    ['name' => 'kat2', 'label' => \__('Unterlink 2 – Text'), 'width' => 30, 'placeholder' => 'z. B. Frauen'],
                    ['name' => 'link2', 'label' => \__('Unterlink 2 – URL'), 'width' => 70],
                ],
                true,
                \__('Kachel'),
                true,
                \__('Bilder mit gleichem Seitenverhältnis wirken am ruhigsten (z. B. 900 × 600 px).')
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Kacheln')   => ['slides'],
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
