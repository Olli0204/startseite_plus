<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\Common;

use JTL\OPC\InputType;
use JTL\OPC\PortletInstance;

/**
 * Gemeinsame Hilfsfunktionen und Property-Bausteine für alle Startseite-Plus-Portlets.
 *
 * - Assets: lädt Common/common.css sowie das portlet-eigene style.css (auch im OPC-Editor)
 * - Werte lesen: normalisiert und sichert Portlet-Properties ab (Keys, Zahlen, Farben, URLs, Listen)
 * - Property-Bausteine: einheitliche Definitionen für getPropertyDesc()
 * - Migration: übernimmt Einstellungen aus Plugin-Version 1.x
 */
trait PortletHelper
{
    /* ------------------------------------------------------------------ Assets */

    /**
     * @return string[]
     */
    public function getExtraCssFiles(): array
    {
        $version = '?v=' . \rawurlencode($this->getPluginVersion());
        $files   = [$this->getCommonUrl() . 'common.css' . $version];
        if (\file_exists($this->getBasePath() . 'style.css')) {
            $files[] = $this->getBaseUrl() . 'style.css' . $version;
        }

        return $files;
    }

    public function getPluginVersion(): string
    {
        try {
            $plugin = $this->getPlugin();
            if ($plugin !== null) {
                return $plugin->getMeta()->getVersion();
            }
        } catch (\Throwable) {
            // Fallback unten
        }

        return '0';
    }

    /**
     * URL zum Ordner Portlets/Common/
     */
    public function getCommonUrl(): string
    {
        return \dirname(\rtrim($this->getBaseUrl(), '/')) . '/Common/';
    }

    /* ------------------------------------------------------------- Werte lesen */

    /**
     * Liest eine Listen-Property (repeater) und normalisiert jeden Eintrag auf die angegebenen Felder.
     *
     * @param array<string, string> $defaults Feldname => Standardwert
     * @return array<int, array<string, string>>
     */
    public function getItems(PortletInstance $instance, string $prop, array $defaults): array
    {
        $raw = $instance->getProperty($prop);
        if (!\is_array($raw)) {
            return [];
        }
        $items = [];
        foreach ($raw as $item) {
            if (!\is_array($item)) {
                continue;
            }
            $normalized = $defaults;
            foreach ($defaults as $field => $default) {
                $value              = $item[$field] ?? $default;
                $normalized[$field] = \is_scalar($value) ? \trim((string)$value) : $default;
            }
            $items[] = $normalized;
        }

        return $items;
    }

    /**
     * Liefert einen CSS-tauglichen Schlüssel (a-z, 0-9, "-", "_") oder den Standardwert.
     */
    public function getKey(PortletInstance $instance, string $prop, string $default): string
    {
        return self::cssKey($instance->getProperty($prop), $default);
    }

    public static function cssKey(mixed $value, string $default): string
    {
        $value = \is_scalar($value) ? \strtolower(\trim((string)$value)) : '';

        return $value !== '' && \preg_match('/^[a-z0-9][a-z0-9_-]{0,40}$/', $value) === 1 ? $value : $default;
    }

    /**
     * Ganzzahl-Property mit Grenzen. Werte unterhalb von $min fallen auf den Standard zurück.
     */
    public function getNum(PortletInstance $instance, string $prop, int $default, ?int $min = null, ?int $max = null): int
    {
        $value = $instance->getProperty($prop);
        $num   = \is_numeric($value) ? (int)$value : $default;
        if ($min !== null && $num < $min) {
            $num = $default;
        }
        if ($max !== null && $num > $max) {
            $num = $max;
        }

        return $num;
    }

    /**
     * Checkbox (bool) oder Radio ('true'/'false') auswerten.
     */
    public function isTrue(PortletInstance $instance, string $prop): bool
    {
        $value = $instance->getProperty($prop);

        return $value === true || $value === 'true' || $value === '1' || $value === 1;
    }

    public function getAccent(PortletInstance $instance): string
    {
        return self::safeColor($instance->getProperty('accent-color'));
    }

    public function getString(PortletInstance $instance, string $prop): string
    {
        $value = $instance->getProperty($prop);

        return \is_scalar($value) ? \trim((string)$value) : '';
    }

    public static function safeColor(mixed $value): string
    {
        $value = \is_scalar($value) ? \trim((string)$value) : '';
        if ($value === '') {
            return '';
        }
        $pattern = '/^(#[0-9a-f]{3,8}|rgba?\([0-9\s.,%]+\)|hsla?\([0-9\s.,%]+\)|[a-z]{3,20})$/i';

        return \preg_match($pattern, $value) === 1 ? $value : '';
    }

    public static function safeUrl(mixed $value): string
    {
        $value = \is_scalar($value) ? \trim((string)$value) : '';
        if ($value === '' || \preg_match('/^\s*(javascript|data|vbscript):/i', $value) === 1) {
            return '';
        }

        return $value;
    }

