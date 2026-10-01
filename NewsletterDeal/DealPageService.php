<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\DB\DbInterface;
use JTL\Shop;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Deal\DealService;
use stdClass;

/**
 * Newsletter-Deals: versteckte Aktionsseiten (Tabelle startseite_plus_nl_deal).
 * Eine Seite ist nur über ihren geheimen Link erreichbar, steht in keinem Menü, wird nicht indexiert und zeigt die
 * gewählten Artikel als normale Artikelliste samt Kupon-Code (Rabatt über einen JTL-Kupon).
 */
final class DealPageService
{
    public const TABLE        = 'startseite_plus_nl_deal';
    public const MAX_PRODUCTS = 200;
    public const SLUG_PREFIX  = 'newsletter-deals';

    public function __construct(private readonly DbInterface $db)
    {
    }

    public static function create(): self
    {
        return new self(Shop::Container()->getDB());
    }

    /* ------------------------------------------------------------ Laden */

    /**
     * @return stdClass[]
     */
    public function all(): array
    {
        try {
            return $this->db->getObjects('SELECT * FROM ' . self::TABLE . ' ORDER BY id DESC');
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
    }

    public function find(int $id): ?stdClass
    {
        if ($id <= 0) {
            return null;
        }
        try {
            return $this->db->getSingleObject('SELECT * FROM ' . self::TABLE . ' WHERE id = :id', ['id' => $id]);
        } catch (\Throwable $e) {
            $this->logError($e);

            return null;
        }
    }

    /**
     * Routen aller Seiten: je Seite der deutsche und der englische Link. Auch inaktive Seiten bekommen Routen, damit
     * Admins sie vorab ansehen können; Kunden erhalten dort die normale 404-Seite.
     *
     * @return array<int, array{id: int, slug: string, lang: string}>
     */
    public function routes(): array
    {
        $routes = [];
        try {
            foreach ($this->db->getObjects('SELECT id, slug, slug_en FROM ' . self::TABLE) as $row) {
                foreach (['ger' => (string)$row->slug, 'eng' => (string)$row->slug_en] as $lang => $slug) {
                    if (self::isValidSlug($slug)) {
                        $routes[] = ['id' => (int)$row->id, 'slug' => $slug, 'lang' => $lang];
                    }
                }
            }
        } catch (\Throwable) {
            // Tabelle/Spalte fehlt (z. B. während des Plugin-Updates) – keine Routen
        }

        return $routes;
    }

    /* ------------------------------------------------------------- Status */

    /**
     * active | inactive | upcoming | expired
     */
    public static function status(stdClass $row, ?int $now = null): string
    {
        $now ??= \time();
        if ((int)$row->active !== 1) {
            return 'inactive';
        }
        $from = self::timestamp($row->valid_from ?? null);
        if ($from !== null && $from > $now) {
            return 'upcoming';
        }
        $until = self::timestamp($row->valid_until ?? null);
        if ($until !== null && $until <= $now) {
            return 'expired';
        }

        return 'active';
    }

    /**
     * Kunden sehen aktive und abgelaufene Seiten (abgelaufen = Hinweis statt Artikel), Admins zusätzlich
     * inaktive und künftige Seiten als Vorschau.
     */
    public static function isVisible(stdClass $row, bool $isAdmin): bool
    {
        return $isAdmin || \in_array(self::status($row), ['active', 'expired'], true);
    }

    /* --------------------------------------------------------------- Slug */

    public static function isValidSlug(string $slug): bool
    {
        return \mb_strlen($slug) >= 3 && \mb_strlen($slug) <= 120
            && \preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Nicht erratbarer Standard-Slug, z. B. "newsletter-deals-7f3a9c2e".
     */
    public static function randomSlug(): string
    {
        return self::SLUG_PREFIX . '-' . \bin2hex(\random_bytes(4));
    }

    /**
     * Freitext in einen gültigen Slug umwandeln (Kleinbuchstaben, Ziffern, Bindestriche).
     */
    public static function normalizeSlug(string $value): string
    {
        $value = \mb_strtolower(\trim($value));
        $value = \strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
        $value = (string)\preg_replace('/[^a-z0-9]+/', '-', $value);

        return \trim($value, '-');
    }

    /**
     * Grund, warum der Slug nicht nutzbar ist (leer = frei). Prüft andere Deal-Seiten und alle Shop-URLs (tseo),
     * damit weder eine Kategorie noch ein Artikel überdeckt wird.
     */
    public function slugProblem(string $slug, int $exceptID): string
    {
        if (!self::isValidSlug($slug)) {
            return 'Der Link darf nur Kleinbuchstaben, Ziffern und Bindestriche enthalten (3–120 Zeichen).';
        }
        try {
            $own = $this->db->getSingleObject(
                'SELECT id FROM ' . self::TABLE . ' WHERE (slug = :slug OR slug_en = :slug2) AND id != :id',
                ['slug' => $slug, 'slug2' => $slug, 'id' => $exceptID]
            );
            if ($own !== null) {
                return 'Dieser Link wird bereits von einer anderen Deal-Seite verwendet.';
            }
            $seo = $this->db->getSingleObject('SELECT cKey FROM tseo WHERE cSeo = :slug LIMIT 1', ['slug' => $slug]);
            if ($seo !== null) {
                return 'Dieser Link ist bereits eine Shop-URL (' . $seo->cKey . ').';
            }
        } catch (\Throwable $e) {
            $this->logError($e);
        }

        return '';
    }

    /**
     * @param int|null $languageID für Shops mit eigener Domain je Sprache (URL_SHOP_ENG); sonst egal
     */
    public static function url(string $slug, ?int $languageID = null): string
    {
        return \rtrim(Shop::getURL(false, $languageID), '/') . '/' . $slug;
    }

    /**
     * Link aus dem Backend: "fromAdmin=yes" lässt Shop::isAdmin(true) die Admin-Sitzung erkennen, damit
     * inaktive und geplante Seiten als Vorschau erscheinen. Für den Newsletter immer url() verwenden.
     */
    public static function previewUrl(string $slug, ?int $languageID = null): string
    {
        return self::url($slug, $languageID) . '?fromAdmin=yes';
    }

    /**
     * Englischer Standard-Link zum deutschen: "<slug>-en".
     */
    public static function englishSlug(string $slug): string
    {
        return \mb_substr($slug, 0, 117) . '-en';
    }

    /* ------------------------------------------------------------ Artikel */

    /**
     * Artikel-IDs aus der Picker-Auswahl ("101;102").
     *
     * @return int[]
     */
    public static function parseIds(?string $value): array
    {
        return DealService::parseIds((string)$value, self::MAX_PRODUCTS);
    }

    /**
     * IDs für die Artikelliste: Auswahl aus dem Picker, sonst die Artikel des Kupons. Gewählte Varianten
     * (Kinderartikel) bringen ihren Vaterartikel mit, weil die Artikelliste Vaterartikel zeigt.
     *
     * @return int[]
     */
    public function listProductIDs(stdClass $row): array
    {
        $ids = self::parseIds($row->products ?? '');
        if ($ids === []) {
            // ohne eigene Auswahl: alle Artikel der Deal-Preise (inkl. Set-Partner), sonst die Artikel des Kupons
            foreach ((new DealPricing($this->db))->rulesFor([(int)$row->id]) as $rule) {
                $ids = \array_merge($ids, $rule['products'], $rule['partners']);
            }
            $ids = \array_slice(\array_values(\array_unique($ids)), 0, self::MAX_PRODUCTS);
        }
        if ($ids === []) {
            $coupon = DealService::create()->findCoupon((string)$row->coupon);
            if ($coupon !== null) {
                $numbers = \preg_split('/[,;\r\n]+/', (string)$coupon->cArtikel) ?: [];
                $numbers = \array_values(\array_unique(\array_filter(\array_map('trim', $numbers))));
                $ids     = DealService::create()->idsForNumbers(\array_slice($numbers, 0, self::MAX_PRODUCTS));
            }
        }
        if ($ids === []) {
            return [];
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT kArtikel, kVaterArtikel FROM tartikel WHERE kArtikel IN (' . \implode(',', $ids) . ')'
            );
        } catch (\Throwable $e) {
            $this->logError($e);

            return $ids;
        }
        foreach ($rows as $product) {
            if ((int)$product->kVaterArtikel > 0) {
                $ids[] = (int)$product->kVaterArtikel;
            }
        }

        return \array_values(\array_unique($ids));
    }

    /* -------------------------------------------------------- Frontend-View */

    /**
     * Anzeige-Daten für den Kopf der Artikelliste (Titel, Text, Kupon-Karte, Countdown, Status).
     *
     * @return array<string, mixed>
     */
    public function frontendView(stdClass $row, bool $isAdmin): array
    {
        $isEn   = CountdownService::currentLanguage() === 'eng';
        $status = self::status($row);
        $pick   = static fn(string $de, string $en): string => $isEn && \trim($en) !== '' ? \trim($en) : \trim($de);

        $deal = null;
        if (\trim((string)$row->coupon) !== '') {
            // Rabatt, Code, Gültigkeit und Prüfungen wie beim Deal-Banner; die Artikel zeigt die Liste selbst
            $deal = DealService::create()->view((string)$row->coupon, '', '', ['showButton' => false]);
        }
        $couponOk = $deal !== null && !empty($deal['show']);
        $rules    = \count((new DealPricing($this->db))->rulesFor([(int)$row->id]));
        $code     = DealPricing::normalizeCode((string)($row->code ?? ''));

        // Ende für Countdown und "gültig bis": Seite, sonst Kupon
        $until = self::timestamp($row->valid_until ?? null) ?? ($couponOk ? ($deal['validUntil'] ?? null) : null);

        $notes = [];
        if ($isAdmin) {
            $notes = match ($status) {
                'inactive' => ['Vorschau: Die Seite ist deaktiviert – Kunden sehen eine 404-Seite.'],
                'upcoming' => ['Vorschau: Die Seite startet am ' . \date('d.m.Y H:i', (int)self::timestamp($row->valid_from)) . ' – bis dahin sehen Kunden eine 404-Seite.'],
                default    => [],
            };
            if ($deal !== null && !$couponOk) {
                foreach ($deal['problems'] ?? [] as $problem) {
                    $notes[] = 'Kupon wird nicht angezeigt: ' . $problem;
                }
            }
            if (\trim((string)$row->coupon) === '' && $rules === 0) {
                $notes[] = 'Hinweis: Weder Deal-Preise noch Kupon – die Seite zeigt nur die Artikel.';
            }
            if ($rules > 0 && $status !== 'active') {
                $notes[] = 'Vorschau: Die Deal-Preise gelten erst, wenn die Seite aktiv ist und läuft.';
            }
        }

        return [
            'id'          => (int)$row->id,
            'title'       => $pick((string)$row->title, (string)$row->title_en),
            'text'        => $pick((string)($row->text ?? ''), (string)($row->text_en ?? '')),
            'status'      => $status,
            'expired'     => $status === 'expired',
            'expiredText' => $isEn ? 'This deal has ended. Thanks for your interest!'
                : 'Diese Aktion ist leider beendet. Danke für dein Interesse!',
            'homeLabel'   => $isEn ? 'Continue shopping' : 'Weiter shoppen',
            'homeUrl'     => Shop::getURL() . '/',
            'notes'       => $notes,
            'deal'        => $couponOk ? $deal : null,
            'discount'    => $couponOk ? (string)$deal['discount'] : '',
            'kicker'      => $isEn ? 'Exclusive for newsletter subscribers' : 'Exklusiv für Newsletter-Abonnenten',
            'hint'        => $isEn ? 'Enter the code in your cart.' : 'Code im Warenkorb eingeben.',
            'saveLabel'   => $isEn ? 'off with your code' : 'Rabatt mit deinem Code',
            'validLabel'  => $until !== null
                ? ($isEn ? 'valid until ' . \date('m/d/Y H:i', $until) : 'gültig bis ' . \date('d.m.Y, H:i', $until) . ' Uhr')
                : '',
            'prices'      => $rules > 0,
            'pricesTitle' => $isEn ? 'Your newsletter prices are active' : 'Deine Newsletter-Preise sind aktiv',
            'pricesHint'  => $isEn ? 'The deal prices apply automatically in your cart.'
                : 'Die Deal-Preise gelten automatisch im Warenkorb.',
            'code'        => $code,
            'codeHint'    => $isEn ? 'On another device? Enter the code in your cart:'
                : 'Auf einem anderen Gerät? Code im Warenkorb eingeben:',
            'copyLabel'   => $isEn ? 'Copy' : 'Kopieren',
            'copiedLabel' => $isEn ? 'Copied' : 'Kopiert',
            'countdown'   => $until !== null && $status !== 'expired'
                ? CountdownService::buildView(
                    0,
                    '',
                    $until,
                    $isEn ? 'Ends in' : 'Endet in',
                    'inline',
                    'text',
                    $isEn ? 'This deal has ended.' : 'Die Aktion ist beendet.'
                )
                : null,
        ];
    }

    /* ------------------------------------------------------------- Helfer */

    public static function timestamp(mixed $value): ?int
    {
        if ($value === null || $value === '' || \str_starts_with((string)$value, '0000')) {
            return null;
        }
        $ts = \strtotime((string)$value);

        return $ts === false || $ts <= 0 ? null : $ts;
    }

    private function logError(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->error('startseite_plus Newsletter-Deals: ' . $e->getMessage());
        } catch (\Throwable) {
        }
    }
}
