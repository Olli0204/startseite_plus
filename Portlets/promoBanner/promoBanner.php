<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\promoBanner;

use JTL\OPC\InputType;
use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Aktions-Banner: Bild mit Kicker, Überschrift, Text, bis zu zwei Buttons und optionalem Countdown
 * (z. B. "Boardsale endet in …"). Layouts: Text auf Bild oder Bild/Text nebeneinander.
 * Der Countdown kommt aus der zentralen Countdown-Verwaltung (Plugin-Tab "Countdowns");
 * ein eigener Endzeitpunkt im Portlet bleibt als Fallback für ältere Banner möglich.
 * Nach Ablauf des Countdowns kann der Banner automatisch ausgeblendet werden.
 */
class promoBanner extends Portlet
{
    use PortletHelper;

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-bullhorn');
    }

    /**
     * @inheritdoc
     */
    public function initInstance(PortletInstance $instance, bool $isFrontend = true): void
    {
        parent::initInstance($instance, $isFrontend);
        // Banner aus 2.0.x: Checkbox "Countdown anzeigen" + eigener Endzeitpunkt -> Auswahl "Eigener Endzeitpunkt"
        if ($this->getString($instance, 'countdown-id') === '' && $this->isTrue($instance, 'use-countdown')) {
            $instance->setProperty('countdown-id', 'custom');
        }
    }

    /**
     * Countdown-View (siehe CountdownService) oder null, wenn kein Countdown aktiv ist.
     * Vorrang hat der in der Verwaltung gewählte Countdown; sonst der eigene Endzeitpunkt des Portlets.
     *
     * @return array<string, mixed>|null
     */
    public function getCountdown(PortletInstance $instance): ?array
    {
        $selection = $this->getString($instance, 'countdown-id');
        if ($selection === '' && $this->isTrue($instance, 'use-countdown')) {
            $selection = 'custom'; // Banner aus 2.0.x: Checkbox "Countdown anzeigen" + eigener Endzeitpunkt
        }
        if ($selection === '' || $selection === 'none') {
            return null;
        }
        if ($selection !== 'custom') {
            $service = CountdownService::create();
            $row     = $service->find((int)$selection);

            return $row !== null ? $service->toView($row) : null;
        }
        $until = $this->getString($instance, 'countdown-until');
        if ($until === '') {
            return null;
        }
        $timestamp = \strtotime($until);
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }

        return CountdownService::buildView(
            0,
            '',
            $timestamp,
            $this->getString($instance, 'countdown-label'),
            $this->getKey($instance, 'countdown-style', 'boxes'),
            $this->getKey($instance, 'countdown-expired', 'hide'),
            $this->getString($instance, 'countdown-expired-text')
        );
    }

    /**
     * Auswahlliste des Countdown-Tabs: kein Countdown, Countdowns aus der Verwaltung, eigener Endzeitpunkt.
     * getPropertyDesc() läuft über getDefaultProps() bei jedem Render, daher pro Request nur eine Abfrage.
     *
     * @return array<string, string>
     */
    private function countdownOptions(): array
    {
        static $options = null;
        if ($options !== null) {
            return $options;
        }
        $options = ['' => \__('Kein Countdown')];
        try {
            $options += CountdownService::create()->options('');
            unset($options['']);
            $options = ['' => \__('Kein Countdown')] + $options;
        } catch (\Throwable) {
            // ohne Datenbank bleibt nur die Grundauswahl
        }
        $options['custom'] = \__('Eigener Endzeitpunkt (Felder unten)');

        return $options;
    }

    /**
     * Absoluter Pfad des gemeinsamen Countdown-Snippets (Portlets/Common/countdown.tpl),
     * ohne "file:"-Präfix – so wie JTL selbst Portlet-Templates per Pfad lädt.
     */
    public function getCountdownTemplate(): string
    {
        return \dirname(\rtrim($this->getBasePath(), '/')) . '/Common/countdown.tpl';
    }

    /**
     * @return string[]
     */
    public function getExtraJsFiles(): array
    {
        return [$this->getCommonUrl() . 'countdown.js?v=' . \rawurlencode($this->getPluginVersion())];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'layout'        => $this->propSelect(
                \__('Layout'),
                [
                    'overlay'     => \__('Text auf dem Bild'),
                    'split-left'  => \__('Bild links, Text rechts'),
                    'split-right' => \__('Bild rechts, Text links'),
                ],
                'overlay',
                34
            ),
            'aspect'        => $this->propAspect(\__('Seitenverhältnis Bild (Desktop)'), '21-9', 33),
            'aspect-mobile' => $this->propAspect(\__('Seitenverhältnis Bild (Mobil)'), '4-3', 33, true),
            'src'           => $this->propImage(\__('Bild'), 50),
            'src-mobile'    => $this->propImage(
                \__('Bild für Smartphones (optional)'),
                50,
                \__('Wird unterhalb von 768 px statt des Hauptbildes angezeigt, z. B. ein hochkantiger Zuschnitt.')
            ),
            'alt'           => $this->propText(\__('Alternativtext (SEO)'), 50),
            'focus'         => $this->propSelect(\__('Bildausschnitt'), $this->focusOptions(), 'center', 25),
            'text-color'    => $this->propSelect(
                \__('Textfarbe'),
                ['light' => \__('Hell (für dunkle Bilder)'), 'dark' => \__('Dunkel (für helle Bilder)')],
                'light',
                25
            ),
            'overlay'       => $this->propSelect(
                \__('Abdunkelung'),
                [
                    'gradient-left'   => \__('Verlauf von links'),
                    'gradient-right'  => \__('Verlauf von rechts'),
                    'gradient-bottom' => \__('Verlauf von unten'),
                    'soft'            => \__('Leicht'),
                    'strong'          => \__('Stark'),
                    'none'            => \__('Keine'),
                ],
                'gradient-left',
                25,
                \__('Nur im Layout „Text auf dem Bild“.')
            ),
            'text-align'    => $this->propSelect(
                \__('Textausrichtung'),
                ['left' => \__('Links'), 'center' => \__('Zentriert'), 'right' => \__('Rechts')],
                'left',
                25
            ),
            'bg'            => $this->propBackground(
                'none',
                25,
                \__('Hintergrund des Textbereichs in den Layouts „Bild links/rechts“.')
            ),
            'rounded'       => $this->propRounded('md', 25),
            'width'         => $this->propWidthMode('container', 25),
            'accent-color'  => $this->propAccentColor(25),
            'kicker'        => $this->propText(\__('Kleine Zeile über der Überschrift'), 34, '', '', 'z. B. Nur für kurze Zeit'),
            'title'         => $this->propText(\__('Überschrift'), 66, '', '', 'z. B. K2 Boardsale – bis zu 54 % sparen'),
            'title-tag'     => $this->propTitleTag('h2', 25),
            'title-size'    => $this->propTitleSize('md', 25),
            'text'          => $this->propRichText(\__('Text')),
            'btn1-label'    => $this->propText(\__('Button 1 – Text'), 34, '', '', 'z. B. Jetzt zuschlagen'),
            'btn1-url'      => $this->propText(\__('Button 1 – Link'), 33),
            'btn1-style'    => $this->propButtonStyle(\__('Button 1 – Stil'), 'primary', 33),
            'btn2-label'    => $this->propText(\__('Button 2 – Text'), 34),
            'btn2-url'      => $this->propText(\__('Button 2 – Link'), 33),
            'btn2-style'    => $this->propButtonStyle(\__('Button 2 – Stil'), 'outline-light', 33),
            'countdown-id'           => $this->propSelect(
                \__('Countdown'),
                $this->countdownOptions(),
                '',
                100,
                \__('Countdowns werden zentral im Plugin-Tab „Countdowns“ gepflegt (Endzeitpunkt, Beschriftung, Verhalten nach Ablauf) und stehen hier zur Auswahl. Die Felder darunter gelten nur für „Eigener Endzeitpunkt“.')
            ),
            'countdown-until'        => [
                'type'  => InputType::DATETIME,
                'label' => \__('Eigener Endzeitpunkt'),
                'width' => 50,
            ],
            'countdown-label'        => $this->propText(\__('Beschriftung (eigener Endzeitpunkt)'), 50, 'Nur noch'),
            'countdown-style'        => $this->propSelect(
                \__('Darstellung (eigener Endzeitpunkt)'),
                ['boxes' => \__('Kästchen'), 'inline' => \__('Textzeile')],
                'boxes',
                50
            ),
            'countdown-expired'      => $this->propSelect(
                \__('Nach Ablauf (eigener Endzeitpunkt)'),
                [
                    'hide' => \__('Banner ausblenden'),
                    'text' => \__('Hinweistext statt Countdown anzeigen'),
                    'keep' => \__('Banner ohne Countdown anzeigen'),
                ],
                'hide',
                50
            ),
            'countdown-expired-text' => $this->propText(
                \__('Hinweistext nach Ablauf (eigener Endzeitpunkt)'),
                100,
                'Die Aktion ist beendet.'
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Buttons')   => ['btn1-label', 'btn1-url', 'btn1-style', 'btn2-label', 'btn2-url', 'btn2-style'],
            \__('Countdown') => ['countdown-id', 'countdown-until', 'countdown-label', 'countdown-style', 'countdown-expired', 'countdown-expired-text'],
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