    /**
     * Erlaubt nur Zeichen, die in Font-Awesome-Klassen vorkommen.
     */
    public static function safeIconClass(mixed $value): string
    {
        $value = \is_scalar($value) ? \trim((string)$value) : '';

        return \preg_replace('/[^a-z0-9 _-]/i', '', $value) ?? '';
    }

    /**
     * Inline-Style des Root-Elements: OPC-Styles (Tab "Styles") plus eigene CSS-Variablen (--sp-*).
     *
     * @param array<string, scalar|null> $vars
     */
    public function rootStyle(PortletInstance $instance, array $vars = []): string
    {
        $style = $instance->getStyleString();
        foreach ($vars as $name => $value) {
            $value = \is_scalar($value) ? (string)$value : '';
            if ($value === '') {
                continue;
            }
            $style .= '--sp-' . $name . ':' . \htmlspecialchars($value, \ENT_QUOTES) . ';';
        }

        return $style;
    }

    /**
     * Klassen des Root-Elements: OPC-Klassen (hidden-*, custom-class) plus Animation.
     */
    public function rootClasses(PortletInstance $instance): string
    {
        return \trim($instance->getAnimationClass() . ' ' . $instance->getStyleClasses());
    }

    /* -------------------------------------------------------- Migration 1.x */

    /**
     * Plugin 1.x kannte nur "name" (Slider-ID bzw. Überschrift) und "color" (Hover-Farbe).
     * "color" kollidiert seit 2.0 mit der Schriftfarbe aus dem Styles-Tab und wird deshalb
     * in "accent-color" überführt. "name" wird ggf. in die Text-Property übernommen und geleert,
     * damit die Migration nur einmal läuft.
     */
    protected function migrateLegacyProps(PortletInstance $instance, string $textProp = ''): void
    {
        $name = $instance->getProperty('name');
        if (!\is_string($name) || $name === '') {
            return;
        }
        if ($textProp !== '' && $this->getString($instance, $textProp) === '') {
            $instance->setProperty($textProp, $name);
        }
        $color = $instance->getProperty('color');
        if (\is_string($color) && $color !== '' && $this->getString($instance, 'accent-color') === '') {
            $instance->setProperty('accent-color', $color);
            $instance->setProperty('color', '');
        }
        $instance->setProperty('name', '');
    }

    /* ---------------------------------------------------- Property-Bausteine */

    /**
     * @return array<string, mixed>
     */
    protected function propText(
        string $label,
        int $width = 50,
        string $default = '',
        string $desc = '',
        string $placeholder = ''
    ): array {
        $prop = ['type' => InputType::TEXT, 'label' => $label, 'default' => $default, 'width' => $width];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }
        if ($placeholder !== '') {
            $prop['placeholder'] = $placeholder;
        }

