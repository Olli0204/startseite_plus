<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\Filter\AbstractFilter;
use JTL\Filter\FilterInterface;
use JTL\Filter\ProductFilter;

/**
 * Basis-Zustand der Artikelliste einer Newsletter-Deal-Seite (wie Kategorie oder Hersteller bei normalen Listen).
 * Liefert die Bedingung "nur die gewählten Artikel" und den Slug, aus dem der Core alle Links der Liste baut
 * (Seiten, Sortierung, Filter). Er wird nicht registriert und erscheint daher nicht als aktiver Filter.
 */
final class DealPageState extends AbstractFilter
{
    /** @var int[] */
    private array $productIDs = [];

    public function __construct(ProductFilter $productFilter)
    {
        parent::__construct($productFilter);
        $this->setIsCustom(false)
            ->setUrlParam('spnld')
            ->setUrlParamSEO(null);
    }

    /**
     * @param int[] $productIDs leer = keine Artikel (z. B. abgelaufene Aktion)
     */
    public static function create(ProductFilter $productFilter, int $pageID, string $slug, string $name, array $productIDs): self
    {
        $state             = new self($productFilter);
        $state->productIDs = \array_values(\array_filter(\array_map('intval', $productIDs), static fn(int $id) => $id > 0));
        $state->setValue($pageID)->setName($name)->setFrontendName($name);
        foreach ($productFilter->getFilterConfig()->getLanguages() as $language) {
            $state->cSeo[(int)$language->kSprache] = $slug;
        }
        $state->isInitialized = true;

        return $state;
    }

    /**
     * @inheritdoc
     */
    public function setValue($value): FilterInterface
    {
        return parent::setValue((int)$value);
    }

    /**
     * Der Slug wird in create() gesetzt; der Core ruft setSeo() z. B. aus init() auf.
     *
     * @inheritdoc
     */
    public function setSeo(array $languages): FilterInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSQLCondition(): string
    {
        return $this->productIDs === []
            ? '0 = 1'
            : 'tartikel.kArtikel IN (' . \implode(',', $this->productIDs) . ')';
    }

    /**
     * @inheritdoc
     * @return array{}
     */
    public function getSQLJoin(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     * @return array{}
     */
    public function getOptions($mixed = null): array
    {
        return [];
    }
}
