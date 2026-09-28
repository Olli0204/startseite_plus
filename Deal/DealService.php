<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Deal;

use JTL\Cart\CartHelper;
use JTL\Catalog\Product\Artikel;
use JTL\Catalog\Product\Preise;
use JTL\Checkout\Kupon;
use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Session\Frontend;
use JTL\Shop;
use Plugin\startseite_plus\Countdown\CountdownService;

/**
 * Deal-Banner: liest einen JTL-Kupon (Rabatt, Gültigkeit, Artikel), lädt die beworbenen Artikel mit
 * den Preisen der aktuellen Kundengruppe und legt per IO-Aufruf alle Artikel in den Warenkorb und
 * löst den Kupon dort über die Core-Logik (Kupon::check()/accept()) ein.
 */
class DealService
{
    public const IO_FUNCTION           = 'startseitePlusDeal';
    public const ADMIN_SEARCH_FUNCTION = 'startseitePlusProductSearch';
    public const ADMIN_COUPON_FUNCTION = 'startseitePlusCouponSearch';
    public const MAX_PRODUCTS          = 4;

    public function __construct(private readonly DbInterface $db)
    {
    }

    public static function create(): self
    {
        return new self(Shop::Container()->getDB());
    }

    /* ------------------------------------------------------------ Kupon */

    public function findCoupon(string $code): ?Kupon
    {
        $code = \trim($code);
        if ($code === '') {
            return null;
        }
        try {
            $coupon = (new Kupon(0, $this->db))->getByCode($code);
        } catch (\Throwable $e) {
            $this->logError($e);

            return null;
        }

        return $coupon instanceof Kupon && (int)$coupon->kKupon > 0 ? $coupon : null;
    }

    /**
     * Gründe, aus denen der Banner im Shop nicht angezeigt wird (leer = anzeigen).
     * Warenkorb-abhängige Bedingungen (Mindestbestellwert, Artikel im Warenkorb) prüft erst der Core beim Einlösen.
     *
     * @return string[]
     */
    public function displayProblems(?Kupon $coupon, string $code): array
    {
        if ($coupon === null) {
            return [\sprintf('Kein Kupon mit dem Code „%s“ gefunden.', $code)];
        }
        $problems = [];
        if ($coupon->cAktiv !== 'Y') {
            $problems[] = 'Der Kupon ist nicht aktiv.';
        }
        $until = self::timestamp($coupon->dGueltigBis);
        if ($until !== null && $until < \time()) {
            $problems[] = 'Der Kupon ist abgelaufen.';
        }
        $from = self::timestamp($coupon->dGueltigAb);
        if ($from !== null && $from > \time()) {
            $problems[] = 'Der Kupon ist noch nicht gültig (ab ' . \date('d.m.Y H:i', $from) . ').';
        }
        if ((int)$coupon->nVerwendungen > 0 && (int)$coupon->nVerwendungen <= (int)$coupon->nVerwendungenBisher) {
            $problems[] = 'Der Kupon ist aufgebraucht (maximale Verwendungen erreicht).';
        }
        if ($coupon->cKuponTyp !== Kupon::TYPE_STANDARD) {
            $problems[] = 'Nur Standardkupons werden unterstützt (kein Versand- oder Neukundenkupon).';
        }
        if (!\in_array($coupon->cWertTyp, ['festpreis', 'prozent'], true)) {
            $problems[] = 'Unbekannter Rabatttyp des Kupons.';
        }
        $groupID = (int)$coupon->kKundengruppe;
        if ($groupID > 0 && $groupID !== Frontend::getCustomerGroup()->getID()) {
            $problems[] = 'Der Kupon gilt nicht für die aktuelle Kundengruppe.';
        }
        if ((string)$coupon->cKunden !== '' && (int)$coupon->cKunden !== -1) {
            $problems[] = 'Der Kupon ist auf einzelne Kunden beschränkt.';
        }

        return $problems;
    }

    /* ---------------------------------------------------------- Artikel */

