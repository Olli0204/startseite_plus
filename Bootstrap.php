<?php

declare(strict_types=1);

namespace Plugin\startseite_plus;

use JTL\Events\Dispatcher;
use JTL\Plugin\Bootstrapper;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Laminas\Diactoros\ServerRequestFactory;
use Plugin\startseite_plus\Countdown\CountdownService;

use function Functional\first;

/**
 * Startseite Plus: OPC-Portlets plus zentrale Countdown-Verwaltung.
 * Der Bootstrap stellt Countdowns für Artikeldetailseiten bereit und rendert den Admin-Tab.
 */
class Bootstrap extends Bootstrapper
{
    public function boot(Dispatcher $dispatcher): void
    {
        parent::boot($dispatcher);

        $dispatcher->hookInto(\HOOK_ARTIKEL_PAGE, function (array $args): void {
            $this->assignProductCountdowns($args['oArtikel'] ?? null);
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
