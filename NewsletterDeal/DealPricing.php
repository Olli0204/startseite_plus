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
 *   setpct – Set-Rabatt: wie set, aber der Artikel kostet price Prozent weniger als sein aktueller Shop-Preis
 * Ein Vaterartikel in einer Regel gilt für alle seine Varianten. Artikel und Set-Partner können zusätzlich über einen
 * Merkmalwert gewählt werden (z. B. Warengruppe = Snowboard, seit 2.17.0); rulesFor() ergänzt dann alle Artikel mit
 * diesem Merkmalwert (Vater oder Kind – matches() prüft beides).
 *
 * Freigeschaltet werden die Preise pro Sitzung: durch den Besuch des geheimen Links oder durch den Deal-Code im
 * Kupon-Feld. Der Warenkorb übernimmt sie in HOOK_SETZTE_POSITIONSPREISE direkt als Positionspreis (der Core ruft
 * Cart::setzePositionsPreise() bei jeder Änderung auf); ein Deal-Preis ersetzt den Shop-Preis nur, wenn er günstiger ist.
 */
final class DealPricing
{
    public const SESSION_KEY = 'startseitePlusNlDeals';
    /** Anzeige im Warenkorb (basket/cart_items.tpl): kArtikel => regulärer Preis, Marke, Stückzahl */
    public const CART_INFO_KEY = 'startseitePlusNlCartInfo';
    public const TABLE_RULES = 'startseite_plus_nl_deal_rule';
    public const TYPES       = ['price', 'set', 'setpct'];
    public const SET_TYPES   = ['set', 'setpct'];
    public const IO_FUNCTION = 'startseitePlusNlDealPrices';

    /** @var array<int, int[]> Artikel-IDs je Merkmalwert (pro Request) */
    private static array $featureArticles = [];

    /** @var array<int, int[]> Artikel-IDs je Kategorie (pro Request) */
    private static array $categoryArticles = [];

    /** @var array<int, array<string, mixed>>|null Regeln der freigeschalteten, laufenden Deals (pro Request) */
    private static ?array $active = null;

    /** @var array<int, true>|null Artikel mit Deal-Preis in irgendeinem laufenden Deal (pro Request) */
    private static ?array $running = null;

    /** @var array<int, array<string, mixed>>|null Regeln laufender Deals (pro Request) */
    private static ?array $runningRules = null;

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

        $rules = \array_values(\array_filter(\array_map(static fn(stdClass $row): array => self::ruleFromRow($row), $rows),
            static fn(array $rule): bool => self::isUsable($rule)));

