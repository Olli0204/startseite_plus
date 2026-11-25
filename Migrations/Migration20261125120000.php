<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Countdowns (2.22.0): Beschränkung auf eine Kategorie (Artikelseiten nur für Artikel dieser Kategorie) und
 * optionale Anzeige oben auf dieser Kategorieseite.
 */
class Migration20261125120000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_countdown` LIKE 'category'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_countdown` ADD COLUMN `category` INT NOT NULL DEFAULT 0 '
                . 'COMMENT "kKategorie, 0 = alle Artikel" AFTER `product_page`'
            );
        }
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_countdown` LIKE 'category_page'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_countdown` ADD COLUMN `category_page` TINYINT NOT NULL DEFAULT 0 '
                . 'COMMENT "auch oben auf der Kategorieseite" AFTER `category`'
            );
        }
    }

    public function down(): void
    {
        foreach (['category_page', 'category'] as $column) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_countdown` LIKE '" . $column . "'") !== null) {
                $this->execute('ALTER TABLE `startseite_plus_countdown` DROP COLUMN `' . $column . '`');
            }
        }
    }
}
