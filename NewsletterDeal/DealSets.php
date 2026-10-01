<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\Cart\CartHelper;
use JTL\Catalog\Product\Artikel;
use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Product;
use JTL\Shop;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Deal\DealService;

/**
 * Set-Konfigurator der Newsletter-Deals: aus einer Set-Regel (Artikel kostet X €, wenn ein Set-Partner im Warenkorb
 * liegt) wird eine Karte mit beiden Artikeln, Größen-/Variantenauswahl und einem Button, der beides in den Warenkorb
 * legt. Die Preise setzt danach wie gewohnt DealPricing im Warenkorb.
 *
 * Cache-sicher: Die Karten werden ausschließlich per IO (POST) für Sitzungen mit freigeschaltetem Deal ausgeliefert;
 * im HTML stehen nur Platzhalter, deren Vorhandensein nicht von der Sitzung abhängt (LiteSpeed-Seitencache im Live-Shop).
 */
final class DealSets
{
    public const IO_SETS = 'startseitePlusNlDealSets';
    public const IO_ADD  = 'startseitePlusNlDealSetAdd';

    /** @var array<int, true>|null Artikel in Set-Regeln laufender Deals (für die Platzhalter, pro Request) */
    private static ?array $running = null;

    /**
     * @param \Closure(int): ?object|null $productLoader lädt einen Artikel (Standard: Artikel::fuelleArtikel; für Tests)
     */
    public function __construct(
        private readonly DbInterface $db,
        private readonly DealPricing $pricing,
        private readonly ?\Closure $productLoader = null
    ) {
    }

    public static function create(): self
    {
        $db = Shop::Container()->getDB();

        return new self($db, new DealPricing($db));
    }

    /**
     * Artikel (Set-Artikel und Set-Partner) laufender Deals – unabhängig von der Sitzung.
     *
     * @return array<int, true>
     */
    public function runningProductIDs(): array
    {
        if (self::$running !== null) {
            return self::$running;
        }
        $ids = [];
        foreach ($this->pricing->runningRules() as $rule) {
            if ($rule['type'] !== 'set') {
                continue;
            }
            foreach (\array_merge($rule['products'], $rule['partners']) as $id) {
                $ids[$id] = true;
            }
        }

        return self::$running = $ids;
    }

