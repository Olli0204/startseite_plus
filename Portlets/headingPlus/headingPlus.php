<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\headingPlus;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Überschrift mit Dekor-Varianten (Linien, Akzentstrich, Balken), Kicker, Unterzeile und optionalem Link.
 */
class headingPlus extends Portlet
{
    use PortletHelper;

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-heading');
    }

    public function initInstance(PortletInstance $instance): void
    {
        // Version 1.x: "name" war der Überschriften-Text, "color" die Hover-Farbe
        $this->migrateLegacyProps($instance, 'text');
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'text'         => $this->propText(\__('Text'), 50, '', '', \__('Überschrift')),
            'level'        => $this->propSelect(
                \__('HTML-Tag'),
                ['1' => 'H1', '2' => 'H2', '3' => 'H3', '4' => 'H4', '5' => 'H5', '6' => 'H6'],
                '2',
                17,
                \__('Für SEO: nur eine H1 pro Seite verwenden.')
            ),
            'align'        => $this->propSelect(
                \__('Ausrichtung'),
                ['center' => \__('Zentriert'), 'left' => \__('Links'), 'right' => \__('Rechts')],
                'center',
                33
            ),
            'style'        => $this->propSelect(
                \__('Stil'),
                [
                    'lines'     => \__('Linien links und rechts'),
                    'underline' => \__('Kurzer Akzentstrich darunter'),
                    'bar'       => \__('Akzentbalken links'),
                    'plain'     => \__('Ohne Dekoration'),
                ],
                'lines',
                25
            ),
            'size'         => $this->propSelect(
                \__('Schriftgröße'),
                ['sm' => \__('Klein'), 'md' => \__('Mittel'), 'lg' => \__('Groß'), 'xl' => \__('Sehr groß')],
                'lg',
                25
            ),
            'uppercase'    => $this->propCheckbox(\__('Großbuchstaben'), 25),
            'accent-color' => $this->propAccentColor(25),
            'kicker'       => $this->propText(\__('Kleine Zeile darüber'), 50, '', '', \__('z. B. „Unsere Auswahl“')),
            'subtitle'     => $this->propText(\__('Unterzeile'), 50, '', '', \__('z. B. „Alles für deinen Winter“')),
            'url'          => $this->propText(
                \__('Link (optional)'),
                100,
                '',
                \__('Macht die Überschrift klickbar, z. B. zur passenden Kategorie.'),
                'https://… oder /Kategorie'
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
