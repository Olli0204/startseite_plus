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
    public const IO_FUNCTION  = 'startseitePlusDeal';
    public const MAX_PRODUCTS = 4;

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
     * @param string[] $numbers
     * @return Artikel[]
     */
    public function loadProducts(array $numbers): array
    {
        $products = [];
        foreach ($numbers as $number) {
            try {
                $row = $this->db->getSingleObject(
                    'SELECT kArtikel FROM tartikel WHERE cArtNr = :nr LIMIT 1',
                    ['nr' => $number]
                );
                if ($row === null) {
                    continue;
                }
                $product = new Artikel();
                $product->fuelleArtikel((int)$row->kArtikel, Artikel::getDefaultOptions());
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
     * Artikel, die ohne Auswahl (Variation, Konfigurator) und mit Bestand direkt in den Warenkorb können.
     */
    public static function isDirectlyBuyable(Artikel $product): bool
    {
        if ((int)($product->nIstVater ?? 0) === 1 || !empty($product->Variationen) || !empty($product->bHasKonfig)) {
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
    public function buildView(string $code, string $numbers): array
    {
        $lang     = CountdownService::currentLanguage();
        $isEn     = $lang === 'eng';
        $coupon   = $this->findCoupon($code);
        $problems = $this->displayProblems($coupon, $code);
        $products = $this->loadProducts(self::parseNumbers($numbers, $coupon));
        $netto    = Frontend::getCustomerGroup()->isMerchant();

        $items    = [];
        $sumNet   = 0.0;
        $sumGross = 0.0;
        $buyable  = $products !== [];
        foreach ($products as $product) {
            $net      = (float)($product->Preise->fVKNetto ?? 0);
            $gross    = (float)($product->Preise->fVKBrutto ?? 0);
            $sumNet   += $net;
            $sumGross += $gross;
            $direct   = self::isDirectlyBuyable($product);
            $buyable  = $buyable && $direct;
            $image    = $product->Bilder[0] ?? null;
            $items[]  = [
                'id'     => (int)$product->kArtikel,
                'name'   => (string)$product->cName,
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
                if (CartHelper::addProductIDToCart($id, 1, [], 1)) {
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