        return $prop;
    }

    /**
     * @return array<string, mixed>
     */
    protected function propNumber(string $label, int $default, int $width = 25, string $desc = ''): array
    {
        $prop = ['type' => InputType::NUMBER, 'label' => $label, 'default' => $default, 'width' => $width];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }

        return $prop;
    }

    /**
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    protected function propSelect(string $label, array $options, string $default, int $width = 25, string $desc = ''): array
    {
        $prop = [
            'type'    => InputType::SELECT,
            'label'   => $label,
            'options' => $options,
            'default' => $default,
            'width'   => $width,
        ];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }

        return $prop;
    }

    /**
     * Radio Ja/Nein – gespeichert als 'true'/'false' (siehe isTrue()).
     *
     * @return array<string, mixed>
     */
    protected function propYesNo(string $label, bool $default = true, int $width = 25, string $desc = ''): array
    {
        $prop = [
            'type'    => InputType::RADIO,
            'label'   => $label,
            'options' => ['true' => \__('Ja'), 'false' => \__('Nein')],
            'default' => $default ? 'true' : 'false',
            'inline'  => true,
            'width'   => $width,
        ];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }

        return $prop;
    }

    /**
     * @param array<string, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    protected function propCheckbox(string $label, int $width = 25, string $desc = '', array $children = []): array
    {
        $prop = ['type' => InputType::CHECKBOX, 'label' => $label, 'width' => $width];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }
        if ($children !== []) {
            $prop['children'] = $children;
        }

        return $prop;
    }

    /**
     * @return array<string, mixed>
     */
    protected function propImage(string $label, int $width = 50, string $desc = ''): array
    {
        $prop = ['type' => InputType::IMAGE, 'label' => $label, 'default' => '', 'width' => $width, 'thumb' => true];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }

        return $prop;
    }

    /**
     * @return array<string, mixed>
     */
    protected function propRichText(string $label): array
    {
        return ['type' => InputType::RICHTEXT, 'label' => $label, 'default' => '', 'width' => 100];
    }

    /**
     * @return array<string, mixed>
     */
    protected function propAccentColor(int $width = 25): array
    {
        return [
            'type'    => InputType::COLOR,
            'label'   => \__('Akzentfarbe'),
            'default' => '',
            'width'   => $width,
            'desc'    => \__('Farbe für Buttons, Hover-Effekte und Hervorhebungen. Leer = Primärfarbe des Templates.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function propWidthMode(string $default = 'container', int $width = 25): array
    {
        return $this->propSelect(
            \__('Breite'),
            [
                'container' => \__('Inhaltsbreite (Container)'),
                'full'      => \__('Volle Bildschirmbreite'),
            ],
            $default,
            $width,
            \__('„Volle Bildschirmbreite“ wirkt am besten im Bereich oberhalb des Inhalts (opc_before_main).')
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propTitleTag(string $default = 'h2', int $width = 25): array
    {
        return $this->propSelect(
            \__('HTML-Tag der Überschrift'),
            ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'div' => \__('Kein Heading (div)')],
            $default,
            $width,
            \__('Für SEO: nur eine H1 pro Seite verwenden.')
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propTitleSize(string $default = 'md', int $width = 25): array
    {
        return $this->propSelect(
            \__('Schriftgröße Überschrift'),
            ['sm' => \__('Klein'), 'md' => \__('Mittel'), 'lg' => \__('Groß'), 'xl' => \__('Sehr groß')],
            $default,
            $width
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propRounded(string $default = 'md', int $width = 25): array
    {
        return $this->propSelect(
            \__('Abgerundete Ecken'),
            ['none' => \__('Keine'), 'sm' => \__('Leicht'), 'md' => \__('Mittel'), 'lg' => \__('Stark')],
            $default,
            $width
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propBackground(string $default = 'none', int $width = 25, string $desc = ''): array
    {
        return $this->propSelect(
            \__('Hintergrund'),
            [
                'none'   => \__('Keiner'),
                'light'  => \__('Hellgrau'),
                'tint'   => \__('Akzentfarbe (sehr hell)'),
                'dark'   => \__('Dunkel'),
                'accent' => \__('Akzentfarbe'),
            ],
            $default,
            $width,
            $desc
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propButtonStyle(string $label, string $default = 'primary', int $width = 33): array
    {
        return $this->propSelect(
            $label,
            [
                'primary'       => \__('Akzentfarbe'),
                'light'         => \__('Weiß'),
                'outline-light' => \__('Weiß umrandet'),
                'dark'          => \__('Dunkel'),
                'outline-dark'  => \__('Dunkel umrandet'),
                'pill'          => \__('Grauer Pill-Button'),
                'link'          => \__('Textlink'),
            ],
            $default,
            $width
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function propAspect(string $label, string $default, int $width = 25, bool $mobile = false): array
    {
        $options = $mobile
            ? [
                'same' => \__('Wie Desktop'),
                '2-1'  => '2:1',
                '16-9' => '16:9',
                '3-2'  => '3:2',
                '4-3'  => '4:3',
                '1-1'  => '1:1 (quadratisch)',
            ]
            : [
                'natural' => \__('Originalhöhe des Bildes'),
                '4-1'     => '4:1 (sehr flach)',
                '3-1'     => '3:1',
                '21-9'    => '21:9',
                '2-1'     => '2:1',
                '16-9'    => '16:9',
                '4-3'     => '4:3',
            ];

        return $this->propSelect(
            $label,
            $options,
            $default,
            $width,
            \__('Bei festem Seitenverhältnis wird das Bild passend zugeschnitten (Bildausschnitt pro Bild einstellbar).')
        );
    }

    /**
     * Listen-Editor (portlet_input_types/repeater.tpl).
     *
     * @param array<int, array<string, mixed>> $fields
     * @return array<string, mixed>
     */
    protected function propRepeater(
        string $label,
        array $fields,
        bool $useImage,
        string $entryLabel,
        bool $requireImage = true,
        string $desc = ''
    ): array {
        $prop = [
            'type'         => 'startseite_plus.repeater',
            'label'        => $label,
            'default'      => [],
            'width'        => 100,
            'useImage'     => $useImage,
            'requireImage' => $useImage && $requireImage,
            'entryLabel'   => $entryLabel,
            'fields'       => $fields,
        ];
        if ($desc !== '') {
            $prop['desc'] = $desc;
        }

        return $prop;
    }

    /**
     * Auswahl für den Bildausschnitt (object-position).
     *
     * @return array<string, string>
     */
    protected function focusOptions(): array
    {
        return [
            'center' => \__('Mitte'),
            'top'    => \__('Oben'),
            'bottom' => \__('Unten'),
            'left'   => \__('Links'),
            'right'  => \__('Rechts'),
        ];
    }
}
