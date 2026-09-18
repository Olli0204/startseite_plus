<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Countdown-Verwaltung (2.1.0): zentrale Tabelle für Countdowns, die vom Aktions-Banner
 * und auf Artikeldetailseiten genutzt werden.
 */
class Migration20260918120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `startseite_plus_countdown` (
                `id`              INT          NOT NULL AUTO_INCREMENT,
                `name`            VARCHAR(100) NOT NULL,
                `until`           DATETIME     NOT NULL,
                `label`           VARCHAR(150) NOT NULL DEFAULT "",
                `label_en`        VARCHAR(150) NOT NULL DEFAULT "",
                `style`           VARCHAR(10)  NOT NULL DEFAULT "boxes"   COMMENT "boxes | inline",
                `expired_mode`    VARCHAR(10)  NOT NULL DEFAULT "hide"    COMMENT "hide | text | keep",
                `expired_text`    VARCHAR(255) NOT NULL DEFAULT "",
                `expired_text_en` VARCHAR(255) NOT NULL DEFAULT "",
                `product_page`    VARCHAR(10)  NOT NULL DEFAULT "none"    COMMENT "none | sale | all",
                `active`          TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `idx_active_until` (`active`, `until`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `startseite_plus_countdown`');
        }
    }
}
