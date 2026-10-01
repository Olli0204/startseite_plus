<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\Catalog\Product\Preise;
use JTL\DB\DbInterface;
use JTL\Helpers\Tax;
use JTL\Session\Frontend;
use JTL\Shop;
use Plugin\startseite_plus\Countdown\CountdownService;
use stdClass;

/**
 * Deal-Preise der Newsletter-Deal-Seiten.
 *
 * Regeln (Tabelle startseite_plus_nl_deal_rule):
 *   price – Festpreis: jeder der Artikel kostet je Stück den Bruttopreis
 *   set   – Set-Preis: liegt einer der Partnerartikel im Warenkorb, kostet der Artikel je Stück den Bruttopreis
 *           (höchstens so viele Stück wie Partnerartikel im Warenkorb liegen)
 * Ein Vaterartikel in einer Regel gilt für alle seine Varianten.
 *
 * Freigeschaltet werden die Preise pro Sitzung: durch den Besuch des geheimen Links oder durch den Deal-Code im
 * Kupon-Feld. Der Warenkorb übernimmt sie in HOOK_SETZTE_POSITIONSPREISE direkt als Positionspreis (der Core ruft
 * Cart::setzePositionsPreise() bei jeder Änderung auf); ein Deal-Preis ersetzt den Shop-Preis nur, wenn er günstiger ist.
 */
final class DealPricing
{
    public const SESSION_KEY = 'startseitePlusNlDeals';
    public const TABLE_RULES = 'startseite_plus_nl_deal_rule';
    public const TYPES       = ['price', 'set'];

    /** @var array<int, array<string, mixed>>|null Regeln der freigeschalteten, laufenden Deals (pro Request) */
    private static ?array $active = null;

    /** @var array<int, string> Positionshinweise je spl_object_id der Warenkorbposition */
    private static array $notes = [];

    public function __construct(private readonly DbInterface $db)
    {
    }

    public static function create(): self
    {
        return new self(Shop::Container()->getDB());
    }

    /* -------------------------------------------------------- Freischalten */

    /**
     * @return int[]
     */
    public static function unlockedIDs(): array
    {
        $ids = $_SESSION[self::SESSION_KEY] ?? [];

        return \is_array($ids) ? \array_map('intval', \array_keys($ids)) : [];
    }

    /**
     * Deal für diese Sitzung freischalten (nur laufende Seiten mit Regeln). Liegen schon Artikel im Warenkorb,
     * werden ihre Preise sofort neu berechnet.
     */
    public function unlock(stdClass $page): bool
    {
        if (DealPageService::status($page) !== 'active' || \in_array((int)$page->id, self::unlockedIDs(), true)) {
            return false;
        }
        if ($this->rulesFor([(int)$page->id]) === []) {
            return false;
        }
        $ids                         = \is_array($_SESSION[self::SESSION_KEY] ?? null) ? $_SESSION[self::SESSION_KEY] : [];
        $ids[(int)$page->id]         = \time();
        $_SESSION[self::SESSION_KEY] = $ids;
        self::$active                = null;
        $this->recalculateCart();

        return true;
    }

    /**
     * Laufende Deal-Seite zum Code (Groß-/Kleinschreibung egal), sonst null.
     */
    public function findByCode(string $code): ?stdClass
    {
        $code = self::normalizeCode($code);
        if ($code === '') {
            return null;
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT * FROM ' . DealPageService::TABLE . ' WHERE UPPER(code) = :code ORDER BY id DESC',
                ['code' => $code]
            );
        } catch (\Throwable) {
            return null;
        }
        foreach ($rows as $row) {
            if (DealPageService::status($row) === 'active') {
                return $row;
            }
        }

