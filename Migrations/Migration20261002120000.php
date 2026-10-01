<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.12.0): eigener englischer Link je Deal-Seite. Wie bei Kategorien bestimmt die URL die Sprache;
 * bestehende Seiten bekommen "<deutscher Link>-en".
 */
class Migration20261002120000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'slug_en'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_nl_deal`
                    ADD COLUMN `slug_en` VARCHAR(120) NOT NULL DEFAULT "" AFTER `slug`'
            );
        }
        $this->execute(
            'UPDATE `startseite_plus_nl_deal` SET `slug_en` = LEFT(CONCAT(`slug`, "-en"), 120) WHERE `slug_en` = ""'
        );
        if ($this->fetchOne("SHOW INDEX FROM `startseite_plus_nl_deal` WHERE Key_name = 'uq_slug_en'") === null) {
            $this->execute('ALTER TABLE `startseite_plus_nl_deal` ADD UNIQUE KEY `uq_slug_en` (`slug_en`)');
        }
    }

    public function down(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'slug_en'") !== null) {
            $this->execute('ALTER TABLE `startseite_plus_nl_deal` DROP INDEX `uq_slug_en`, DROP COLUMN `slug_en`');
        }
    }
}
