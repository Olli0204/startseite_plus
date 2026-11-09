<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.19.0): Banner der Aktion oben auf einer gewählten Kategorieseite (banner_category = kKategorie).
 */
class Migration20261109140000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'banner_category'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_nl_deal` ADD COLUMN `banner_category` INT NOT NULL DEFAULT 0 '
                . 'COMMENT "Kategorie mit Aktions-Banner" AFTER `display`'
            );
        }
    }

    public function down(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'banner_category'") !== null) {
            $this->execute('ALTER TABLE `startseite_plus_nl_deal` DROP COLUMN `banner_category`');
        }
    }
}
