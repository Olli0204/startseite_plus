<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\uspBar;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Vorteile-Leiste: Icon (Font Awesome) oder kleines Bild, Titel, Text und optionaler Link je Eintrag.
 * Layouts: Leiste (eine Zeile), Karten, Kompakt. Auf Smartphones wird die Leiste horizontal scrollbar.
 */
class uspBar extends Portlet
{
    use PortletHelper;

    /** @var array<string, string> */
    public const ITEM_DEFAULTS = [
        'url'   => '',
        'icon'  => '',
        'title' => '',
        'text'  => '',
        'link'  => '',
    ];

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-award');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getUspItems(PortletInstance $instance): array
    {
        return \array_values(\array_filter(
            $this->getItems($instance, 'items', self::ITEM_DEFAULTS),
            static fn(array $item): bool => $item['title'] !== '' || $item['text'] !== ''
        ));
    }

    public function getColumnCount(PortletInstance $instance): int
    {
        $columns = $this->getNum($instance, 'columns', 0, 2, 6);
        if ($columns > 0) {
            return $columns;
        }

        return \max(1, \min(6, \count($this->getUspItems($instance))));
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'layout'       => $this->propSelect(
                \__('Layout'),
                [
                    'strip'   => \__('Leiste (Icon neben Text)'),
                    'cards'   => \__('Karten (Icon über Text)'),
                    'compact' => \__('Kompakt (Icon links, zweizeilig)'),
                ],
                'strip',
                25
            ),
            'columns'      => $this->propSelect(
                \__('Spalten (Desktop)'),
                ['auto' => \__('Automatisch'), '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
                'auto',
                25
            ),
            'icon-style'   => $this->propSelect(
                \__('Icon-Stil'),
                ['circle' => \__('Im Kreis'), 'square' => \__('Im Quadrat'), 'plain' => \__('Freistehend')],
                'circle',
                25
            ),
            'icon-size'    => $this->propSelect(
                \__('Icon-Größe'),
                ['sm' => \__('Klein'), 'md' => \__('Mittel'), 'lg' => \__('Groß')],
                'md',
                25
            ),
            'align'        => $this->propSelect(
                \__('Ausrichtung'),
                ['center' => \__('Zentriert'), 'left' => \__('Links')],
                'center',
                25
            ),
            'bg'           => $this->propBackground('none', 25),
            'divider'      => $this->propCheckbox(\__('Trennlinien zwischen Einträgen'), 25),
            'accent-color' => $this->propAccentColor(25),
            'width'        => $this->propWidthMode('container', 25),
            'items'        => $this->propRepeater(
                \__('Einträge'),
                [
                    [
                        'name'        => 'icon',
                        'label'       => \__('Icon (Font-Awesome-Klasse)'),
                        'width'       => 40,
                        'placeholder' => 'z. B. fas fa-truck',
                        'help'        => \__('Beispiele: fas fa-truck, fas fa-lock, fas fa-store, fas fa-undo, fas fa-headset, far fa-credit-card. Alternativ Bild auswählen.'),
                    ],
                    ['name' => 'title', 'label' => \__('Titel'), 'width' => 60, 'placeholder' => 'z. B. Gratis Versand ab 100 €'],
                    ['name' => 'text', 'label' => \__('Text (optional)'), 'width' => 60, 'placeholder' => 'z. B. innerhalb Deutschlands'],
                    ['name' => 'link', 'label' => \__('Link (optional)'), 'width' => 40],
                ],
                true,
                \__('Eintrag'),
                false,
                \__('Icons aus Font Awesome 5 (in NOVA enthalten). Reihenfolge per Drag & Drop.')
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Einträge')  => ['items'],
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
