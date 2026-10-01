<?php

declare(strict_types=1);

namespace Plugin\startseite_plus;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Router\Router;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Laminas\Diactoros\ServerRequestFactory;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Deal\DealService;
use Plugin\startseite_plus\NewsletterDeal\DealPageAdmin;
use Plugin\startseite_plus\NewsletterDeal\DealPageRoute;
use Plugin\startseite_plus\NewsletterDeal\DealPageService;
use Plugin\startseite_plus\NewsletterDeal\DealPricing;

use function Functional\first;

/**
 * Startseite Plus: OPC-Portlets plus zentrale Countdown-Verwaltung und Newsletter-Deal-Seiten.
 * Der Bootstrap stellt Countdowns für Artikeldetailseiten bereit, registriert die IO-Funktion des
 * Deal-Banners (Artikel in den Warenkorb + Kupon einlösen), die Routen der Newsletter-Deal-Seiten
 * und rendert die Admin-Tabs.
 */
class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);

        $dispatcher->hookInto(\HOOK_ARTIKEL_PAGE, function (array $args): void {
            $this->assignProductCountdowns($args['oArtikel'] ?? null);
        });

        $dispatcher->hookInto(\HOOK_IO_HANDLE_REQUEST, function (array $args): void {
            // Deal-Preis-Platzhalter auch in per IO nachgeladenem Markup (z. B. Variantenwechsel auf der Artikelseite)
            $this->assignDealPrices();
            $io = $args['io'] ?? null;
            if (\is_object($io) && !$io->exists(DealPricing::IO_FUNCTION)) {
                // liefert die Preise nur für Sitzungen mit freigeschaltetem Deal (Seiten selbst bleiben cachebar)
                $io->register(
                    DealPricing::IO_FUNCTION,
                    static fn(mixed $ids = []): array => DealPricing::create()->ioPrices($ids)
                );
            }
            if (\is_object($io) && !$io->exists(DealService::IO_FUNCTION)) {
                $io->register(
                    DealService::IO_FUNCTION,
                    static fn(mixed $ids = [], mixed $code = '', mixed $token = ''): array
                        => DealService::create()->addToCart($ids, $code, $token)
                );
            }
            if (\is_object($io) && !$io->exists(DealService::TOKEN_FUNCTION)) {
                // Token der Sitzung für den Warenkorb-Button (gecachte Seiten enthalten kein Token)
                $io->register(DealService::TOKEN_FUNCTION, static fn(): array => DealService::ioToken());
            }
        });

        // Newsletter-Deals: geheime Links als eigene Routen vor dem Core-Catch-all registrieren
        $dispatcher->hookInto(\HOOK_ROUTER_PRE_DISPATCH, static function (array $args): void {
            // Deal-Code im Kupon-Feld abfangen, bevor der Core ihn als unbekannten Kupon ablehnt
            DealPricing::create()->handleCouponField();
            $router = $args['router'] ?? null;
            if ($router instanceof Router) {
                DealPageRoute::register($router);
            }
        });
        // Deal-Preise: Positionspreis im Warenkorb (bei jeder Neuberechnung) und Hinweis an der Position
        $dispatcher->hookInto(\HOOK_SETZTE_POSITIONSPREISE, static function (array $args): void {
            if (isset($args['position']) && \is_object($args['position'])) {
                DealPricing::create()->applyToCartItem($args['position']);
            }
        });
        $dispatcher->hookInto(\HOOK_SET_POSITION_PRICES_END, static function (array $args): void {
            if (isset($args['position']) && \is_object($args['position'])) {
                DealPricing::create()->noteCartItem($args['position']);
            }
        });
        $dispatcher->hookInto(\HOOK_LETZTERINCLUDE_INC, function (): void {
            $this->assignDealPrices();
        });
        $dispatcher->hookInto(\HOOK_PRODUCTFILTER_INIT_STATES, static function (array $args): void {
            DealPageRoute::onInitStates($args);
        });
        $dispatcher->hookInto(\HOOK_FILTER_PAGE, function (): void {
            $this->assignNewsletterDeal();
        });
        $dispatcher->hookInto(\HOOK_FILTER_ENDE, static function (): void {
            $page = DealPageRoute::current();
            if ($page === null) {
                return;
            }
            $view = Shop::Smarty()->getTemplateVars('spNlDeal');
            if (\is_array($view)) {
                Shop::Smarty()->assign('meta_title', $view['title'])
                    ->assign('meta_description', \mb_substr(\preg_replace('/\s+/', ' ', $view['text']) ?? '', 0, 160))
                    ->assign('meta_keywords', '');
            }
        });

        // Artikel-Picker im OPC-Editor (Admin-IO prüft Login und CSRF-Token selbst)
        $dispatcher->hookInto(\HOOK_IO_HANDLE_REQUEST_ADMIN, function (array $args): void {
            $io = $args['io'] ?? null;
            if (\is_object($io) && !$io->exists(DealService::ADMIN_SEARCH_FUNCTION)) {
                $io->register(
                    DealService::ADMIN_SEARCH_FUNCTION,
                    static fn(mixed $query = ''): array => DealService::create()->searchProducts($query)
                );
            }
            if (\is_object($io) && !$io->exists(DealService::ADMIN_COUPON_FUNCTION)) {
                $io->register(
                    DealService::ADMIN_COUPON_FUNCTION,
                    static fn(mixed $query = '', mixed $exact = false): array
                        => DealService::create()->searchCoupons($query, $exact)
                );
            }
            if (\is_object($io) && !$io->exists(DealService::ADMIN_CATEGORY_FUNCTION)) {
                $io->register(
                    DealService::ADMIN_CATEGORY_FUNCTION,
                    static fn(mixed $query = ''): array => DealService::create()->searchCategories($query)
                );
            }
        });
    }

    /**
     * Countdowns mit Freigabe "Artikelseite" (immer oder nur bei aktivem Sonderpreis) an Smarty geben.
     */
    public function assignProductCountdowns(?object $artikel): void
    {
        $smarty = Shop::Smarty();
        $smarty->assign('spProductCountdowns', [])
            ->assign('spCommonUrl', $this->getCommonUrl())
            ->assign('spCommonPath', $this->getCommonPath())
            ->assign('spVersion', $this->getPlugin()->getMeta()->getVersion());

        if ($artikel === null) {
            return;
        }
        $hasSpecialPrice = !empty($artikel->Preise->Sonderpreis_aktiv);
        $smarty->assign('spProductCountdowns', CountdownService::create()->forProductPage($hasSpecialPrice));
    }

    /**
     * Newsletter-Deal-Seite: Kopfdaten an Smarty geben, Seite für Suchmaschinen sperren und den Core-Hinweis
     * "keine Artikel" bei beendeten Aktionen unterdrücken (die Seite zeigt dort einen eigenen Hinweis).
     */
    public function assignNewsletterDeal(): void
    {
        $page = DealPageRoute::current();
        if ($page === null) {
            return;
        }
        $view = DealPageService::create()->frontendView($page, DealPageRoute::isAdmin());
        Shop::Smarty()->assign('spNlDeal', $view)
            ->assign('robotsContent', 'noindex, nofollow')
            ->assign('spCommonUrl', $this->getCommonUrl())
            ->assign('spCommonPath', $this->getCommonPath())
            ->assign('spVersion', $this->getPlugin()->getMeta()->getVersion());
        if ($view['expired']) {
            Shop::Container()->getAlertService()->removeAlertByKey('noFilterResults');
        }
    }

    /**
     * Platzhalter für Deal-Preis-Hinweise in productdetails/price.tpl (Liste, Artikelseite). Das HTML hängt nur von den
     * laufenden Deals ab, nicht von der Sitzung: Der Live-Shop liegt hinter einem LiteSpeed-Seitencache, der eine einmal
     * gerenderte Seite allen Besuchern ausliefert. Die Preise selbst lädt newsletter-deal.js per IO für freigeschaltete
     * Sitzungen nach.
     */
    public function assignDealPrices(): void
    {
        $ids = DealPricing::create()->runningProductIDs();
        if ($ids === []) {
            return;
        }
        $base    = \rtrim($this->getPlugin()->getPaths()->getFrontendURL(), '/');
        $version = $this->getPlugin()->getMeta()->getVersion();
        Shop::Smarty()->assign('spNlDealIDs', $ids)
            ->assign('spNlDealCss', $base . '/css/newsletter-deal.css?v=' . $version)
            ->assign('spNlDealJs', $base . '/js/newsletter-deal.js?v=' . $version);
    }

    public function getCommonUrl(): string
    {
        return \rtrim($this->getPlugin()->getPaths()->getBaseURL(), '/') . '/Portlets/Common/';
    }

    public function getCommonPath(): string
    {
        return \rtrim($this->getPlugin()->getPaths()->getBasePath(), '/') . '/Portlets/Common/';
    }

    /**
     * @inheritdoc
     */
    public function renderAdminMenuTab(string $tabName, int $menuID, JTLSmarty $smarty): string
    {
        if ($tabName === 'Newsletter-Deals') {
            return (new DealPageAdmin($this->getDB(), $this->getPlugin(), $menuID))->render($smarty);
        }

        $controller         = new ModelBackendController(
            $this->getDB(),
            $this->getCache(),
            Shop::Container()->getAlertService(),
            Shop::Container()->getAdminAccount(),
            Shop::Container()->getGetText()
        );
        $controller->menuID = $menuID;
        $controller->plugin = $this->getPlugin();

        $request  = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        $response = $controller->getResponse($request, [], $smarty);

        if (\count($response->getHeader('location')) > 0) {
            \header('Location:' . first($response->getHeader('location')));
            exit();
        }

        return (string)$response->getBody();
    }
}
