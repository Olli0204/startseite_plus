<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.15.0): Darstellung des Newsletter-Preises je Deal-Seite
 * (price = als Hauptpreis, line = Zeile im Preisblock, badge = Marke am Produktbild).
 */
class Migration20261004120000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'display'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_nl_deal`
                    ADD COLUMN `display` VARCHAR(10) NOT NULL DEFAULT "line" AFTER `code`'
            );
        }
    }

    public function down(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'display'") !== null) {
            $this->execute('ALTER TABLE `startseite_plus_nl_deal` DROP COLUMN `display`');
        }
    }
}
