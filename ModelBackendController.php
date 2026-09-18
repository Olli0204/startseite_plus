<?php

declare(strict_types=1);

namespace Plugin\startseite_plus;

use JTL\Helpers\Request;
use JTL\Model\DataModelInterface;
use JTL\Plugin\PluginInterface;
use JTL\Router\Controller\Backend\GenericModelController;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Models\Countdown;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin-Tab "Countdowns": Liste (JTL model_list) und eigenes Bearbeitungsformular.
 * Nutzt den generischen JTL-Model-Controller für Speichern, Löschen und PRG-Redirects.
 */
class ModelBackendController extends GenericModelController
{
    public int $menuID = 0;

    public PluginInterface $plugin;

    /**
     * Formularwerte normalisieren: datetime-local -> "Y-m-d H:i:s", Checkbox -> 0/1, Auswahlwerte absichern.
     */
    public function updateFromPost(DataModelInterface $model, array $post): bool
    {
        $post['name']   = \mb_substr(\trim((string)($post['name'] ?? '')), 0, 100);
        $post['active'] = isset($post['active']) ? 1 : 0;

        $until = \str_replace('T', ' ', \trim((string)($post['until'] ?? '')));
        $ts    = $until !== '' ? \strtotime($until) : false;
        if ($post['name'] === '' || $ts === false) {
            $_SESSION['modelErrorMsg'] = \__('Bitte Name und einen gültigen Endzeitpunkt angeben.');

            return false;
        }
        $post['until'] = \date('Y-m-d H:i:s', $ts);

        foreach (['style' => CountdownService::STYLES, 'expired_mode' => CountdownService::MODES, 'product_page' => CountdownService::PRODUCT_PAGE] as $field => $allowed) {
            if (!\in_array($post[$field] ?? '', $allowed, true)) {
                $post[$field] = $allowed[0];
            }
        }
        foreach (['label', 'label_en', 'expired_text', 'expired_text_en'] as $field) {
            // filterXSS entfernt Anführungszeichen; Rohwert übernehmen, Ausgabe wird escaped
            $post[$field] = \mb_substr(\trim((string)($_POST[$field] ?? '')), 0, 255);
        }

        return parent::updateFromPost($model, $post);
    }

    public function getResponse(ServerRequestInterface $request, array $args, JTLSmarty $smarty): ResponseInterface
    {
        $this->smarty        = $smarty;
        $this->route         = \str_replace(Shop::getAdminURL(), '', $this->plugin->getPaths()->getBackendURL());
        $this->modelClass    = Countdown::class;
        $this->adminBaseFile = \ltrim($this->route, '/');

        $tab = Request::getVar('action', 'overview');
        // Nach "Speichern und weiter" verliert die PRG-Weiterleitung ?action=detail; Schritt aus der Session lesen
        if ($tab === 'overview' && ($_SESSION['step'] ?? '') === 'detail') {
            $tab = 'detail';
        }

        if ($tab === 'overview') {
            $smarty->assign('models', Countdown::loadAll($this->getDB(), [], []));
        } else {
            $itemId = Request::getInt('id') ?: (int)($_SESSION['modelid'] ?? 0);
            $item   = $itemId > 0 ? Countdown::loadByAttributes(['id' => $itemId], $this->getDB()) : new Countdown($this->getDB());
            $smarty->assign('item', $item)
                ->assign('untilInput', $this->toInputValue((string)($item->until ?? '')))
                ->assign('styles', ['boxes' => \__('Kästchen'), 'inline' => \__('Textzeile')])
                ->assign('modes', ['hide' => \__('Ausblenden'), 'text' => \__('Hinweistext anzeigen'), 'keep' => \__('Ohne Countdown weiter anzeigen')])
                ->assign('productPages', ['none' => \__('Nein'), 'sale' => \__('Nur bei aktivem Sonderpreis'), 'all' => \__('Immer')]);
        }

        $smarty->assign('route', $this->route)
            ->assign('step', $tab)
            ->assign('tab', $tab)
            ->assign('action', $this->plugin->getPaths()->getBackendURL())
            ->assign('defaultTabbertab', $this->menuID)
            ->assign('now', \date('Y-m-d H:i:s'));

        return $this->handle(__DIR__ . '/adminmenu/templates/countdowns.tpl');
    }

    /**
     * "Y-m-d H:i:s" -> Wert für <input type="datetime-local">
     */
    private function toInputValue(string $dbValue): string
    {
        $ts = $dbValue !== '' ? \strtotime($dbValue) : false;

        return $ts === false ? '' : \date('Y-m-d\TH:i', $ts);
    }
}
