<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.18.0): Artikel und Set-Partner einer Regel zusätzlich über eine Kategorie (z. B. "Union Bundle Deal").
 */
class Migration20261109130000 extends Migration implements IMigration
{
    private const COLUMNS = [
        'product_category' => 'INT NOT NULL DEFAULT 0 COMMENT "Kategorie (kKategorie) der Artikel" AFTER `partner_feature`',
        'partner_category' => 'INT NOT NULL DEFAULT 0 COMMENT "Kategorie (kKategorie) der Set-Partner" AFTER `product_category`',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $column => $definition) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal_rule` LIKE '" . $column . "'") === null) {
                $this->execute('ALTER TABLE `startseite_plus_nl_deal_rule` ADD COLUMN `' . $column . '` ' . $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (\array_reverse(\array_keys(self::COLUMNS)) as $column) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal_rule` LIKE '" . $column . "'") !== null) {
                $this->execute('ALTER TABLE `startseite_plus_nl_deal_rule` DROP COLUMN `' . $column . '`');
            }
        }
    }
}
