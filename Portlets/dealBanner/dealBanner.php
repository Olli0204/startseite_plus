<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Portlets\dealBanner;

use JTL\OPC\Portlet;
use JTL\OPC\PortletInstance;
use Plugin\startseite_plus\Countdown\CountdownService;
use Plugin\startseite_plus\Deal\DealService;
use Plugin\startseite_plus\Portlets\Common\PortletHelper;

/**
 * Deal-Banner: bewirbt einen JTL-Kupon (z. B. Bundle-Rabatt in einer Kategorie).
 * Rabatt, Gültigkeit und – falls nicht im Portlet angegeben – die Artikel kommen aus dem Kupon.
 * "In den Warenkorb" legt alle Artikel ab und löst den Code automatisch ein (IO-Funktion, siehe Bootstrap).
 * Ist der Kupon inaktiv, abgelaufen oder aufgebraucht, wird der Banner im Shop nicht ausgegeben.
 */
class dealBanner extends Portlet
{
    use PortletHelper;

    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('fas fa-tags');
    }

    /**
     * @inheritdoc
     */
    public function initInstance(PortletInstance $instance, bool $isFrontend = true): void
    {
        parent::initInstance($instance, $isFrontend);
        // 2.3.x: Artikelnummern als Text ("products") -> Artikel-Picker ("product-ids")
        $numbers = $this->getString($instance, 'products');
        if ($numbers === '' || $this->getString($instance, 'product-ids') !== '') {
            return;
        }
        try {
            $ids = DealService::create()->idsForNumbers(DealService::parseNumbers($numbers, null));
        } catch (\Throwable) {
            return;
        }
        if ($ids !== []) {
            $instance->setProperty('product-ids', \implode(';', $ids));
            $instance->setProperty('products', '');
        }
    }

    /**
     * Anzeige-Daten inkl. fertiger Texte; null, solange kein Code gepflegt ist.
     *
     * @return array<string, mixed>|null
     */
    public function getDeal(PortletInstance $instance): ?array
    {
        $code = $this->getString($instance, 'coupon-code');
        if ($code === '') {
            return null;
        }
        $deal = DealService::create()->view(
            $code,
            $this->getString($instance, 'products'),
            $this->getString($instance, 'product-ids'),
            [
                'kicker'       => $this->getString($instance, 'kicker'),
                'title'        => $this->getString($instance, 'title'),
                'text'         => $this->getString($instance, 'text'),
                'btnLabel'     => $this->getString($instance, 'btn-label'),
                'showButton'   => $this->isTrue($instance, 'show-button'),
                'showPrices'   => $this->isTrue($instance, 'show-prices'),
                'showValidity' => $this->isTrue($instance, 'show-validity'),
            ]
        );
        if (!empty($deal['found'])) {
            $deal['countdown'] = $this->getCountdown($instance, $deal['validUntil']);
        }

        return $deal;
    }

    /**
     * Countdown bis Kupon-Ablauf oder aus der zentralen Verwaltung; null = kein Countdown.
     *
     * @return array<string, mixed>|null
     */
    private function getCountdown(PortletInstance $instance, ?int $couponUntil): ?array
    {
        $selection = $this->getString($instance, 'countdown-id');
        if ($selection === '') {
            return null;
        }
        if ($selection === 'coupon') {
            if ($couponUntil === null) {
                return null;
            }

            return CountdownService::buildView(
                0,
                '',
                $couponUntil,
                $this->getString($instance, 'countdown-label'),
                $this->getKey($instance, 'countdown-style', 'inline'),
                'hide',
                ''
            );
        }
        try {
            $service = CountdownService::create();
            $row     = $service->find((int)$selection);

            return $row !== null ? $service->toView($row) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    protected function getSharedCssFiles(): array
    {
        return ['deal.css'];
    }

    /**
     * @return string[]
     */
    public function getExtraJsFiles(): array
    {
        $version = '?v=' . \rawurlencode($this->getPluginVersion());

        return [
            $this->getCommonUrl() . 'countdown.js' . $version,
            $this->getCommonUrl() . 'deal.js' . $version,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyDesc(): array
    {
        return [
            'coupon-code'     => [
                'type'    => 'startseite_plus.couponpicker',
                'label'   => \__('Kupon'),
                'default' => '',
                'width'   => 100,
                'desc'    => \__('Standardkupon aus JTL auswählen (fester Betrag oder Prozent). Rabatt, Gültigkeit und Artikel werden daraus gelesen. Ist der Kupon inaktiv, abgelaufen oder aufgebraucht, blendet sich der Banner im Shop aus.'),
            ],
            'product-ids'     => [
                'type'      => 'startseite_plus.productpicker',
                'label'     => \__('Artikel (optional)'),
                'default'   => '',
                'width'     => 100,
                'max'       => DealService::MAX_PRODUCTS,
                'emptyText' => \__('Keine Artikel ausgewählt – es werden die im Kupon hinterlegten Artikel angezeigt.'),
                'desc'      => \__('Artikel suchen und anklicken, höchstens 4; Reihenfolge per Ziehen ändern. Ohne Auswahl zeigt der Banner die Artikel, die im Kupon hinterlegt sind.'),
            ],
            'layout'          => $this->propSelect(
                \__('Layout'),
                [
                    'bundle' => \__('Bundle-Karte mit Artikeln und Preis'),
                    'strip'  => \__('Schlanke Coupon-Leiste'),
                    'ticket' => \__('Gutschein-Ticket'),
                ],
                'bundle',
                34
            ),
            'bg'              => $this->propBackground('tint', 33),
            'rounded'         => $this->propRounded('md', 33),
            'kicker'          => $this->propText(\__('Kleine Zeile über der Überschrift'), 34, 'Bundle-Deal'),
            'title'           => $this->propText(
                \__('Überschrift'),
                66,
                '',
                \__('Leer = automatisch, z. B. „Zusammen kaufen und 15 € extra sparen“.')
            ),
            'text'            => $this->propText(\__('Zusatztext (optional)'), 100, '', '', 'z. B. Gilt auf den bereits reduzierten Preis.'),
            'show-button'     => $this->propYesNo(
                \__('Button „In den Warenkorb“'),
                true,
                34,
                \__('Legt alle Artikel in den Warenkorb und löst den Code automatisch ein. Nur für Artikel ohne Variationsauswahl.')
            ),
            'btn-label'       => $this->propText(\__('Button-Text'), 33, '', \__('Leer = „Beide in den Warenkorb“')),
            'show-prices'     => $this->propYesNo(\__('Bundle-Preis anzeigen'), true, 33),
            'show-validity'   => $this->propYesNo(\__('Gültigkeit anzeigen'), true, 34),
            'width'           => $this->propWidthMode('container', 33),
            'accent-color'    => $this->propAccentColor(33),
            'countdown-id'    => $this->propSelect(
                \__('Countdown'),
                ['' => \__('Kein Countdown'), 'coupon' => \__('Bis zum Ablauf des Kupons')]
                    + $this->managedCountdownOptions(),
                '',
                100,
                \__('„Bis zum Ablauf des Kupons“ nutzt das Gültig-bis-Datum des Kupons. Weitere Countdowns pflegst du im Plugin-Tab „Countdowns“.')
            ),
            'countdown-label' => $this->propText(\__('Beschriftung (Kupon-Ablauf)'), 50, 'Endet in'),
            'countdown-style' => $this->propSelect(
                \__('Darstellung (Kupon-Ablauf)'),
                ['inline' => \__('Textzeile'), 'boxes' => \__('Kästchen')],
                'inline',
                50
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Countdown') => ['countdown-id', 'countdown-label', 'countdown-style'],
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