    /**
     * Artikelnummern aus dem Portlet (komma-, semikolon- oder zeilengetrennt) oder – wenn leer – aus dem Kupon.
     *
     * @return string[]
     */
    public static function parseNumbers(string $numbers, ?Kupon $coupon): array
    {
        if (\trim($numbers) === '' && $coupon !== null) {
            $numbers = (string)$coupon->cArtikel;
        }
        $parts = \preg_split('/[,;\r\n]+/', $numbers) ?: [];
        $parts = \array_values(\array_unique(\array_filter(\array_map('trim', $parts), static fn($p) => $p !== '')));

        return \array_slice($parts, 0, self::MAX_PRODUCTS);
    }

    /**
     * Artikel-IDs aus der Picker-Property ("101;102", auch Komma-getrennt).
     *
     * @return int[]
     */
    public static function parseIds(string $ids): array
    {
        $parts = \array_map('intval', \preg_split('/[,;\s]+/', $ids) ?: []);

        return \array_slice(\array_values(\array_unique(\array_filter($parts, static fn(int $id) => $id > 0))), 0, self::MAX_PRODUCTS);
    }

    /**
     * Artikelnummern in Artikel-IDs übersetzen (Reihenfolge bleibt, unbekannte Nummern entfallen).
     *
     * @param string[] $numbers
     * @return int[]
     */
    public function idsForNumbers(array $numbers): array
    {
        $ids = [];
        foreach ($numbers as $number) {
            try {
                $row = $this->db->getSingleObject(
                    'SELECT kArtikel FROM tartikel WHERE cArtNr = :nr LIMIT 1',
                    ['nr' => $number]
                );
                if ($row !== null) {
                    $ids[] = (int)$row->kArtikel;
                }
            } catch (\Throwable $e) {
                $this->logError($e);
            }
        }

        return \array_values(\array_unique($ids));
    }

    /**
     * @param int[] $ids
     * @return Artikel[]
     */
    public function loadProducts(array $ids): array
    {
        $products = [];
        foreach ($ids as $id) {
            try {
                $product = new Artikel();
                $product->fuelleArtikel($id, Artikel::getDefaultOptions());
                if ((int)($product->kArtikel ?? 0) > 0) {
                    $products[] = $product;
                }
            } catch (\Throwable $e) {
                $this->logError($e);
            }
        }

        return $products;
    }

