<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\DB\DbInterface;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Plugin\PluginInterface;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use stdClass;

/**
 * Admin-Tab "Newsletter-Deals": Liste und Formular der versteckten Aktionsseiten.
 *
 * Der PluginController rendert bei jedem Aufruf alle Tabs; dieser Tab reagiert deshalb nur auf eigene Parameter
 * (GET "nld"/"nld_id", POST "nld_action") und nutzt keine Namen des Countdown-Tabs (action, id, save-model …).
 */
final class DealPageAdmin
{
    private const FIELDS_TEXT = ['name' => 100, 'title' => 150, 'title_en' => 150];

    public function __construct(
        private readonly DbInterface $db,
        private readonly PluginInterface $plugin,
        private readonly int $menuID
    ) {
    }

    public function render(JTLSmarty $smarty): string
    {
        $error = '';
        $form  = null;
        if (Request::postVar('nld_action') !== null) {
            [$error, $form] = $this->handlePost();
        }

        $view   = Request::getVar('nld', '');
        $editID = Request::getInt('nld_id');
        if ($form === null && $view === 'edit') {
            $form = $editID > 0 ? $this->service()->find($editID) : null;
        }
        if ($form === null && $view === 'new') {
            $form = $this->emptyRow();
        }

        $smarty->assign('nldPages', $form === null ? $this->listRows() : [])
            ->assign('nldForm', $form === null ? null : $this->formView($form))
            ->assign('nldError', $error)
            ->assign('nldFlash', $this->takeFlash())
            ->assign('nldMenuID', $this->menuID)
            ->assign('nldBaseUrl', $this->tabUrl())
            ->assign('nldShopUrl', \rtrim(Shop::getURL(), '/') . '/')
            ->assign('nldPrefix', DealPageService::SLUG_PREFIX)
            ->assign('nldRuleTpl', $this->plugin->getPaths()->getAdminPath() . 'templates/newsletter_deal_rule.tpl')
            ->assign('nldPickerCore', \rtrim($this->plugin->getPaths()->getBasePath(), '/') . '/portlet_input_types/picker-core.tpl')
            ->assign('nldNow', \date('d.m.Y H:i'));

        return $smarty->fetch($this->plugin->getPaths()->getAdminPath() . 'templates/newsletter_deals.tpl');
    }

    /**
     * @return array{0: string, 1: ?stdClass} Fehlermeldung und Formularwerte (bei Fehlern erneut anzeigen)
     */
    private function handlePost(): array
    {
        if (!Form::validateToken()) {
            return ['Die Sitzung ist abgelaufen (CSRF-Token ungültig). Bitte erneut versuchen.', null];
        }
        $action = (string)Request::postVar('nld_action');
        $id     = Request::postInt('nld_id');

        if ($action === 'delete') {
            if ($id > 0) {
                $this->db->delete(DealPageService::TABLE, 'id', $id);
                $this->db->delete(DealPricing::TABLE_RULES, 'deal_id', $id);
                DealCache::purge();
                $this->flash('Deal-Seite gelöscht.');
            }
            $this->redirect([]);
        }
        if ($action === 'toggle') {
            $row = $this->service()->find($id);
            if ($row !== null) {
                $this->db->update(DealPageService::TABLE, 'id', $id, (object)['active' => (int)$row->active === 1 ? 0 : 1]);
                DealCache::purge();
                $this->flash((int)$row->active === 1 ? 'Deal-Seite deaktiviert.' : 'Deal-Seite aktiviert.');
            }
            $this->redirect([]);
        }
        if ($action !== 'save' && $action !== 'save_continue') {
            return ['', null];
        }

        [$row, $error] = $this->rowFromPost($id);
        if ($error !== '') {
            return [$error, $row];
        }
        $data  = clone $row;
        $rules = $data->rules;
        unset($data->id, $data->rules);
        // NiceDB macht aus null einen Leerstring (ungültig für DATETIME); '_DBNULL_' schreibt echtes NULL
        $data->valid_from  ??= '_DBNULL_';
        $data->valid_until ??= '_DBNULL_';
        $data->public_from ??= '_DBNULL_';
        if ($id > 0 && $this->service()->find($id) !== null) {
            $this->db->update(DealPageService::TABLE, 'id', $id, $data);
        } else {
            $id = $this->db->insert(DealPageService::TABLE, $data);
        }
        $this->saveRules($id, $rules);
        // gecachte Seiten (Platzhalter, Hero-Slide) sofort neu aufbauen lassen
        DealCache::purge();
        $this->flash('Deal-Seite gespeichert.');
        $this->redirect($action === 'save_continue' ? ['nld' => 'edit', 'nld_id' => $id] : []);
    }