        return null;
    }

    public static function normalizeCode(string $code): string
    {
        return \mb_strtoupper((string)\preg_replace('/[^A-Za-z0-9_-]/', '', \trim($code)));
    }

    /**
     * HOOK_ROUTER_PRE_DISPATCH: Deal-Code aus dem Kupon-Feld (Warenkorb oder Checkout) abfangen, bevor der Core ihn als
     * unbekannten Kupon ablehnt.
     */
    public function handleCouponField(): void
    {
        $code = \is_string($_POST['Kuponcode'] ?? null) ? $_POST['Kuponcode'] : '';
        if (\trim($code) === '') {
            return;
        }
        $page = $this->findByCode($code);
        if ($page === null) {
            return;
        }
        unset($_POST['Kuponcode'], $_REQUEST['Kuponcode']);
        $isEn = CountdownService::currentLanguage() === 'eng';
        if (!$this->unlock($page) && !\in_array((int)$page->id, self::unlockedIDs(), true)) {
            return;
        }
        try {
            Shop::Container()->getAlertService()->addSuccess(
                $isEn ? 'Your newsletter prices are now active.' : 'Deine Newsletter-Preise sind jetzt aktiv.',
                'startseitePlusNlDealCode'
            );
        } catch (\Throwable) {
        }
    }

    public function recalculateCart(): void
    {
        try {
            $cart = Frontend::getCart();
            if ($cart->gibAnzahlArtikelExt([\C_WARENKORBPOS_TYP_ARTIKEL]) > 0) {
                $cart->setzePositionsPreise();
            }
        } catch (\Throwable $e) {
            $this->logError($e);
        }
    }

    /* -------------------------------------------------------------- Regeln */

    /**
     * Regeln von Deal-Seiten (Admin und Freischalten).
     *
     * @param int[] $dealIDs
     * @return array<int, array<string, mixed>>
     */
    public function rulesFor(array $dealIDs): array
    {
        $dealIDs = \array_values(\array_filter(\array_map('intval', $dealIDs), static fn(int $id) => $id > 0));
        if ($dealIDs === []) {
            return [];
        }
        try {
            $rows = $this->db->getObjects(
                'SELECT * FROM ' . self::TABLE_RULES . ' WHERE deal_id IN (' . \implode(',', $dealIDs) . ')
                    ORDER BY deal_id, sort, id'
            );
        } catch (\Throwable) {
            return [];
        }

        return \array_values(\array_filter(\array_map(static fn(stdClass $row): array => self::ruleFromRow($row), $rows),
            static fn(array $rule): bool => self::isUsable($rule)));
    }

    /**
     * @return array<string, mixed>
     */
    public static function ruleFromRow(stdClass $row): array
    {
        return [
            'id'       => (int)($row->id ?? 0),
            'dealID'   => (int)($row->deal_id ?? 0),
            'type'     => \in_array($row->type ?? '', self::TYPES, true) ? (string)$row->type : 'price',
            'products' => DealPageService::parseIds((string)($row->products ?? '')),
            'partners' => DealPageService::parseIds((string)($row->partners ?? '')),
            'price'    => \round((float)($row->price ?? 0), 2),
        ];
    }

    /**
     * @param array<string, mixed> $rule
     */
    public static function isUsable(array $rule): bool
    {
        return $rule['products'] !== [] && $rule['price'] > 0 && ($rule['type'] !== 'set' || $rule['partners'] !== []);
    }

    /**
     * Regeln aller freigeschalteten Deals, die gerade laufen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeRules(): array
    {
        if (self::$active !== null) {
            return self::$active;
        }
        $unlocked = self::unlockedIDs();
        if ($unlocked === []) {
            return self::$active = [];
        }
        $running = [];
        try {
            $pages = $this->db->getObjects(
                'SELECT * FROM ' . DealPageService::TABLE . ' WHERE id IN (' . \implode(',', $unlocked) . ')'
            );
            foreach ($pages as $page) {
                if (DealPageService::status($page) === 'active') {
                    $running[] = (int)$page->id;
                }
            }
        } catch (\Throwable) {
            return self::$active = [];
        }

        return self::$active = $this->rulesFor($running);
    }

    /**
     * @param int[] $ids
     */
    public static function matches(array $ids, int $productID, int $parentID): bool
    {
        return \in_array($productID, $ids, true) || ($parentID > 0 && \in_array($parentID, $ids, true));
    }

    /* ----------------------------------------------------------- Warenkorb */

    /**
     * HOOK_SETZTE_POSITIONSPREISE: Deal-Preis als Positionspreis übernehmen (netto, wie der Core rechnet).
     */
    public function applyToCartItem(object $item): void
    {
        unset(self::$notes[\spl_object_id($item)]);
        $rules = $this->activeRules();
        if ($rules === [] || (int)($item->kKonfigitem ?? 0) > 0 || (int)($item->kArtikel ?? 0) <= 0) {
            return;
        }
        $productID = (int)$item->kArtikel;
        $parentID  = (int)($item->Artikel->kVaterArtikel ?? 0);
        $qty       = \max(1.0, (float)($item->nAnzahl ?? 1));
        $taxRate   = (float)Tax::getSalesTax((int)($item->kSteuerklasse ?? 0));
        $normal    = (float)$item->fPreis * (100 + $taxRate) / 100;

        $result = self::bestPrice($rules, $productID, $parentID, $qty, $normal, $this->cartQuantities($item));
        if ($result === null) {
            return;
        }
        $net                     = $result['unit'] * 100 / (100 + $taxRate);
        $item->fPreis            = $net;
        $item->fPreisEinzelNetto = $net;
        self::$notes[\spl_object_id($item)] = $this->note($result, $normal);
    }

    /**
     * HOOK_SET_POSITION_PRICES_END: Hinweis an der Position (der Core leert cHinweis vorher).
     */
    public function noteCartItem(object $item): void
    {
        $note = self::$notes[\spl_object_id($item)] ?? '';
        if ($note !== '') {
            $item->cHinweis = $note;
        }
    }

    /**
     * Günstigster Stückpreis (brutto) aus Festpreis- und Set-Regeln oder null, wenn der Shop-Preis günstiger ist.
     *
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array{0: int, 1: int, 2: float}> $cart Positionen als [kArtikel, kVaterArtikel, Menge]
     * @return array{unit: float, single: ?float, set: ?float, setUnits: float, qty: float}|null
     */
    public static function bestPrice(array $rules, int $productID, int $parentID, float $qty, float $normal, array $cart): ?array
    {
        $single = null;
        $set    = null;
        $pairs  = 0.0;
        foreach ($rules as $rule) {
            if (!self::matches($rule['products'], $productID, $parentID)) {
                continue;
            }
            if ($rule['type'] === 'price') {
                $single = $single === null ? $rule['price'] : \min($single, $rule['price']);
                continue;
            }
            $partnerQty = 0.0;
            foreach ($cart as [$id, $parent, $count]) {
                if (self::matches($rule['partners'], $id, $parent)) {
                    $partnerQty += $count;
                }
            }
            if ($partnerQty > 0 && ($set === null || $rule['price'] < $set)) {
                $set   = $rule['price'];
                $pairs = $partnerQty;
            }
        }
        $base     = $single !== null ? \min($single, $normal) : $normal;
        $setUnits = $set !== null && $set < $base ? \min($qty, $pairs) : 0.0;
        $unit     = ($setUnits * (float)$set + ($qty - $setUnits) * $base) / $qty;
        if ($unit >= $normal - 0.005) {
            return null;
        }

        return ['unit' => \round($unit, 4), 'single' => $single, 'set' => $setUnits > 0 ? $set : null, 'setUnits' => $setUnits, 'qty' => $qty];
    }

    /**
     * Artikelpositionen des Warenkorbs ohne die gerade berechnete Position.
     *
     * @return array<int, array{0: int, 1: int, 2: float}>
     */
    private function cartQuantities(object $current): array
    {
        $list = [];
        try {
            foreach (Frontend::getCart()->PositionenArr as $position) {
                if ($position === $current || (int)($position->nPosTyp ?? 0) !== \C_WARENKORBPOS_TYP_ARTIKEL) {
                    continue;
                }
                $list[] = [
                    (int)$position->kArtikel,
                    (int)($position->Artikel->kVaterArtikel ?? 0),
                    (float)$position->nAnzahl,
                ];
            }
        } catch (\Throwable) {
        }

        return $list;
    }

    /**
     * @param array{unit: float, single: ?float, set: ?float, setUnits: float, qty: float} $result
     */
    private function note(array $result, float $normal): string
    {
        $isEn    = CountdownService::currentLanguage() === 'eng';
        $instead = self::formatGross($normal);
        if ($result['set'] !== null && $result['setUnits'] < $result['qty']) {
            return $isEn
                ? \sprintf('Newsletter deal: %s× set price %s (instead of %s)', self::formatQty($result['setUnits']), self::formatGross($result['set']), $instead)
                : \sprintf('Newsletter-Deal: %s× Set-Preis %s (statt %s)', self::formatQty($result['setUnits']), self::formatGross($result['set']), $instead);
        }
        if ($result['set'] !== null) {
            return $isEn ? 'Newsletter set price (instead of ' . $instead . ')' : 'Newsletter-Set-Preis (statt ' . $instead . ')';
        }

        return $isEn ? 'Newsletter deal (instead of ' . $instead . ')' : 'Newsletter-Deal (statt ' . $instead . ')';
    }

    /* ------------------------------------------------------------- Anzeige */

    /**
     * Hinweise für productdetails/price.tpl (Liste und Artikelseite): kArtikel => Zeilen. Leer, solange der Kunde
     * keinen laufenden Deal freigeschaltet hat.
     *
     * @return array<int, array{price: string, sets: array<int, array{price: string, label: string}>}>
     */
    public function displayMap(): array
    {
        $rules = $this->activeRules();
        if ($rules === []) {
            return [];
        }
        $partnerIDs = [];
        foreach ($rules as $rule) {
            $partnerIDs = \array_merge($partnerIDs, $rule['partners']);
        }
        $names = $this->productNames($partnerIDs);
        $map   = [];
        foreach ($rules as $rule) {
            foreach ($rule['products'] as $id) {
                $map[$id] ??= ['price' => null, 'sets' => []];
                if ($rule['type'] === 'price') {
                    $map[$id]['price'] = $map[$id]['price'] === null ? $rule['price'] : \min($map[$id]['price'], $rule['price']);
                    continue;
                }
                $partners           = \implode(' / ', \array_filter(\array_map(
                    static fn(int $partner): string => $names[$partner] ?? '',
                    $rule['partners']
                )));
                $map[$id]['sets'][] = [
                    'price' => self::formatGross($rule['price']),
                    'label' => \sprintf(self::labels()['set'], $partners),
                ];
            }
        }
        foreach ($map as $id => $entry) {
            $map[$id]['price'] = $entry['price'] === null ? '' : self::formatGross($entry['price']);
        }

        return $map;
    }

    /**
     * Beschriftungen für die Anzeige.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $isEn = CountdownService::currentLanguage() === 'eng';

        return [
            'price' => $isEn ? 'Newsletter price' : 'Newsletter-Preis',
            'set'   => $isEn ? 'In a set with %s' : 'Im Set mit %s',
            'each'  => $isEn ? 'each' : 'je Stück',
        ];
    }

    /**
     * Artikelnamen in der aktuellen Sprache (Fallback: Standardname).
     *
     * @param int[] $ids
     * @return array<int, string>
     */
    private function productNames(array $ids): array
    {
        $ids = \array_values(\array_unique(\array_filter($ids)));
        if ($ids === []) {
            return [];
        }
        $names = [];
        try {
            foreach ($this->db->getObjects('SELECT kArtikel, cName FROM tartikel WHERE kArtikel IN (' . \implode(',', $ids) . ')') as $row) {
                $names[(int)$row->kArtikel] = self::plain($row->cName);
            }
            $langID = Shop::getLanguageID();
            if ($langID > 0) {
                foreach ($this->db->getObjects(
                    'SELECT kArtikel, cName FROM tartikelsprache WHERE kSprache = :lang AND kArtikel IN (' . \implode(',', $ids) . ')',
                    ['lang' => $langID]
                ) as $row) {
                    if (\trim((string)$row->cName) !== '') {
                        $names[(int)$row->kArtikel] = self::plain($row->cName);
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logError($e);
        }

        return $names;
    }

    /**
     * Bruttopreis in der Shop-Währung formatieren (Deal-Preise sind Endkundenpreise inkl. MwSt.).
     */
    public static function formatGross(float $gross): string
    {
        try {
            return \html_entity_decode(Preise::getLocalizedPriceString($gross, null, true), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        } catch (\Throwable) {
            return \number_format($gross, 2, ',', '.') . ' €';
        }
    }

    private static function formatQty(float $qty): string
    {
        return \rtrim(\rtrim(\number_format($qty, 2, ',', ''), '0'), ',');
    }

    private static function plain(mixed $value): string
    {
        return \trim(\html_entity_decode((string)$value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
    }

    /**
     * Nur für Tests: Request-Cache leeren.
     */
    public static function reset(): void
    {
        self::$active = null;
        self::$notes  = [];
    }

    private function logError(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->error('startseite_plus Deal-Preise: ' . $e->getMessage());
        } catch (\Throwable) {
        }
    }
}
