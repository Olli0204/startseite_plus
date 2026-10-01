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
            $io = $args['io'] ?? null;
            if (\is_object($io) && !$io->exists(DealService::IO_FUNCTION)) {
                $io->register(
                    DealService::IO_FUNCTION,
                    static fn(mixed $ids = [], mixed $code = '', mixed $token = ''): array
                        => DealService::create()->addToCart($ids, $code, $token)
                );
            }
        });

        // Newsletter-Deals: geheime Links als eigene Routen vor dem Core-Catch-all registrieren
        $dispatcher->hookInto(\HOOK_ROUTER_PRE_DISPATCH, static function (array $args): void {
            $router = $args['router'] ?? null;
            if ($router instanceof Router) {
                DealPageRoute::register($router);
            }
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