    /**
     * Artikelsuche für den Artikel-Picker im OPC-Editor (Admin-IO): Suchtext (Name, Artikelnummer, GTIN)
     * oder Liste von Artikel-IDs. Findet Vater- und Kinderartikel (Variationskombinationen); passt der Suchtext
     * auf einen Vaterartikel, erscheinen auch alle seine Kinder – jeweils direkt unter dem Vater.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchProducts(mixed $query): array
    {
        $select = 'SELECT a.kArtikel, a.cName, a.cArtNr, a.cBarcode, a.nIstVater, a.kVaterArtikel,
                          a.kEigenschaftKombi, p.cPfad,
                          (SELECT s.cSeo FROM tseo s
                            WHERE s.cKey = \'kArtikel\' AND s.kKey = a.kArtikel
                            ORDER BY s.kSprache LIMIT 1) AS cSeo,
                          v.cName AS vName, v.cArtNr AS vArtNr, v.cBarcode AS vBarcode, vp.cPfad AS vPfad,
                          (SELECT s.cSeo FROM tseo s
                            WHERE s.cKey = \'kArtikel\' AND s.kKey = a.kVaterArtikel
                            ORDER BY s.kSprache LIMIT 1) AS vSeo
                     FROM tartikel a
                     LEFT JOIN tartikelpict p ON p.kArtikel = a.kArtikel AND p.nNr = 1
                     LEFT JOIN tartikel v ON v.kArtikel = a.kVaterArtikel AND a.kVaterArtikel > 0
                     LEFT JOIN tartikelpict vp ON vp.kArtikel = a.kVaterArtikel AND vp.nNr = 1';
        try {
            if (\is_array($query)) {
                $ids = self::parseIds(\implode(';', \array_map('strval', $query)));
                if ($ids === []) {
                    return [];
                }
                $rows = $this->db->getObjects($select . ' WHERE a.kArtikel IN (' . \implode(',', $ids) . ')');
                $byId = [];
                foreach ($rows as $row) {
                    $byId[(int)$row->kArtikel] = $row;
                }
                $rows = \array_values(\array_filter(\array_map(static fn(int $id) => $byId[$id] ?? null, $ids)));
            } else {
                $term = \trim(\is_scalar($query) ? (string)$query : '');
                if (\mb_strlen($term) < 2) {
                    return [];
                }
                $like = '%' . \addcslashes($term, '%_\\') . '%';
                $rows = $this->db->getObjects(
                    $select . ' WHERE a.cName LIKE :q OR a.cArtNr LIKE :q2 OR a.cBarcode LIKE :q3
                           OR a.kVaterArtikel IN (
                               SELECT parent.kArtikel FROM (
                                   SELECT kArtikel FROM tartikel
                                    WHERE nIstVater = 1 AND (cName LIKE :q4 OR cArtNr LIKE :q5)
                               ) AS parent
                           )
                        ORDER BY (a.cArtNr = :exact) DESC,
                                 COALESCE(NULLIF(a.kVaterArtikel, 0), a.kArtikel) DESC,
                                 (a.kVaterArtikel > 0), a.cArtNr
                        LIMIT 60',
                    ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like, 'q5' => $like, 'exact' => $term]
                );
            }
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
        $labels = $this->variationLabels(\array_map(static fn(object $row): int => (int)$row->kEigenschaftKombi, $rows));

        return \array_map(function (object $row) use ($labels): array {
            $isChild = (int)$row->kVaterArtikel > 0;

            return [
                'id'         => (int)$row->kArtikel,
                'name'       => (string)$row->cName,
                'artNr'      => (string)$row->cArtNr,
                'variations' => (int)$row->nIstVater === 1,
                'child'      => $isChild,
                'variant'    => $labels[(int)$row->kEigenschaftKombi] ?? '',
                'thumb'      => $this->thumbUrl($row),
            ];
        }, $rows);
    }

    /**
     * Beschriftung der Variationskombinationen, z. B. "Größe: 158 / Farbe: Schwarz" (Standardsprache).
     *
     * @param int[] $combiIDs
     * @return array<int, string> kEigenschaftKombi => Beschriftung
     */
    public function variationLabels(array $combiIDs): array
    {
        $combiIDs = \array_values(\array_unique(\array_filter($combiIDs, static fn(int $id): bool => $id > 0)));
        if ($combiIDs === []) {
            return [];
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT ekw.kEigenschaftKombi, e.cName AS attribute, ew.cName AS value
                   FROM teigenschaftkombiwert ekw
                   JOIN teigenschaft e ON e.kEigenschaft = ekw.kEigenschaft
                   JOIN teigenschaftwert ew ON ew.kEigenschaftWert = ekw.kEigenschaftWert
                  WHERE ekw.kEigenschaftKombi IN (' . \implode(',', $combiIDs) . ')
                  ORDER BY ekw.kEigenschaftKombi, e.nSort, e.cName'
            );
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
        $labels = [];
        foreach ($rows as $row) {
            $id            = (int)$row->kEigenschaftKombi;
            $part          = \trim((string)$row->attribute . ': ' . (string)$row->value, ': ');
            $labels[$id]   = isset($labels[$id]) ? $labels[$id] . ' / ' . $part : $part;
        }

        return $labels;
    }

