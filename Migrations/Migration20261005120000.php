<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Newsletter-Deals (2.16.0): "Für alle Kunden ab" (public_from) – ab dann gelten die Deal-Preise ohne Link/Code für alle
 * Kunden und die Hero-Slide "Newsletter-Aktion" erscheint. cache_state merkt sich den zuletzt ausgelieferten Zustand je
 * Seite, damit der LiteSpeed-Seitencache bei jedem Wechsel (Newsletter → alle → beendet) einmal geleert wird.
 */
class Migration20261005120000 extends Migration implements IMigration
{
    public function up(): void
    {
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'public_from'") === null) {
            $this->execute('ALTER TABLE `startseite_plus_nl_deal` ADD COLUMN `public_from` DATETIME NULL AFTER `valid_until`');
        }
        if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE 'cache_state'") === null) {
            $this->execute(
                'ALTER TABLE `startseite_plus_nl_deal` ADD COLUMN `cache_state` VARCHAR(20) NOT NULL DEFAULT "" AFTER `active`'
            );
        }
    }

    public function down(): void
    {
        foreach (['public_from', 'cache_state'] as $column) {
            if ($this->fetchOne("SHOW COLUMNS FROM `startseite_plus_nl_deal` LIKE '" . $column . "'") !== null) {
                $this->execute('ALTER TABLE `startseite_plus_nl_deal` DROP COLUMN `' . $column . '`');
            }
        }
    }
}
