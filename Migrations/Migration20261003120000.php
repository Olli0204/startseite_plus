<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.13.0): Deal-Preise je Seite (Festpreis je Artikel, Set-Preis mit Partnerartikel) und ein eigener
 * Deal-Code, der die Preise im Kupon-Feld des Warenkorbs freischaltet.
 */
class Migration20261003120000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'code'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_nl_deal`
                    ADD COLUMN `code` VARCHAR(50) NOT NULL DEFAULT "" AFTER `coupon`'
            );
        }
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `startseite_plus_nl_deal_rule` (
                `id`       INT           NOT NULL AUTO_INCREMENT,
                `deal_id`  INT           NOT NULL,
                `type`     VARCHAR(10)   NOT NULL DEFAULT "price" COMMENT "price | set",
                `products` TEXT          NULL                     COMMENT "Artikel-IDs, ;-getrennt",
                `partners` TEXT          NULL                     COMMENT "Set: Partnerartikel-IDs, ;-getrennt",
                `price`    DECIMAL(10,2) NOT NULL DEFAULT 0       COMMENT "Bruttopreis je Stück",
                `sort`     INT           NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_deal` (`deal_id`, `sort`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(): void
    {
        if ($this->doDeleteData()) {
            $this->execute('DROP TABLE IF EXISTS `startseite_plus_nl_deal_rule`');
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'code'") !== null) {
                $this->execute('ALTER TABLE `startseite_plus_nl_deal` DROP COLUMN `code`');
            }
        }
    }
}
