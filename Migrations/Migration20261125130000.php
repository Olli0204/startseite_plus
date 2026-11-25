<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Countdowns (2.23.0): Design der Box auf Artikel- und Kategorieseiten (hell/dunkel) und Kicker-Zeile DE/EN.
 */
class Migration20261125130000 extends Migration implements IMigration
{
    private const COLUMNS = [
        'design'    => "VARCHAR(10) NOT NULL DEFAULT 'light' COMMENT \"light|dark\" AFTER `style`",
        'kicker'    => "VARCHAR(100) NOT NULL DEFAULT '' COMMENT \"kleine Zeile über der Beschriftung\" AFTER `label_en`",
        'kicker_en' => "VARCHAR(100) NOT NULL DEFAULT '' AFTER `kicker`",
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $column => $definition) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_countdown` LIKE '" . $column . "'") === null) {
                $this->execute('ALTER TABLE `startseite_plus_countdown` ADD COLUMN `' . $column . '` ' . $definition);
            }
        }
    }

    public function down(): void
    {
        foreach (\array_reverse(\array_keys(self::COLUMNS)) as $column) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_countdown` LIKE '" . $column . "'") !== null) {
                $this->execute('ALTER TABLE `startseite_plus_countdown` DROP COLUMN `' . $column . '`');
            }
        }
    }
}
