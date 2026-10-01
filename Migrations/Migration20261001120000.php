<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.11.0): versteckte Aktionsseiten, nur über einen geheimen Link erreichbar.
 * Die Seite ist eine normale JTL-Artikelliste der gewählten Artikel plus Kupon-Code.
 */
class Migration20261001120000 extends Migration implements IMigration
{
    public function up(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `startseite_plus_nl_deal` (
                `id`          INT          NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(100) NOT NULL,
                `slug`        VARCHAR(120) NOT NULL,
                `title`       VARCHAR(150) NOT NULL DEFAULT "",
                `title_en`    VARCHAR(150) NOT NULL DEFAULT "",
                `text`        TEXT         NULL,
                `text_en`     TEXT         NULL,
                `coupon`      VARCHAR(255) NOT NULL DEFAULT "" COMMENT "Kupon-Code",
                `products`    TEXT         NULL                COMMENT "Artikel-IDs, ;-getrennt",
                `valid_from`  DATETIME     NULL,
                `valid_until` DATETIME     NULL,
                `active`      TINYINT(1)   NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `startseite_plus_nl_deal`');
        }
    }
}
