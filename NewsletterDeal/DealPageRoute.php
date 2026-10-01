<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\Filter\States\DummyState;
use JTL\Router\Controller\DefaultController;
use JTL\Router\Controller\ProductListController;
use JTL\Router\Router;
use JTL\Shop;
use JTL\Shopsetting;
use JTL\Smarty\JTLSmarty;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

/**
 * Routing der Newsletter-Deal-Seiten.
 *
 * Ablauf: HOOK_ROUTER_PRE_DISPATCH registriert pro Seite eine Route "/<slug>" (plus angehängte SEO-Filter wie
 * "_s2" für Seite 2 oder "__…"/"::…" für Merkmal- und Herstellerfilter). Der Handler lässt den Core-DefaultController
 * die Filter aus URL und GET-Parametern lesen, setzt danach den eigenen Basis-Zustand DealPageState und rendert die
 * Liste mit dem Core-ProductListController – Filter, Sortierung, Seitenaufteilung und Mobil-Filter funktionieren
 * dadurch wie auf jeder Kategorieseite.
 */
final class DealPageRoute
{
    private static ?stdClass $current = null;

    private static bool $isAdmin = false;

    /**
     * Die Deal-Seite, die gerade gerendert wird (null auf allen anderen Seiten).
     */
    public static function current(): ?stdClass
    {
        return self::$current;
    }

    public static function isAdmin(): bool
    {
        return self::$isAdmin;
    }

    public static function register(Router $router): void
    {
        foreach (DealPageService::create()->routes() as $id => $slug) {
            // Slug besteht nur aus [a-z0-9-] (isValidSlug), kein Escaping nötig. Keine Capture-Groups (FastRoute).
            $router->addRoute(
                '/{spnld:' . $slug . '(?:(?:' . \SEP_SEITE . '|' . \SEP_KAT . '|' . \SEP_MERKMAL . ')[^/]*)?}',
                static fn(ServerRequestInterface $request, array $args, JTLSmarty $smarty): ResponseInterface
                    => self::handle($id, $request, $args, $smarty),
                'startseite_plus_nld_' . $id
            );
        }
    }

    /**
     * Während initStates() steht noch der leere Core-Basiszustand. ProductFilter::validate() leitet bei aktivem
     * Hersteller- oder Kategoriefilter ohne Basis auf die Hersteller-/Kategorieseite um – außer der Basis ist ein
     * initialisierter DummyState. Deshalb hier initialisieren; handle() ersetzt ihn danach durch DealPageState.
     *
     * @param array<string, mixed> $args HOOK_PRODUCTFILTER_INIT_STATES
     */
    public static function onInitStates(array $args): void
    {
        if (self::$current === null) {
            return;
        }
        $productFilter = $args['productFilter'] ?? null;
        if (!\is_object($productFilter) || !\method_exists($productFilter, 'getBaseState')) {
            return;
        }
        $base = $productFilter->getBaseState();
        if ($base instanceof DummyState && !$base->isInitialized()) {
            $base->init(1);
        }
    }

    /**
     * @param array<string, string> $args
     */
    private static function handle(int $id, ServerRequestInterface $request, array $args, JTLSmarty $smarty): ResponseInterface
    {
        $db      = Shop::Container()->getDB();
        $cache   = Shop::Container()->getCache();
        $alerts  = Shop::Container()->getAlertService();
        $state   = Shop::getRouter()->getState();
        $config  = Shopsetting::getInstance($db, $cache)->getAll();
        $slug    = (string)($args['spnld'] ?? '');
        $service = DealPageService::create();
        $page    = $service->find($id);
        $default = new DefaultController($db, $cache, $state, $config, $alerts);

        try {
            self::$isAdmin = Shop::isAdmin(true);
        } catch (\Throwable) {
            self::$isAdmin = false;
        }
        if ($page === null || !DealPageService::isVisible($page, self::$isAdmin)) {
            // wie eine unbekannte URL: normale 404-Seite
            return $default->getResponse($request, ['slug' => $slug], $smarty);
        }

        self::$current = $page;
        // liest Seiten-/Filter-Teile aus dem Slug und den GET-Parametern und ruft ProductFilter::initStates() auf
        // (ungültige Filter-Slugs setzen is404 – dann liefert ProductListController::init() false → 404)
        $default->getStateFromSlug(['slug' => $slug]);
        $state->pageType = \PAGE_ARTIKELLISTE;

        $isExpired     = DealPageService::status($page) === 'expired';
        $productFilter = Shop::getProductFilter();
        $productFilter->setBaseState(DealPageState::create(
            $productFilter,
            (int)$page->id,
            (string)$page->slug,
            (string)$page->title,
            $isExpired ? [] : $service->listProductIDs($page)
        ));

        $controller = new ProductListController($db, $cache, $state, $config, $alerts);
        if ($controller->init() === false) {
            return $controller->notFoundResponse($request, $args, $smarty);
        }

        return $controller->getResponse($request, $args, $smarty);
    }
}
