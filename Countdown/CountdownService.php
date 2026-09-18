<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Countdown;

use JTL\DB\DbInterface;
use JTL\Shop;
use stdClass;

/**
 * Zentrale Countdown-Verwaltung: liest die Tabelle startseite_plus_countdown und
 * liefert fertige Anzeige-Arrays ("Views") für Templates.
 *
 * View-Array: id, name, timestamp, until (ISO 8601 mit Zeitzone), expired, label, style,
 * mode (hide|text|keep), text (Hinweis nach Ablauf), units (Beschriftungen Tage/Std./Min./Sek.)
 */
class CountdownService
{
    public const TABLE = 'startseite_plus_countdown';

    public const STYLES = ['boxes', 'inline'];

    public const MODES = ['hide', 'text', 'keep'];

    public const PRODUCT_PAGE = ['none', 'sale', 'all'];

    private const UNITS = [
        'ger' => ['d' => 'Tage', 'h' => 'Std.', 'm' => 'Min.', 's' => 'Sek.'],
        'eng' => ['d' => 'Days', 'h' => 'Hrs', 'm' => 'Min', 's' => 'Sec'],
    ];

    public function __construct(private readonly DbInterface $db)
    {
    }

    public static function create(): self
    {
        return new self(Shop::Container()->getDB());
    }

    /**
     * @return stdClass[]
     */
    public function all(): array
    {
        return $this->db->getObjects('SELECT * FROM ' . self::TABLE . ' ORDER BY `until` DESC, id DESC');
    }

    public function find(int $id): ?stdClass
    {
        if ($id <= 0) {
            return null;
        }

        return $this->db->select(self::TABLE, 'id', $id);
    }

    /**
     * Auswahlliste für Portlet-Properties: id => "Name (bis 24.11.2026 23:59)".
     *
     * @return array<string, string>
     */
    public function options(string $emptyLabel): array
    {
        $options = ['' => $emptyLabel];
        foreach ($this->all() as $row) {
            $ts   = \strtotime((string)$row->until) ?: 0;
            $when = $ts > 0 ? \date('d.m.Y H:i', $ts) : '?';
            $flag = (int)$row->active === 1 ? '' : ' [inaktiv]';

            $options[(string)(int)$row->id] = $row->name . ' (bis ' . $when . ')' . $flag;
        }

        return $options;
    }

    /**
     * Countdowns für die Artikeldetailseite. Abgelaufene erscheinen nur mit Hinweistext.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forProductPage(bool $hasSpecialPrice, string $lang = ''): array
    {
        $rows  = $this->db->getObjects(
            'SELECT * FROM ' . self::TABLE . " WHERE active = 1 AND product_page <> 'none' ORDER BY `until` ASC"
        );
        $views = [];
        foreach ($rows as $row) {
            if ($row->product_page === 'sale' && !$hasSpecialPrice) {
                continue;
            }
            $view = $this->toView($row, $lang);
            if ($view === null || ($view['expired'] && $view['mode'] !== 'text')) {
                continue;
            }
            $views[] = $view;
        }

        return $views;
    }

    /**
     * @return array<string, mixed>|null null bei ungültigem Endzeitpunkt oder inaktivem Countdown
     */
    public function toView(stdClass $row, string $lang = ''): ?array
    {
        if ((int)$row->active !== 1) {
            return null;
        }
        $timestamp = \strtotime((string)$row->until);
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }
        $lang = $lang !== '' ? $lang : self::currentLanguage();
        $isEn = $lang === 'eng';

        return self::buildView(
            (int)$row->id,
            (string)$row->name,
            $timestamp,
            $isEn && (string)$row->label_en !== '' ? (string)$row->label_en : (string)$row->label,
            (string)$row->style,
            (string)$row->expired_mode,
            $isEn && (string)$row->expired_text_en !== '' ? (string)$row->expired_text_en : (string)$row->expired_text,
            $lang
        );
    }

    /**
     * View aus freien Werten (z. B. eigener Endzeitpunkt im Portlet).
     *
     * @return array<string, mixed>
     */
    public static function buildView(
        int $id,
        string $name,
        int $timestamp,
        string $label,
        string $style,
        string $mode,
        string $text,
        string $lang = ''
    ): array {
        $lang = $lang !== '' ? $lang : self::currentLanguage();

        return [
            'id'        => $id,
            'name'      => $name,
            'timestamp' => $timestamp,
            'until'     => \date('c', $timestamp),
            'expired'   => $timestamp <= \time(),
            'label'     => \trim($label),
            'style'     => \in_array($style, self::STYLES, true) ? $style : 'boxes',
            'mode'      => \in_array($mode, self::MODES, true) ? $mode : 'hide',
            'text'      => \trim($text),
            'units'     => self::UNITS[$lang] ?? self::UNITS['ger'],
        ];
    }

    public static function currentLanguage(): string
    {
        try {
            $code = Shop::getLanguageCode();
        } catch (\Throwable) {
            $code = '';
        }

        return $code === 'eng' ? 'eng' : 'ger';
    }
}
