<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.17.0): Set-Rabatt in Prozent (type "setpct", Prozentwert in `price`) und Artikelauswahl über einen
 * Merkmalwert (z. B. Warengruppe = Snowboard) für die Artikel und die Set-Partner einer Regel; eigener Hinweistext DE/EN.
 */
class Migration20261109120000 extends Migration implements IMigration
{
    private const COLUMNS = [
        'product_feature' => 'INT NOT NULL DEFAULT 0 COMMENT "Merkmalwert (kMerkmalWert) der Artikel" AFTER `partners`',
        'partner_feature' => 'INT NOT NULL DEFAULT 0 COMMENT "Merkmalwert (kMerkmalWert) der Set-Partner" AFTER `product_feature`',
        'label'           => 'VARCHAR(160) NOT NULL DEFAULT "" COMMENT "Hinweistext DE" AFTER `price`',
        'label_en'        => 'VARCHAR(160) NOT NULL DEFAULT "" COMMENT "Hinweistext EN" AFTER `label`',
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