    /**
     * Hat die Deal-Seite Set-Regeln? (Platzhalter auf der Deal-Seite)
     */
    public function dealHasSets(int $dealID): bool
    {
        foreach ($this->pricing->rulesFor([$dealID]) as $rule) {
            if ($rule['type'] === 'set') {
                return true;
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------ IO */

    /**
     * IO: Set-Karten für eine Deal-Seite (['deal' => id]) oder eine Artikelseite (['product' => id, 'parent' => id]).
     * Nur für Sitzungen mit freigeschaltetem, laufendem Deal; sonst leer.
     *
     * @return array<string, mixed>
     */
    public function ioSets(mixed $context): array
    {
        $context = \is_array($context) ? $context : [];
        $dealID  = (int)($context['deal'] ?? 0);
        $product = (int)($context['product'] ?? 0);
        $parent  = (int)($context['parent'] ?? 0);
        $sets    = [];
        foreach ($this->pricing->activeRules() as $rule) {
            if ($rule['type'] !== 'set') {
                continue;
            }
            if ($dealID > 0 && $rule['dealID'] !== $dealID) {
                continue;
            }
            if ($dealID <= 0 && !DealPricing::matches(\array_merge($rule['products'], $rule['partners']), $product, $parent)) {
                continue;
            }
            $set = $this->buildSet($rule);
            if ($set !== null) {
                $sets[] = $set;
            }
        }

        return ['sets' => $sets, 'labels' => self::labels()];
    }

    /**
     * IO: Set in den Warenkorb legen (Partner-Variante + Set-Artikel-Variante, je 1 Stück).
     *
     * @return array{ok: bool, redirect: string, message: string}
     */
    public function ioAdd(mixed $ruleID, mixed $partnerID, mixed $targetID, mixed $token): array
    {
        $labels = self::labels();
        $fail   = static fn(string $message): array => ['ok' => false, 'redirect' => '', 'message' => $message];
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !\is_string($token) || !Form::validateToken($token)) {
            return $fail($labels['expired']);
        }
        $rule = null;
        foreach ($this->pricing->activeRules() as $candidate) {
            if ($candidate['type'] === 'set' && $candidate['id'] === (int)$ruleID) {
                $rule = $candidate;
                break;
            }
        }
        $partnerID = (int)$partnerID;
        $targetID  = (int)$targetID;
        if ($rule === null
            || !DealPricing::matches($rule['partners'], $partnerID, $this->parentOf($partnerID))
            || !DealPricing::matches($rule['products'], $targetID, $this->parentOf($targetID))
            || $this->isParent($partnerID) || $this->isParent($targetID)
        ) {
            return $fail($labels['invalid']);
        }
        // CartHelper::addToCartCheck() prüft das Token aus $_POST (oben gegen die Sitzung geprüft)
        $_POST['jtl_token'] = $token;
        $added              = 0;
        foreach ([$partnerID, $targetID] as $id) {
            try {
                $properties = (int)($this->row($id)->kEigenschaftKombi ?? 0) > 0
                    ? Product::getSelectedPropertiesForVarCombiArticle($id)
                    : [];
                if (CartHelper::addProductIDToCart($id, 1, $properties, 1)) {
                    ++$added;
                }
            } catch (\Throwable $e) {
                $this->logError($e);
            }
        }
        if ($added < 2) {
            return $fail($added === 0 ? $labels['failed'] : $labels['partial']);
        }

        return ['ok' => true, 'redirect' => DealService::cartUrl(), 'message' => ''];
    }

    /* --------------------------------------------------------- Kartendaten */

    /**
     * @param array<string, mixed> $rule
     * @return array<string, mixed>|null
     */
    private function buildSet(array $rule): ?array
    {
        $singles  = $this->singlePrices();
        $partners = \array_values(\array_filter(\array_map(
            fn(int $id): ?array => $this->option($id, $singles[$id] ?? null),
            $rule['partners']
        )));
        $targets  = \array_values(\array_filter(\array_map(
            fn(int $id): ?array => $this->option($id, (float)$rule['price']),
            $rule['products']
        )));
        if ($partners === [] || $targets === []) {
            return null;
        }
        foreach ($targets as &$target) {
            // "statt": Festpreis des Artikels (falls vorhanden), sonst Shop-Preis
            $single = $singles[$target['id']] ?? null;
            $target['instead'] = $single !== null && $single < $target['shopValue']
                ? DealPricing::formatGross($single)
                : $target['shop'];
        }
        unset($target);
        $totals = [];
        foreach ($partners as $partner) {
            foreach ($targets as $target) {
                $totals[$partner['id'] . '-' . $target['id']] = DealPricing::formatGross($partner['value'] + $target['value']);
            }
        }

        return [
            'id'       => $rule['id'],
            'title'    => \implode(' / ', \array_column($partners, 'name')) . ' + ' . \implode(' / ', \array_column($targets, 'name')),
            'partners' => $partners,
            'targets'  => $targets,
            'totals'   => $totals,
        ];
    }

    /**
     * Ein Artikel der Karte: Name, Bild, Link, Preis (Deal-Preis oder Shop-Preis) und bestellbare Varianten.
     *
     * @return array<string, mixed>|null
     */
    private function option(int $id, ?float $dealPrice): ?array
    {
        try {
            if ($this->productLoader !== null) {
                $product = ($this->productLoader)($id);
            } else {
                $product = new Artikel();
                $product->fuelleArtikel($id, Artikel::getDefaultOptions());
            }
        } catch (\Throwable $e) {
            $this->logError($e);

            return null;
        }
        if ($product === null || (int)($product->kArtikel ?? 0) <= 0) {
            return null;
        }
        $shop  = (float)($product->Preise->fVKBrutto ?? 0);
        $value = $dealPrice !== null && ($shop <= 0 || $dealPrice < $shop) ? $dealPrice : $shop;
        $image = $product->Bilder[0] ?? null;
        $isParent = (int)($product->nIstVater ?? 0) === 1;

        return [
            'id'        => $id,
            'name'      => self::plain($product->cName),
            'url'       => (string)($product->cURLFull ?? ''),
            'image'     => (string)($image->cURLNormal ?? $image->cURLKlein ?? ''),
            'value'     => \round($value, 2),
            'price'     => DealPricing::formatGross($value),
            'shopValue' => $shop,
            'shop'      => $shop > 0 ? DealPricing::formatGross($shop) : '',
            'reduced'   => $value < $shop - 0.005,
            'variants'  => $isParent ? $this->variants($id) : [],
            'single'    => !$isParent,
        ];
    }

    /**
     * Kinderartikel eines Vaters mit Variantenbeschriftung (nur Werte, z. B. "159" oder "M / Wide") und Lagerstatus.
     *
     * @return array<int, array{id: int, label: string, available: bool}>
     */
    private function variants(int $parentID): array
    {
        try {
            $children = $this->db->getObjects(
                'SELECT kArtikel, kEigenschaftKombi, fLagerbestand, cLagerBeachten, cLagerKleinerNull
                   FROM tartikel WHERE kVaterArtikel = :id',
                ['id' => $parentID]
            );
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
        $combiIDs = \array_values(\array_filter(\array_map(static fn(object $c): int => (int)$c->kEigenschaftKombi, $children)));
        $labels   = $this->variantLabels($combiIDs);
        $list     = [];
        foreach ($children as $child) {
            $combi  = (int)$child->kEigenschaftKombi;
            $list[] = [
                'id'        => (int)$child->kArtikel,
                'label'     => $labels[$combi]['label'] ?? ('#' . (int)$child->kArtikel),
                'sort'      => $labels[$combi]['sort'] ?? '',
                'available' => $child->cLagerBeachten !== 'Y' || $child->cLagerKleinerNull === 'Y'
                    || (float)$child->fLagerbestand > 0,
            ];
        }
        \usort($list, static fn(array $a, array $b): int => \strnatcmp($a['sort'], $b['sort']));

        return \array_map(static fn(array $v): array => [
            'id'        => $v['id'],
            'label'     => $v['label'],
            'available' => $v['available'],
        ], $list);
    }

    /**
     * Variantenwerte je Kombination in der aktuellen Sprache (Fallback: Standardname), sortiert wie in der Wawi.
     *
     * @param int[] $combiIDs
     * @return array<int, array{label: string, sort: string}>
     */
    private function variantLabels(array $combiIDs): array
    {
        if ($combiIDs === []) {
            return [];
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT ekw.kEigenschaftKombi, e.nSort AS attrSort, ew.nSort AS valueSort,
                        COALESCE(NULLIF(ews.cName, \'\'), ew.cName) AS value
                   FROM teigenschaftkombiwert ekw
                   JOIN teigenschaft e ON e.kEigenschaft = ekw.kEigenschaft
                   JOIN teigenschaftwert ew ON ew.kEigenschaftWert = ekw.kEigenschaftWert
                   LEFT JOIN teigenschaftwertsprache ews
                          ON ews.kEigenschaftWert = ew.kEigenschaftWert AND ews.kSprache = :lang
                  WHERE ekw.kEigenschaftKombi IN (' . \implode(',', \array_map('intval', $combiIDs)) . ')
                  ORDER BY ekw.kEigenschaftKombi, e.nSort, e.kEigenschaft',
                ['lang' => Shop::getLanguageID()]
            );
        } catch (\Throwable $e) {
            $this->logError($e);

            return [];
        }
        $labels = [];
        foreach ($rows as $row) {
            $id    = (int)$row->kEigenschaftKombi;
            $value = self::plain($row->value);
            $sort  = \sprintf('%05d-%05d', (int)$row->attrSort, (int)$row->valueSort);
            if (isset($labels[$id])) {
                $labels[$id]['label'] .= ' / ' . $value;
                $labels[$id]['sort']  .= '|' . $sort;
            } else {
                $labels[$id] = ['label' => $value, 'sort' => $sort];
            }
        }

        return $labels;
    }

    /**
     * Festpreise der freigeschalteten Deals je Artikel-ID (günstigster gewinnt).
     *
     * @return array<int, float>
     */
    private function singlePrices(): array
    {
        $prices = [];
        foreach ($this->pricing->activeRules() as $rule) {
            if ($rule['type'] !== 'price') {
                continue;
            }
            foreach ($rule['products'] as $id) {
                $prices[$id] = isset($prices[$id]) ? \min($prices[$id], $rule['price']) : $rule['price'];
            }
        }

        return $prices;
    }

    private function row(int $id): ?object
    {
        try {
            return $this->db->getSingleObject(
                'SELECT kArtikel, kVaterArtikel, nIstVater, kEigenschaftKombi FROM tartikel WHERE kArtikel = :id',
                ['id' => $id]
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function parentOf(int $id): int
    {
        return (int)($this->row($id)->kVaterArtikel ?? 0);
    }

    private function isParent(int $id): bool
    {
        $row = $this->row($id);

        return $row === null || (int)$row->nIstVater === 1;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $isEn = CountdownService::currentLanguage() === 'eng';

        return [
            'kicker'   => $isEn ? 'Set deal' : 'Set-Deal',
            'together' => $isEn ? 'together only' : 'zusammen nur',
            'choose'   => $isEn ? 'Choose size' : 'Größe wählen',
            'product'  => $isEn ? 'Choose product' : 'Artikel wählen',
            'soldOut'  => $isEn ? 'sold out' : 'ausverkauft',
            'inSet'    => $isEn ? 'in the set' : 'im Set',
            'instead'  => $isEn ? 'instead of' : 'statt',
            'add'      => $isEn ? 'Add set to cart' : 'Set in den Warenkorb',
            'missing'  => $isEn ? 'Please choose a size for both products.' : 'Bitte wähle für beide Artikel eine Größe.',
            'expired'  => $isEn ? 'Your session has expired. Please reload the page.'
                : 'Deine Sitzung ist abgelaufen. Bitte lade die Seite neu.',
            'invalid'  => $isEn ? 'This set is no longer available.' : 'Dieses Set ist nicht mehr verfügbar.',
            'failed'   => $isEn ? 'The set could not be added to your cart.'
                : 'Das Set konnte nicht in den Warenkorb gelegt werden.',
            'partial'  => $isEn ? 'Only one of the products could be added to your cart.'
                : 'Nur einer der Artikel konnte in den Warenkorb gelegt werden.',
        ];
    }

    private static function plain(mixed $value): string
    {
        return \trim(\html_entity_decode((string)$value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
    }

    public static function reset(): void
    {
        self::$running = null;
    }

    private function logError(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->error('startseite_plus Set-Konfigurator: ' . $e->getMessage());
        } catch (\Throwable) {
        }
    }
}