        return \array_map(fn(array $rule): array => $this->expandFeatures($rule), $rules);
    }

    /**
     * Regel aus einer Tabellenzeile (ohne Merkmal-Artikel – die ergänzt erst rulesFor()).
     *
     * @return array<string, mixed>
     */
    public static function ruleFromRow(stdClass $row): array
    {
        return [
            'id'             => (int)($row->id ?? 0),
            'dealID'         => (int)($row->deal_id ?? 0),
            'type'           => \in_array($row->type ?? '', self::TYPES, true) ? (string)$row->type : 'price',
            'products'       => DealPageService::parseIds((string)($row->products ?? '')),
            'partners'       => DealPageService::parseIds((string)($row->partners ?? '')),
            'productFeature' => \max(0, (int)($row->product_feature ?? 0)),
            'partnerFeature' => \max(0, (int)($row->partner_feature ?? 0)),
            'productCategory' => \max(0, (int)($row->product_category ?? 0)),
            'partnerCategory' => \max(0, (int)($row->partner_category ?? 0)),
            'price'          => \round((float)($row->price ?? 0), 2),
            'label'          => \trim((string)($row->label ?? '')),
            'labelEn'        => \trim((string)($row->label_en ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $rule
     */
    public static function isSetRule(array $rule): bool
    {
        return \in_array($rule['type'], self::SET_TYPES, true);
    }

    /**
     * @param array<string, mixed> $rule
     */
    public static function isUsable(array $rule): bool
    {
        if (($rule['products'] === [] && ($rule['productFeature'] ?? 0) <= 0 && ($rule['productCategory'] ?? 0) <= 0)
            || $rule['price'] <= 0
        ) {
            return false;
        }
        if ($rule['type'] === 'setpct' && $rule['price'] >= 100) {
            return false;
        }

        return !self::isSetRule($rule) || $rule['partners'] !== [] || ($rule['partnerFeature'] ?? 0) > 0
            || ($rule['partnerCategory'] ?? 0) > 0;
    }

    /**
     * Stückpreis (brutto) einer Regel für einen Artikel mit dem aktuellen Shop-Preis $normal.
     *
     * @param array<string, mixed> $rule
     */
    public static function rulePrice(array $rule, float $normal): float
    {
        return $rule['type'] === 'setpct' ? \round($normal * (100 - $rule['price']) / 100, 2) : (float)$rule['price'];
    }

    /**
     * Artikel mit den Merkmalwerten der Regel ergänzen (Artikel und Set-Partner).
     *
     * @param array<string, mixed> $rule
     * @return array<string, mixed>
     */
    private function expandFeatures(array $rule): array
    {
        $rule['pickedPartners'] = $rule['partners'];
        foreach (['products' => 'productFeature', 'partners' => 'partnerFeature'] as $list => $feature) {
            if (($rule[$feature] ?? 0) > 0) {
                $rule[$list] = \array_values(\array_unique(\array_merge($rule[$list], $this->featureArticles($rule[$feature]))));
            }
        }
        foreach (['products' => 'productCategory', 'partners' => 'partnerCategory'] as $list => $category) {
            if (($rule[$category] ?? 0) > 0) {
                $rule[$list] = \array_values(\array_unique(\array_merge($rule[$list], $this->categoryArticles($rule[$category]))));
            }
        }

        return $rule;
    }

    /**
     * Alle Artikel (Väter und Kinder, wie von der Wawi übertragen) mit dem Merkmalwert.
     *
     * @return int[]
     */
    public function featureArticles(int $valueID): array
    {
        if ($valueID <= 0) {
            return [];
        }
        if (!isset(self::$featureArticles[$valueID])) {
            try {
                self::$featureArticles[$valueID] = \array_map(
                    static fn(stdClass $row): int => (int)$row->kArtikel,
                    $this->db->getObjects(
                        'SELECT DISTINCT kArtikel FROM tartikelmerkmal WHERE kMerkmalWert = :id',
                        ['id' => $valueID]
                    )
                );
            } catch (\Throwable $e) {
                $this->logError($e);
                self::$featureArticles[$valueID] = [];
            }
        }

        return self::$featureArticles[$valueID];
    }

    /**
     * Alle Artikel einer Kategorie (direkt zugeordnet; Varianten über ihren Vaterartikel, siehe matches()).
     *
     * @return int[]
     */
    public function categoryArticles(int $categoryID): array
    {
        if ($categoryID <= 0) {
            return [];
        }
        if (!isset(self::$categoryArticles[$categoryID])) {
            try {
                self::$categoryArticles[$categoryID] = \array_map(
                    static fn(stdClass $row): int => (int)$row->kArtikel,
                    $this->db->getObjects(
                        'SELECT DISTINCT kArtikel FROM tkategorieartikel WHERE kKategorie = :id',
                        ['id' => $categoryID]
                    )
                );
            } catch (\Throwable $e) {
                $this->logError($e);
                self::$categoryArticles[$categoryID] = [];
            }
        }

        return self::$categoryArticles[$categoryID];
    }

    /**
     * Name einer Kategorie in der aktuellen Sprache (Fallback: Standardname).
     */
    public function categoryName(int $categoryID): string
    {
        if ($categoryID <= 0) {
            return '';
        }
        try {
            $row = $this->db->getSingleObject(
                'SELECT k.cName, ks.cName AS localized FROM tkategorie k
                    LEFT JOIN tkategoriesprache ks ON ks.kKategorie = k.kKategorie AND ks.kSprache = :lang
                    WHERE k.kKategorie = :id',
                ['id' => $categoryID, 'lang' => Shop::getLanguageID()]
            );
        } catch (\Throwable) {
            return '';
        }
        if ($row === null) {
            return '';
        }

        return self::plain(\trim((string)($row->localized ?? '')) !== '' ? $row->localized : $row->cName);
    }

    /**
     * Name eines Merkmalwerts in der aktuellen Sprache (Fallback: irgendeine Sprache).
     */
    public function featureName(int $valueID): string
    {
        if ($valueID <= 0) {
            return '';
        }
        try {
            $row = $this->db->getSingleObject(
                'SELECT cWert FROM tmerkmalwertsprache WHERE kMerkmalWert = :id
                    ORDER BY kSprache = :lang DESC, kSprache LIMIT 1',
                ['id' => $valueID, 'lang' => Shop::getLanguageID()]
            );
        } catch (\Throwable) {
            return '';
        }

        return $row === null ? '' : self::plain($row->cWert);
    }

    /**
     * Hinweistext einer Set-Regel in der aktuellen Sprache: eigener Text, sonst aus Partnern bzw. Merkmalwert gebildet.
     *
     * @param array<string, mixed> $rule
     * @param string $partners Partnernamen (nur bei wenigen, einzeln gewählten Partnern)
     */
    public function setLabel(array $rule, string $partners = ''): string
    {
        $isEn   = CountdownService::currentLanguage() === 'eng';
        $custom = $isEn && $rule['labelEn'] !== '' ? $rule['labelEn'] : $rule['label'];
        if ($custom !== '') {
            return $custom;
        }
        if (($rule['partnerFeature'] ?? 0) > 0) {
            $partners = $this->featureName($rule['partnerFeature']);
        } elseif (($rule['partnerCategory'] ?? 0) > 0) {
            $partners = $this->categoryName($rule['partnerCategory']);
        }
        if ($rule['type'] === 'setpct') {
            $percent = self::formatPercent($rule['price']);

            return $partners !== ''
                ? \sprintf($isEn ? '%s in a set with %s' : '%s im Set mit %s', $percent, $partners)
                : \sprintf($isEn ? '%s in a set' : '%s im Set', $percent);
        }
        $labels = self::labels();

        return $partners !== '' ? \sprintf($labels['set'], $partners) : $labels['inSet'];
    }

    /**
     * Set-Partner einer Regel für Hinweise: Merkmalwert, Kategorie oder die Namen einzeln gewählter Partner.
     *
     * @param array<string, mixed> $rule
     */
    private function partnerText(array $rule): string
    {
        if (($rule['partnerFeature'] ?? 0) > 0) {
            return $this->featureName($rule['partnerFeature']);
        }
        if (($rule['partnerCategory'] ?? 0) > 0) {
            return $this->categoryName($rule['partnerCategory']);
        }

        return \implode(' / ', \array_values(\array_unique(\array_filter($this->partnerNames($rule['pickedPartners'] ?? $rule['partners'])))));
    }

    public static function formatPercent(float $percent): string
    {
        return '−' . \rtrim(\rtrim(\number_format($percent, 2, ',', ''), '0'), ',') . ' %';
    }

    /**
     * Regeln aller Deals, die gerade laufen und für diese Sitzung gelten: freigeschaltet (Link/Code) oder öffentlich.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeRules(): array
    {
        if (self::$active !== null) {
            return self::$active;
        }
        $unlocked = self::unlockedIDs();
        $running  = [];
        try {
            // laufende Seiten, die diese Sitzung freigeschaltet hat oder die schon für alle Kunden gelten (public_from)
            foreach ($this->db->getObjects('SELECT * FROM ' . DealPageService::TABLE . ' WHERE active = 1') as $page) {
                if (DealPageService::status($page) !== 'active') {
                    continue;
                }
                if (\in_array((int)$page->id, $unlocked, true) || DealPageService::isPublic($page)) {
                    $running[] = (int)$page->id;
                }
            }
        } catch (\Throwable) {
            return self::$active = [];
        }

        return self::$active = $this->rulesFor($running);
    }

    /**
     * Artikel-IDs mit Deal-Preis in laufenden Deals – unabhängig von der Sitzung. Nur daraus entstehen die (leeren)
     * Platzhalter im HTML, damit Seiten für alle Besucher identisch sind und ein Seitencache (z. B. LiteSpeed) keine
     * freigeschalteten Preise an andere Kunden ausliefert.
     *
     * @return array<int, true>
     */
    public function runningProductIDs(): array
    {
        if (self::$running !== null) {
            return self::$running;
        }
        $ids = [];
        foreach ($this->runningRules() as $rule) {
            foreach ($rule['products'] as $id) {
                $ids[$id] = true;
            }
        }

        return self::$running = $ids;
    }

    /**
     * Regeln aller laufenden Deals – unabhängig von der Sitzung (nur für Platzhalter, nie für Preise).
     *
     * @return array<int, array<string, mixed>>
     */
    public function runningRules(): array
    {
        if (self::$runningRules !== null) {
            return self::$runningRules;
        }
        $running = [];
        try {
            foreach ($this->db->getObjects('SELECT * FROM ' . DealPageService::TABLE . ' WHERE active = 1') as $page) {
                if (DealPageService::status($page) === 'active') {
                    $running[] = (int)$page->id;
                }
            }
        } catch (\Throwable) {
            return self::$runningRules = [];
        }

        return self::$runningRules = $this->rulesFor($running);
    }

    /**
     * IO-Funktion für newsletter-deal.js: Deal-Preis-Hinweise der angefragten Artikel – nur für eine Sitzung mit
     * freigeschaltetem, laufendem Deal, sonst leer.
     *
     * @return array{prices: array<int, array<string, mixed>>, labels: array<string, string>}
     */
    public function ioPrices(mixed $ids): array
    {
        $wanted = \is_array($ids) ? $ids : \preg_split('/[,;\s]+/', \is_scalar($ids) ? (string)$ids : '');
        $wanted = \array_slice(\array_values(\array_unique(\array_filter(\array_map('intval', $wanted ?: [])))), 0, 200);
        $map    = $wanted === [] ? [] : $this->displayMap();

        return [
            'prices' => \array_intersect_key($map, \array_flip($wanted)),
            'labels' => self::labels(),
        ];
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
        if (isset($item->kArtikel) && \is_array($_SESSION[self::CART_INFO_KEY] ?? null)) {
            unset($_SESSION[self::CART_INFO_KEY][(int)$item->kArtikel]);
        }
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
        $info = \is_array($_SESSION[self::CART_INFO_KEY] ?? null) ? $_SESSION[self::CART_INFO_KEY] : [];
        $info[$productID]              = $this->cartInfo($result, $normal);
        $_SESSION[self::CART_INFO_KEY] = $info;
    }

    /**
     * Daten für die Anzeige unter dem Stückpreis im Warenkorb: regulärer Preis (durchgestrichen), Marke und – wenn nur
     * ein Teil der Menge reduziert ist – die Stückzahl.
     *
     * @param array{unit: float, single: ?float, set: ?float, setUnits: float, qty: float, setRule: ?array} $result
     * @return array{regular: string, badge: string, units: string, title: string}
     */
    private function cartInfo(array $result, float $normal): array
    {
        $isEn  = CountdownService::currentLanguage() === 'eng';
        $rule  = $result['setRule'] ?? null;
        $badge = match (true) {
            $rule !== null && $rule['type'] === 'setpct' => ($isEn ? 'Set discount ' : 'Set-Rabatt ') . self::formatPercent($rule['price']),
            $result['set'] !== null                       => $isEn ? 'Set price' : 'Set-Preis',
            default                                       => self::labels()['price'],
        };
        $units = $result['set'] !== null && $result['setUnits'] < $result['qty']
            ? \sprintf($isEn ? 'for %s of %s' : 'für %s von %s Stück', self::formatQty($result['setUnits']), self::formatQty($result['qty']))
            : '';

        return [
            'regular' => self::formatGross($normal),
            'badge'   => $badge,
            'units'   => $units,
            'title'   => $rule !== null ? $this->note($result, $normal) : '',
        ];
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
     * Set-Partner werden über den ganzen Warenkorb verteilt: Positionen derselben Regel, die vor dieser Position im
     * Warenkorb liegen, verbrauchen zuerst Partner (1 Board + 2 verschiedene Bindungen → nur eine Bindung reduziert).
     *
     * @param array<int, array<string, mixed>> $rules
     * @param array<int, array{0: int, 1: int, 2: float, 3?: bool}> $cart andere Positionen als
     *     [kArtikel, kVaterArtikel, Menge, liegt vor dieser Position]
     * @return array{unit: float, single: ?float, set: ?float, setUnits: float, qty: float, setRule: ?array}|null
     */
    public static function bestPrice(array $rules, int $productID, int $parentID, float $qty, float $normal, array $cart): ?array
    {
        $single  = null;
        $set     = null;
        $setRule = null;
        $pairs   = 0.0;
        foreach ($rules as $rule) {
            if (!self::matches($rule['products'], $productID, $parentID)) {
                continue;
            }
            if ($rule['type'] === 'price') {
                $single = $single === null ? $rule['price'] : \min($single, $rule['price']);
                continue;
            }
            $partnerQty = 0.0;
            $used       = 0.0;
            foreach ($cart as $position) {
                [$id, $parent, $count] = $position;
                if (self::matches($rule['partners'], $id, $parent)) {
                    $partnerQty += $count;
                } elseif (($position[3] ?? false) && self::matches($rule['products'], $id, $parent)) {
                    $used += $count;
                }
            }
            $available = $partnerQty - $used;
            $price     = self::rulePrice($rule, $normal);
            if ($available > 0 && ($set === null || $price < $set)) {
                $set     = $price;
                $setRule = $rule;
                $pairs   = $available;
            }
        }
        $base     = $single !== null ? \min($single, $normal) : $normal;
        $setUnits = $set !== null && $set < $base ? \min($qty, $pairs) : 0.0;
        $unit     = ($setUnits * (float)$set + ($qty - $setUnits) * $base) / $qty;
        if ($unit >= $normal - 0.005) {
            return null;
        }

        return [
            'unit'     => \round($unit, 4),
            'single'   => $single,
            'set'      => $setUnits > 0 ? $set : null,
            'setUnits' => $setUnits,
            'qty'      => $qty,
            'setRule'  => $setUnits > 0 ? $setRule : null,
        ];
    }

    /**
     * Artikelpositionen des Warenkorbs ohne die gerade berechnete Position, mit Merker "liegt davor".
     *
     * @return array<int, array{0: int, 1: int, 2: float, 3: bool}>
     */
    private function cartQuantities(object $current): array
    {
        $list   = [];
        $before = true;
        try {
            foreach (Frontend::getCart()->PositionenArr as $position) {
                if ($position === $current) {
                    $before = false;
                    continue;
                }
                if ((int)($position->nPosTyp ?? 0) !== \C_WARENKORBPOS_TYP_ARTIKEL) {
                    continue;
                }
                $list[] = [
                    (int)$position->kArtikel,
                    (int)($position->Artikel->kVaterArtikel ?? 0),
                    (float)$position->nAnzahl,
                    $before,
                ];
            }
        } catch (\Throwable) {
        }

        return $list;
    }

    /**
     * @param array{unit: float, single: ?float, set: ?float, setUnits: float, qty: float, setRule: ?array} $result
     */
    private function note(array $result, float $normal): string
    {
        $isEn    = CountdownService::currentLanguage() === 'eng';
        $instead = self::formatGross($normal);
        $rule    = $result['setRule'] ?? null;
        if ($result['set'] !== null && $rule !== null && $rule['type'] === 'setpct') {
            // wie die übrigen Zeilen der Position ("Größe: M"): Bezeichnung, dann Rabatt und Grund
            $custom   = $isEn && $rule['labelEn'] !== '' ? $rule['labelEn'] : $rule['label'];
            $partners = $this->partnerText($rule);
            $percent  = self::formatPercent($rule['price']);
            $units    = $result['setUnits'] < $result['qty']
                ? \sprintf($isEn ? ' on %s of %s' : ' auf %s von %s Stück', self::formatQty($result['setUnits']), self::formatQty($result['qty']))
                : '';
            $text     = match (true) {
                $custom !== ''   => $custom . ($units !== '' ? ' (' . \trim($units) . ')' : ''),
                $partners !== '' => $percent . $units . ($isEn ? ' together with ' : ' zusammen mit ') . $partners,
                default          => $percent . $units,
            };

            return ($isEn ? 'Set discount: ' : 'Set-Rabatt: ') . $text
                . ($isEn ? ' (regular price ' : ' (regulär ') . $instead . ')';
        }
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

    public const DISPLAY_MODES = ['line', 'price', 'badge'];

    /**
     * Anzeige-Daten für newsletter-deal.js (Liste und Artikelseite): kArtikel => Festpreis, Set-Preise und
     * Darstellungsart der Deal-Seite. Leer, solange der Kunde keinen laufenden Deal freigeschaltet hat.
     *
     * @return array<int, array{price: string, value: ?float, mode: string,
     *     sets: array<int, array{price: string, value: float, label: string}>}>
     */
    public function displayMap(): array
    {
        $rules = $this->activeRules();
        if ($rules === []) {
            return [];
        }
        $modes      = $this->displayModes(\array_values(\array_unique(\array_column($rules, 'dealID'))));
        $partnerIDs = [];
        foreach ($rules as $rule) {
            $partnerIDs = \array_merge($partnerIDs, $rule['pickedPartners'] ?? []);
        }
        $names  = $this->partnerNames($partnerIDs);
        $labels = self::labels();
        $map    = [];
        foreach ($rules as $rule) {
            $mode = $modes[$rule['dealID']] ?? 'line';
            $set  = null;
            if (self::isSetRule($rule)) {
                // Partnernamen nur aus einzeln gewählten Partnern (Varianten zum Vater zusammengefasst, jeder Name
                // einmal); bei Merkmal-Partnern nennt setLabel() den Merkmalwert
                $partners = \implode(' / ', \array_values(\array_unique(\array_filter(\array_map(
                    static fn(int $partner): string => $names[$partner] ?? '',
                    $rule['pickedPartners'] ?? []
                )))));
                $label    = $this->setLabel($rule, $partners);
                // setpct: den Preis rechnet newsletter-deal.js aus dem aktuellen Shop-Preis (data-sp-nld-current)
                $set = $rule['type'] === 'setpct'
                    ? ['price' => '', 'value' => null, 'percent' => $rule['price'], 'label' => $label,
                       'short' => self::formatPercent($rule['price']) . ' ' . $labels['inSet'],
                       'badge' => $labels['setShort'] . ' ' . self::formatPercent($rule['price'])]
                    : ['price' => self::formatGross($rule['price']), 'value' => $rule['price'], 'label' => $label];
            }
            foreach ($rule['products'] as $id) {
                $map[$id] ??= ['value' => null, 'mode' => $mode, 'sets' => [], 'singleMode' => null];
                if ($set !== null) {
                    $map[$id]['sets'][] = $set;
                    continue;
                }
                if ($map[$id]['value'] === null || $rule['price'] < $map[$id]['value']) {
                    $map[$id]['value']      = $rule['price'];
                    $map[$id]['singleMode'] = $mode;
                }
            }
        }
        foreach ($map as $id => $entry) {
            $map[$id]['mode']  = $entry['singleMode'] ?? $entry['mode'];
            $map[$id]['price'] = $entry['value'] === null ? '' : self::formatGross($entry['value']);
            unset($map[$id]['singleMode']);
        }

        return $map;
    }

    /**
     * Darstellungsart je Deal-Seite (Spalte display, seit 2.15.0).
     *
     * @param int[] $dealIDs
     * @return array<int, string>
     */
    private function displayModes(array $dealIDs): array
    {
        $dealIDs = \array_values(\array_filter(\array_map('intval', $dealIDs)));
        if ($dealIDs === []) {
            return [];
        }
        $modes = [];
        try {
            foreach ($this->db->getObjects(
                'SELECT id, display FROM ' . DealPageService::TABLE . ' WHERE id IN (' . \implode(',', $dealIDs) . ')'
            ) as $row) {
                $modes[(int)$row->id] = self::displayMode((string)($row->display ?? ''));
            }
        } catch (\Throwable) {
        }

        return $modes;
    }

    public static function displayMode(string $mode): string
    {
        return \in_array($mode, self::DISPLAY_MODES, true) ? $mode : 'line';
    }

    /**
     * Namen der Set-Partner, Kinderartikel unter dem Namen ihres Vaterartikels.
     *
     * @param int[] $ids
     * @return array<int, string> Partner-ID => Anzeigename
     */
    private function partnerNames(array $ids): array
    {
        $ids = \array_values(\array_unique(\array_filter(\array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $parents = [];
        try {
            foreach ($this->db->getObjects(
                'SELECT kArtikel, kVaterArtikel FROM tartikel WHERE kArtikel IN (' . \implode(',', $ids) . ')'
            ) as $row) {
                $parents[(int)$row->kArtikel] = (int)$row->kVaterArtikel > 0 ? (int)$row->kVaterArtikel : (int)$row->kArtikel;
            }
        } catch (\Throwable $e) {
            $this->logError($e);
        }
        $names  = $this->productNames(\array_values(\array_unique(\array_merge($ids, \array_values($parents)))));
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = $names[$parents[$id] ?? $id] ?? ($names[$id] ?? '');
        }

        return $result;
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
            'price'    => $isEn ? 'Newsletter price' : 'Newsletter-Preis',
            'set'      => $isEn ? 'In a set with %s' : 'Im Set mit %s',
            'inSet'    => $isEn ? 'in a set' : 'im Set',
            'each'     => $isEn ? 'each' : 'je Stück',
            'oldPrice' => $isEn ? 'Old price' : 'Alter Preis',
            'badge'    => $isEn ? 'Newsletter' : 'Newsletter',
            'setBadge' => $isEn ? 'Newsletter set' : 'Newsletter-Set',
            'setShort' => $isEn ? 'Set' : 'Set',
            'locale'   => $isEn ? 'en-GB' : 'de-DE',
            'currency' => self::currencyCode(),
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

    private static function currencyCode(): string
    {
        try {
            return (string)(Frontend::getCurrency()->getCode() ?: 'EUR');
        } catch (\Throwable) {
            return 'EUR';
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
        self::$active  = null;
        self::$running      = null;
        self::$runningRules = null;
        self::$notes        = [];
    }

    private function logError(\Throwable $e): void
    {
        try {
            Shop::Container()->getLogService()->error('startseite_plus Deal-Preise: ' . $e->getMessage());
        } catch (\Throwable) {
        }
    }
}