    /**
     * SQL-Bedingung für Kupons, die sich für öffentliche Banner eignen: keine Einmal-Codes (z. B. Newsletter-Kupons),
     * keine auf Kunden beschränkten Kupons und keine Massenerstellung. JTL markiert Massenkupons nicht, legt aber
     * alle Codes einer Serie mit demselben Namen an – Namen, die mehrfach vorkommen, gelten daher als Serie.
     * Nutzt den Parameter :seriesType (= Kupon::TYPE_STANDARD). Gilt für Kupon-Picker (Deal-Banner, Deal-Slides).
     */
    private const PUBLIC_COUPON_SQL = ' AND nVerwendungen != 1
        AND (cKunden = \'-1\' OR cKunden = \'\')
        AND cName NOT IN (
            SELECT series.cName FROM (
                SELECT cName FROM tkupon
                 WHERE cKuponTyp = :seriesType AND cCode != \'\' AND cName IS NOT NULL
                 GROUP BY cName HAVING COUNT(*) > 1
            ) AS series
        )';

    /**
     * Kupon-Suche für den Kupon-Picker im OPC-Editor (Admin-IO): Suchtext in Name oder Code, leer = die neuesten
     * Kupons; mit $exact = true genau ein Code (Anzeige des gespeicherten Kupons). Nur Standardkupons mit Code;
     * Suche und Liste ohne Einmal-, Kunden- und Massenkupons (PUBLIC_COUPON_SQL), der gespeicherte Code immer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCoupons(mixed $query, mixed $exact = false): array
    {
        $term   = \trim(\is_scalar($query) ? (string)$query : '');
        $select = 'SELECT kKupon, cName, cCode, fWert, cWertTyp, dGueltigAb, dGueltigBis, cAktiv,
                          nVerwendungen, nVerwendungenBisher, cArtikel
                     FROM tkupon
                    WHERE cKuponTyp = :type AND cCode != \'\'';
        try {
            if ($exact === true || $exact === 'true') {
                if ($term === '') {
                    return [];
                }
                $rows = $this->db->getObjects($select . ' AND cCode = :code LIMIT 1', [
                    'type' => Kupon::TYPE_STANDARD,
                    'code' => $term,
                ]);
            } elseif ($term === '') {
                $rows = $this->db->getObjects(
                    $select . self::PUBLIC_COUPON_SQL . ' ORDER BY kKupon DESC LIMIT 15',
                    ['type' => Kupon::TYPE_STANDARD, 'seriesType' => Kupon::TYPE_STANDARD]
                );
            } else {
                $like = '%' . \addcslashes($term, '%_\\') . '%';
                $rows = $this->db->getObjects(
                    $select . self::PUBLIC_COUPON_SQL . ' AND (cName LIKE :q OR cCode LIKE :q2)
                              ORDER BY (cCode = :exact) DESC, kKupon DESC
                              LIMIT 25',
                    [
                        'type'       => Kupon::TYPE_STANDARD,
                        'seriesType' => Kupon::TYPE_STANDARD,
                        'q'          => $like,
                        'q2'         => $like,
                        'exact'      => $term,
                    ]
                );
            }
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }

        return \array_map(self::couponRow(...), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private static function couponRow(object $row): array
    {
        $value = (float)$row->fWert;
        $until = self::timestamp($row->dGueltigBis);
        $from  = self::timestamp($row->dGueltigAb);
        $state = 'active';
        if ($row->cAktiv !== 'Y') {
            $state = 'inactive';
        } elseif ($until !== null && $until < \time()) {
            $state = 'expired';
        } elseif ($from !== null && $from > \time()) {
            $state = 'upcoming';
        } elseif ((int)$row->nVerwendungen > 0 && (int)$row->nVerwendungen <= (int)$row->nVerwendungenBisher) {
            $state = 'used';
        }
        $articles = \array_filter(\array_map('trim', \explode(';', (string)$row->cArtikel)));

        return [
            'id'       => (int)$row->kKupon,
            'code'     => (string)$row->cCode,
            'name'     => (string)$row->cName,
            'value'    => $row->cWertTyp === 'prozent'
                ? self::formatNumber($value) . ' %'
                : \number_format($value, \fmod($value, 1.0) === 0.0 ? 0 : 2, ',', '.') . ' €',
            'until'    => $until !== null ? \date('d.m.Y', $until) : '',
            'state'    => $state,
            'articles' => \count($articles),
        ];
    }

    /**
     * Vorschaubild (xs) wie in Artikel::holBilder() – Fehler (z. B. abweichende Core-API) ergeben einen Leerstring.
     */
    private function thumbUrl(object $row): string
    {
        $source = $row;
        if (empty($row->cPfad) && !empty($row->vPfad)) {
            // Kinderartikel ohne eigenes Bild: Bild des Vaterartikels (Bild-URL gehört zur ID des Vaters)
            $source = (object)[
                'kArtikel' => (int)$row->kVaterArtikel,
                'cName'    => (string)$row->vName,
                'cArtNr'   => (string)$row->vArtNr,
                'cBarcode' => (string)($row->vBarcode ?? ''),
                'cSeo'     => $row->vSeo ?? null,
                'cPfad'    => (string)$row->vPfad,
            ];
        }
        if (empty($source->cPfad)) {
            return '';
        }
        try {
            $path = \JTL\Media\Image\Product::getThumb(
                \JTL\Media\Image::TYPE_PRODUCT,
                (int)$source->kArtikel,
                $source,
                \JTL\Media\Image::SIZE_XS,
                1,
                (string)$source->cPfad
            );

            return Shop::getImageBaseURL() . $path;
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Artikel, die ohne Auswahl (Variation, Konfigurator) und mit Bestand direkt in den Warenkorb können.
     */
    public static function isDirectlyBuyable(Artikel $product): bool
    {
        if ((int)($product->nIstVater ?? 0) === 1 || !empty($product->bHasKonfig)) {
            return false;
        }
        // Kinderartikel bringen ihre Variationswerte mit (siehe addToCart()); andere Variationen verlangen eine Auswahl
        if ((int)($product->kEigenschaftKombi ?? 0) === 0 && !empty($product->Variationen)) {
            return false;
        }

        return (int)($product->inWarenkorbLegbar ?? 0) >= 1;
    }

    /* ------------------------------------------------------------ Anzeige */

    /**
     * Komplette Anzeige-Daten für das Template.
     *
     * @return array<string, mixed>
     */
    public function buildView(string $code, string $numbers, string $ids = ''): array
    {
        $lang     = CountdownService::currentLanguage();
        $isEn     = $lang === 'eng';
        $coupon   = $this->findCoupon($code);
        $problems = $this->displayProblems($coupon, $code);
        $idList   = self::parseIds($ids);
        if ($idList === []) {
            $idList = $this->idsForNumbers(self::parseNumbers($numbers, $coupon));
        }
        $products = $this->loadProducts($idList);
        $netto    = Frontend::getCustomerGroup()->isMerchant();

        $items    = [];
        $sumNet   = 0.0;
        $sumGross = 0.0;
        $buyable  = $products !== [];
        $variants = $this->variationLabels(\array_map(
            static fn(Artikel $product): int => (int)($product->kEigenschaftKombi ?? 0),
            $products
        ));
        foreach ($products as $product) {
            $net      = (float)($product->Preise->fVKNetto ?? 0);
            $gross    = (float)($product->Preise->fVKBrutto ?? 0);
            $sumNet   += $net;
            $sumGross += $gross;
            $direct   = self::isDirectlyBuyable($product);
            $buyable  = $buyable && $direct;
            $image    = $product->Bilder[0] ?? null;
            $items[]  = [
                'id'      => (int)$product->kArtikel,
                'name'    => (string)$product->cName,
                'variant' => $variants[(int)($product->kEigenschaftKombi ?? 0)] ?? '',
                'parent'  => (int)($product->nIstVater ?? 0) === 1,
                'url'    => (string)($product->cURLFull ?? ''),
                'image'  => (string)($image->cURLNormal ?? $image->cURLKlein ?? ''),
                'price'  => self::price($netto ? $net : $gross),
                'direct' => $direct,
            ];
        }

        $discount      = '';
        $discountGross = 0.0;
        $validUntil    = null;
        if ($coupon !== null) {
            $value = (float)$coupon->fWert;
            if ($coupon->cWertTyp === 'prozent') {
                $discount      = self::formatNumber($value) . ' %';
                $discountGross = $sumGross * $value / 100;
            } else {
                $discount      = self::price($value, \fmod($value, 1.0) === 0.0 ? 0 : 2);
                $discountGross = $value;
            }
            $discountGross = \min($discountGross, $sumGross);
            $validUntil    = self::timestamp($coupon->dGueltigBis);
        }

        $hasPrices = $items !== [] && $sumGross > 0 && $discountGross > 0;
        $sum       = $netto ? $sumNet : $sumGross;
        $reduction = $netto && $sumGross > 0 ? $discountGross * $sumNet / $sumGross : $discountGross;

        return [
            'code'        => $coupon !== null ? (string)$coupon->cCode : \trim($code),
            'found'       => $coupon !== null,
            'show'        => $problems === [],
            'problems'    => $problems,
            'notes'       => \array_values(\array_map(
                static fn(array $item): string => $item['parent']
                    ? '„' . $item['name'] . '“ ist ein Vaterartikel – wähle im Artikel-Picker die gewünschte Variante '
                        . '(z. B. Größe), sonst wird der Warenkorb-Button nicht angezeigt.'
                    : '„' . $item['name'] . '“ ist nicht direkt bestellbar (z. B. ausverkauft oder Konfigurator) – '
                        . 'der Warenkorb-Button wird deshalb nicht angezeigt.',
                \array_filter($items, static fn(array $item): bool => !$item['direct'])
            )),
            'discount'    => $discount,
            'items'       => $items,
            'count'       => \count($items),
            'buyable'     => $buyable,
            'ids'         => \implode(',', \array_column($items, 'id')),
            'hasPrices'   => $hasPrices,
            'sum'         => $hasPrices ? self::price($sum) : '',
            'total'       => $hasPrices ? self::price(\max(0.0, $sum - $reduction)) : '',
            'saving'      => $hasPrices ? self::price($reduction) : '',
            'validUntil'  => $validUntil,
            'validLabel'  => $validUntil !== null
                ? ($isEn ? 'valid until ' . \date('m/d/Y', $validUntil) : 'gültig bis ' . \date('d.m.Y', $validUntil))
                : '',
            'token'       => self::token(),
            'lang'        => $isEn ? 'en' : 'de',
        ];
    }

    /**
     * Anzeige-Daten inkl. fertiger Texte für Deal-Banner und Deal-Slides (Hero-Slider).
     * Optionen: kicker, title, text, btnLabel (leer = automatisch), showButton, showPrices, showValidity.
     *
     * @param array<string, mixed> $opts
     * @return array<string, mixed>
     */
    public function view(string $code, string $numbers = '', string $ids = '', array $opts = []): array
    {
        try {
            $deal = $this->buildView($code, $numbers, $ids);
        } catch (\Throwable $e) {
            $this->logError($e);

            return [
                'found'    => false,
                'show'     => false,
                'problems' => ['Der Kupon konnte nicht geladen werden: ' . $e->getMessage()],
            ];
        }
        $isEn = $deal['lang'] === 'en';

        $deal['kicker'] = (string)($opts['kicker'] ?? '');
        $deal['title']  = (string)($opts['title'] ?? '');
        if ($deal['title'] === '') {
            $deal['title'] = self::autoTitle($deal, $isEn);
        }
        $deal['text']     = (string)($opts['text'] ?? '');
        $deal['btnLabel'] = (string)($opts['btnLabel'] ?? '');
        if ($deal['btnLabel'] === '') {
            $deal['btnLabel'] = match (true) {
                $deal['count'] === 2 => $isEn ? 'Add both to cart' : 'Beide in den Warenkorb',
                $deal['count'] === 1 => $isEn ? 'Add to cart' : 'In den Warenkorb',
                default              => $isEn ? 'Add all to cart' : 'Alle in den Warenkorb',
            };
        }
        $deal['canAdd']      = $deal['buyable'] && ($opts['showButton'] ?? true);
        $deal['showPrices']  = $deal['hasPrices'] && ($opts['showPrices'] ?? true);
        $deal['showValid']   = $deal['validLabel'] !== '' && ($opts['showValidity'] ?? true);
        $deal['codeLabel']   = $isEn ? 'Your code' : 'Dein Code';
        $deal['withCode']    = $isEn ? 'with code' : 'mit Code';
        $deal['insteadOf']   = $isEn ? 'instead of' : 'statt';
        $deal['copyLabel']   = $isEn ? 'Copy' : 'Kopieren';
        $deal['copiedLabel'] = $isEn ? 'Copied' : 'Kopiert';
        $deal['autoHint']    = $deal['canAdd']
            ? ($isEn ? 'The code is applied automatically.' : 'Der Code wird automatisch eingelöst.')
            : ($isEn ? 'Enter the code in your cart.' : 'Code im Warenkorb eingeben.');
        $deal['countdown']   = null;

        return $deal;
    }

    /**
     * @param array<string, mixed> $deal
     */
    private static function autoTitle(array $deal, bool $isEn): string
    {
        $discount = (string)$deal['discount'];
        if ($deal['count'] >= 2) {
            return $isEn
                ? 'Buy together and save an extra ' . $discount
                : 'Zusammen kaufen und ' . $discount . ' extra sparen';
        }
        if ($discount === '') {
            return '';
        }

        return $isEn ? 'Save ' . $discount . ' with your code' : $discount . ' Rabatt mit deinem Code';
    }

    /**
     * CSRF-Token der Session (wie Form::getTokenInput(), nur der Wert).
     */
    private static function token(): string
    {
        if (!isset($_SESSION['jtl_token'])) {
            try {
                Form::getTokenInput();
            } catch (\Throwable) {
                return '';
            }
        }

        return (string)($_SESSION['jtl_token'] ?? '');
    }

    /* --------------------------------------------------------- Warenkorb */

    /**
     * IO-Funktion: Artikel in den Warenkorb legen und den Kupon einlösen.
     *
     * @param mixed $productIDs Artikel-IDs (Array oder kommagetrennt)
     * @return array<string, mixed>
     */
    public function addToCart(mixed $productIDs, mixed $code, mixed $token): array
    {
        $isEn    = CountdownService::currentLanguage() === 'eng';
        $cartUrl = self::cartUrl();
        if (!\is_string($token) || !Form::validateToken($token)) {
            return ['ok' => false, 'redirect' => '', 'message' => $isEn
                ? 'Your session has expired. Please reload the page.'
                : 'Deine Sitzung ist abgelaufen. Bitte lade die Seite neu.'];
        }
        $ids = \is_array($productIDs) ? $productIDs : \explode(',', (string)$productIDs);
        $ids = \array_slice(\array_values(\array_unique(\array_filter(\array_map('intval', $ids)))), 0, self::MAX_PRODUCTS);

        // CartHelper::addToCartCheck() prüft das Token selbst über Form::validateToken() ohne Argument,
        // also aus $_POST['jtl_token'] – beim IO-Aufruf steht es dort nicht, sonst scheitert jeder Artikel
        // mit R_MISSING_TOKEN. Das Token ist oben bereits gegen die Session geprüft.
        $_POST['jtl_token'] = $token;

        $added  = 0;
        $failed = [];
        foreach ($ids as $id) {
            try {
                if (CartHelper::addProductIDToCart($id, 1, $this->variationProperties($id), 1)) {
                    ++$added;
                } else {
                    $failed[] = $id;
                }
            } catch (\Throwable $e) {
                $failed[] = $id;
                $this->logError($e);
            }
        }
        if ($failed !== []) {
            $this->logError(new \RuntimeException(
                'Artikel nicht in den Warenkorb gelegt (kArtikel ' . \implode(', ', $failed) . ')'
            ));
        }
        if ($added === 0) {
            return ['ok' => false, 'redirect' => '', 'message' => $isEn
                ? 'The products could not be added to your cart.'
                : 'Die Artikel konnten nicht in den Warenkorb gelegt werden.'];
        }

        $applied = $this->applyCoupon(\is_string($code) ? $code : '');
        $alerts  = Shop::Container()->getAlertService();
        $options = ['saveInSession' => true];
        if ($applied === true) {
            $alerts->addSuccess($isEn
                ? 'The coupon code has been applied to your cart.'
                : 'Der Rabattcode wurde in deinem Warenkorb eingelöst.', 'spDealCoupon', $options);
        } elseif (\is_string($applied) && $applied !== '') {
            $alerts->addWarning($applied, 'spDealCoupon', $options);
        }

        return ['ok' => true, 'redirect' => $cartUrl, 'message' => ''];
    }

    /**
     * Löst den Kupon mit derselben Prüfung ein wie der Warenkorb (CartController::checkCoupons()).
     * Ein anderer bereits eingelöster Standardkupon wird nicht überschrieben.
     *
     * @return true|string true bei Erfolg, sonst Hinweistext (leer = nichts zu tun)
     */
    public function applyCoupon(string $code): bool|string
    {
        $coupon = $this->findCoupon($code);
        if ($coupon === null || $coupon->cKuponTyp !== Kupon::TYPE_STANDARD) {
            return '';
        }
        $current = $_SESSION['Kupon'] ?? null;
        if (\is_object($current) && (int)($current->kKupon ?? 0) > 0) {
            if ((int)$current->kKupon === (int)$coupon->kKupon) {
                Kupon::reCheck();

                return true;
            }

            return CountdownService::currentLanguage() === 'eng'
                ? 'Another coupon is already applied. Code ' . $coupon->cCode . ' was not applied.'
                : 'Es ist bereits ein anderer Kupon eingelöst. Der Code ' . $coupon->cCode . ' wurde nicht zusätzlich eingelöst.';
        }
        try {
            $errors = $coupon->check();
            if ($errors !== []) {
                $message = Kupon::mapCouponErrorMessage((int)($errors['ungueltig'] ?? 0), false);

                return (string)$message;
            }
            $coupon->accept();
        } catch (\Throwable $e) {
            $this->logError($e);

            return '';
        }

        return true;
    }

    /**
     * Variationswerte eines Kinderartikels für den Warenkorb – wie IOMethods::pushToBasket() über
     * Product::getSelectedPropertiesForVarCombiArticle(); für normale Artikel leer.
     *
     * @return array<mixed>
     */
    private function variationProperties(int $productID): array
    {
        try {
            $row = $this->db->getSingleObject(
                'SELECT kEigenschaftKombi FROM tartikel WHERE kArtikel = :id',
                ['id' => $productID]
            );
            if ($row === null || (int)$row->kEigenschaftKombi <= 0) {
                return [];
            }

            return \JTL\Helpers\Product::getSelectedPropertiesForVarCombiArticle($productID);
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
    }

    public static function cartUrl(): string
    {
        try {
            return Shop::Container()->getLinkService()->getStaticRoute('warenkorb.php');
        } catch (\Throwable) {
            return Shop::getURL() . '/warenkorb.php';
        }
    }

    /* ------------------------------------------------------------ Helfer */

    private static function timestamp(?string $value): ?int
    {
        if ($value === null || $value === '' || \str_starts_with($value, '0000')) {
            return null;
        }
        $ts = \strtotime($value);

        return $ts !== false && $ts > 0 ? $ts : null;
    }

    /**
     * Preis in der Session-Währung mit Symbol („75,00 €“) wie in NOVA; das Template escaped selbst,
     * daher wird die HTML-Entity des Währungssymbols hier in das Zeichen umgewandelt.
     */
    private static function price(float $value, int $decimals = 2): string
    {
        return \html_entity_decode(
            Preise::getLocalizedPriceString($value, null, true, $decimals),
            \ENT_QUOTES | \ENT_HTML5,
            'UTF-8'
        );
    }

    private static function formatNumber(float $value): string
    {
        return \fmod($value, 1.0) === 0.0
            ? (string)(int)$value
            : \rtrim(\rtrim(\number_format($value, 2, ',', ''), '0'), ',');
    }

    private function logError(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->error('startseite_plus Deal-Banner: ' . $e->getMessage());
        } catch (\Throwable) {
            // Logging darf den Seitenaufbau nie verhindern
        }
    }
}