    /**
     * @return array{0: stdClass, 1: string}
     */
    private function rowFromPost(int $id): array
    {
        $row     = $this->emptyRow();
        $row->id = $id;
        foreach (self::FIELDS_TEXT as $field => $max) {
            // Rohwerte (filterXSS würde Anführungszeichen entfernen); die Ausgabe wird überall escaped
            $row->$field = \mb_substr(\trim((string)($_POST['nld_' . $field] ?? '')), 0, $max);
        }
        $row->text    = \mb_substr(\trim((string)($_POST['nld_text'] ?? '')), 0, 2000);
        $row->text_en = \mb_substr(\trim((string)($_POST['nld_text_en'] ?? '')), 0, 2000);
        $row->slug    = DealPageService::normalizeSlug((string)($_POST['nld_slug'] ?? ''));
        $row->slug_en = DealPageService::normalizeSlug((string)($_POST['nld_slug_en'] ?? ''));
        $row->coupon  = \mb_substr(\trim((string)($_POST['nld_coupon'] ?? '')), 0, 255);
        $row->products = \implode(';', DealPageService::parseIds((string)($_POST['nld_products'] ?? '')));
        $row->code     = DealPricing::normalizeCode((string)($_POST['nld_code'] ?? ''));
        $row->display  = DealPricing::displayMode((string)($_POST['nld_display'] ?? ''));
        $row->active   = isset($_POST['nld_active']) ? 1 : 0;

        $errors     = [];
        $row->rules = $this->rulesFromPost($errors);
        foreach (['valid_from' => 'Start', 'valid_until' => 'Ende', 'public_from' => 'Für alle Kunden ab'] as $field => $label) {
            $raw = \str_replace('T', ' ', \trim((string)($_POST['nld_' . $field] ?? '')));
            $ts  = $raw !== '' ? \strtotime($raw) : null;
            if ($ts === false) {
                $errors[] = $label . ': ungültiges Datum.';
                $ts       = null;
            }
            $row->$field = $ts !== null ? \date('Y-m-d H:i:s', $ts) : null;
        }
        if ($row->name === '') {
            $errors[] = 'Bitte einen internen Namen angeben.';
        }
        if ($row->title === '') {
            $errors[] = 'Bitte eine Überschrift angeben.';
        }
        if ($row->slug === '') {
            $row->slug = DealPageService::randomSlug();
        }
        if ($row->slug_en === '') {
            $row->slug_en = DealPageService::englishSlug($row->slug);
        }
        $slugProblem = $this->service()->slugProblem($row->slug, $id);
        if ($slugProblem !== '') {
            $errors[] = $slugProblem;
        }
        if ($row->slug_en === $row->slug) {
            $errors[] = 'Der englische Link muss sich vom deutschen unterscheiden.';
        } elseif (($problemEn = $this->service()->slugProblem($row->slug_en, $id)) !== '') {
            $errors[] = 'Englischer Link: ' . $problemEn;
        }
        $from  = DealPageService::timestamp($row->valid_from);
        $until = DealPageService::timestamp($row->valid_until);
        if ($from !== null && $until !== null && $until <= $from) {
            $errors[] = 'Das Ende muss nach dem Start liegen.';
        }
        if ($row->products === '' && $row->coupon === '' && $row->rules === []) {
            $errors[] = 'Bitte Deal-Preise anlegen, Artikel auswählen oder einen Kupon mit Artikelbeschränkung wählen.';
        }
        if (($codeProblem = $this->codeProblem($row->code, $id)) !== '') {
            $errors[] = $codeProblem;
        }

        return [$row, \implode(' ', $errors)];
    }

    private function emptyRow(): stdClass
    {
        $slug = DealPageService::randomSlug();

        return (object)[
            'id'          => 0,
            'name'        => '',
            'slug'        => $slug,
            'slug_en'     => DealPageService::englishSlug($slug),
            'title'       => '',
            'title_en'    => '',
            'text'        => '',
            'text_en'     => '',
            'coupon'      => '',
            'code'        => '',
            'display'     => 'line',
            'products'    => '',
            'rules'       => [],
            'valid_from'  => null,
            'valid_until' => null,
            'public_from' => null,
            'active'      => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formView(stdClass $row): array
    {
        $input = static function (mixed $value): string {
            $ts = DealPageService::timestamp($value);

            return $ts === null ? '' : \date('Y-m-d\TH:i', $ts);
        };

        return [
            'id'          => (int)$row->id,
            'name'        => (string)$row->name,
            'slug'        => (string)$row->slug,
            'slug_en'     => (string)($row->slug_en ?? ''),
            'title'       => (string)$row->title,
            'title_en'    => (string)$row->title_en,
            'text'        => (string)($row->text ?? ''),
            'text_en'     => (string)($row->text_en ?? ''),
            'coupon'      => (string)$row->coupon,
            'code'        => (string)($row->code ?? ''),
            'display'     => DealPricing::displayMode((string)($row->display ?? '')),
            'products'    => (string)($row->products ?? ''),
            'rules'       => \array_map(static fn(array $rule): array => [
                'type'     => $rule['type'],
                'products' => \implode(';', $rule['products']),
                'partners' => \implode(';', $rule['partners']),
                'price'    => $rule['price'] > 0 ? \number_format((float)$rule['price'], 2, '.', '') : '',
            ], \is_array($row->rules ?? null) ? $row->rules : $this->loadRules((int)$row->id)),
            'valid_from'  => $input($row->valid_from ?? null),
            'valid_until' => $input($row->valid_until ?? null),
            'public_from' => $input($row->public_from ?? null),
            'active'      => (int)$row->active === 1,
            'url'         => (int)$row->id > 0 ? DealPageService::url((string)$row->slug, $this->langID('ger')) : '',
            'previewUrl'  => (int)$row->id > 0 ? DealPageService::previewUrl((string)$row->slug, $this->langID('ger')) : '',
            'urlEn'       => (int)$row->id > 0 ? DealPageService::url((string)$row->slug_en, $this->langID('eng')) : '',
            'previewUrlEn' => (int)$row->id > 0 ? DealPageService::previewUrl((string)$row->slug_en, $this->langID('eng')) : '',
            'hasEnglish'  => $this->langID('eng') !== null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listRows(): array
    {
        $labels = [
            'active'   => ['läuft', 'success'],
            'inactive' => ['deaktiviert', 'secondary'],
            'upcoming' => ['geplant', 'info'],
            'expired'  => ['beendet', 'warning'],
        ];
        $format = static function (mixed $value): string {
            $ts = DealPageService::timestamp($value);

            return $ts === null ? '' : \date('d.m.Y H:i', $ts);
        };

        $ruleCounts = [];
        try {
            foreach ($this->db->getObjects(
                'SELECT deal_id, COUNT(*) AS cnt FROM ' . DealPricing::TABLE_RULES . ' GROUP BY deal_id'
            ) as $count) {
                $ruleCounts[(int)$count->deal_id] = (int)$count->cnt;
            }
        } catch (\Throwable) {
        }
        $langDe = $this->langID('ger');
        $langEn = $this->langID('eng');

        return \array_map(static function (stdClass $row) use ($labels, $format, $langDe, $langEn, $ruleCounts): array {
            $status = DealPageService::status($row);
            $from   = $format($row->valid_from ?? null);
            $until  = $format($row->valid_until ?? null);

            return [
                'id'          => (int)$row->id,
                'name'        => (string)$row->name,
                'title'       => (string)$row->title,
                'url'         => DealPageService::url((string)$row->slug, $langDe),
                'previewUrl'  => DealPageService::previewUrl((string)$row->slug, $langDe),
                'urlEn'       => DealPageService::url((string)$row->slug_en, $langEn),
                'previewUrlEn' => DealPageService::previewUrl((string)$row->slug_en, $langEn),
                'coupon'      => (string)$row->coupon,
                'count'       => \count(DealPageService::parseIds($row->products ?? '')),
                'rules'       => $ruleCounts[(int)$row->id] ?? 0,
                'code'        => (string)($row->code ?? ''),
                'period'      => match (true) {
                    $from !== '' && $until !== '' => $from . ' – ' . $until,
                    $from !== ''                  => 'ab ' . $from,
                    $until !== ''                 => 'bis ' . $until,
                    default                       => 'unbegrenzt',
                },
                'active'      => (int)$row->active === 1,
                'public'      => DealPageService::isPublic($row),
                'publicFrom'  => $format($row->public_from ?? null),
                'statusLabel' => $labels[$status][0],
                'statusClass' => $labels[$status][1],
            ];
        }, $this->service()->all());
    }

    /**
     * Deal-Preise aus dem Formular (nld_rules[i][type|products|partners|price]). Komplett leere Zeilen entfallen,
     * unvollständige erzeugen eine Fehlermeldung (die Zeile bleibt im Formular stehen).
     *
     * @param string[] $errors
     * @return array<int, array<string, mixed>>
     */
    private function rulesFromPost(array &$errors): array
    {
        $rules = [];
        $posted = \is_array($_POST['nld_rules'] ?? null) ? $_POST['nld_rules'] : [];
        foreach (\array_values($posted) as $i => $input) {
            if (!\is_array($input)) {
                continue;
            }
            $priceRaw = \str_replace(',', '.', \trim((string)($input['price'] ?? '')));
            $rule     = DealPricing::ruleFromRow((object)[
                'type'     => (string)($input['type'] ?? 'price'),
                'products' => (string)($input['products'] ?? ''),
                'partners' => (string)($input['partners'] ?? ''),
                'price'    => \is_numeric($priceRaw) ? (float)$priceRaw : 0,
            ]);
            if ($rule['type'] !== 'set') {
                $rule['partners'] = [];
            }
            if ($rule['products'] === [] && $rule['partners'] === [] && $priceRaw === '') {
                continue;
            }
            if (!DealPricing::isUsable($rule)) {
                $errors[] = \sprintf(
                    'Deal-Preis %d: bitte Artikel%s und einen Preis über 0 angeben.',
                    $i + 1,
                    $rule['type'] === 'set' ? ', Set-Partner' : ''
                );
            }
            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * Alle gespeicherten Regeln einer Seite (auch unvollständige) für das Formular.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadRules(int $dealID): array
    {
        if ($dealID <= 0) {
            return [];
        }
        try {
            return \array_map(
                static fn(stdClass $row): array => DealPricing::ruleFromRow($row),
                $this->db->getObjects(
                    'SELECT * FROM ' . DealPricing::TABLE_RULES . ' WHERE deal_id = :id ORDER BY sort, id',
                    ['id' => $dealID]
                )
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     */
    private function saveRules(int $dealID, array $rules): void
    {
        if ($dealID <= 0) {
            return;
        }
        $this->db->delete(DealPricing::TABLE_RULES, 'deal_id', $dealID);
        foreach (\array_values($rules) as $sort => $rule) {
            $this->db->insert(DealPricing::TABLE_RULES, (object)[
                'deal_id'  => $dealID,
                'type'     => $rule['type'],
                'products' => \implode(';', $rule['products']),
                'partners' => \implode(';', $rule['partners']),
                'price'    => \number_format((float)$rule['price'], 2, '.', ''),
                'sort'     => $sort,
            ]);
        }
    }

    /**
     * Deal-Code: optional, eindeutig unter den Deal-Seiten und kein bestehender JTL-Kupon-Code.
     */
    private function codeProblem(string $code, int $id): string
    {
        if ($code === '') {
            return '';
        }
        if (\mb_strlen($code) < 4) {
            return 'Der Deal-Code braucht mindestens 4 Zeichen.';
        }
        try {
            if ($this->db->getSingleObject(
                'SELECT id FROM ' . DealPageService::TABLE . ' WHERE UPPER(code) = :code AND id != :id',
                ['code' => $code, 'id' => $id]
            ) !== null) {
                return 'Der Deal-Code wird bereits von einer anderen Deal-Seite verwendet.';
            }
            if ($this->db->getSingleObject('SELECT kKupon FROM tkupon WHERE UPPER(cCode) = :code LIMIT 1', ['code' => $code]) !== null) {
                return 'Der Deal-Code ist bereits ein JTL-Kupon-Code – bitte einen anderen wählen.';
            }
        } catch (\Throwable) {
        }

        return '';
    }

    /**
     * Sprach-ID für die Links (null = Sprache nicht im Shop, Link nutzt die Standard-URL).
     */
    private function langID(string $iso): ?int
    {
        static $ids = null;
        $ids ??= DealPageRoute::languageIDs();

        return $ids[$iso] ?? null;
    }

    private function service(): DealPageService
    {
        return new DealPageService($this->db);
    }

    /**
     * @param array<string, int|string> $params
     */
    private function tabUrl(array $params = []): string
    {
        $url = $this->plugin->getPaths()->getBackendURL();

        return $url . (\str_contains($url, '?') ? '&' : '?')
            . \http_build_query(['kPluginAdminMenu' => $this->menuID] + $params);
    }

    /**
     * @param array<string, int|string> $params
     */
    private function redirect(array $params): never
    {
        \header('Location: ' . $this->tabUrl($params));
        exit;
    }

    /**
     * Erfolgsmeldung über die Weiterleitung hinweg (eigener Session-Schlüssel, Anzeige im Tab selbst).
     */
    private function flash(string $message): void
    {
        $_SESSION['startseitePlusNldFlash'] = $message;
    }

    private function takeFlash(): string
    {
        $message = (string)($_SESSION['startseitePlusNldFlash'] ?? '');
        unset($_SESSION['startseitePlusNldFlash']);

        return $message;
    }
}
