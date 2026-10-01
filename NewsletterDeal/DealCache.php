<?php

declare(strict_types=1);

namespace Plugin\startseite_plus\NewsletterDeal;

use JTL\DB\DbInterface;
use JTL\Shop;

/**
 * Seitencache (LiteSpeed im Live-Shop) bei Zustandswechseln der Newsletter-Deals leeren.
 *
 * Zeitgesteuerte Wechsel (Start, "Für alle Kunden ab", Ende) ändern das HTML gecachter Seiten: Platzhalter für
 * Deal-Preise, Hero-Slide "Newsletter-Aktion". Ohne Leeren zeigte die Startseite die Slide erst nach Ablauf des Caches
 * bzw. nach dem Ende noch weiter. Jede Seite merkt sich in cache_state den zuletzt ausgelieferten Zustand; weicht der
 * aktuelle ab, schickt die nächste PHP-Antwort (Seite, IO-Aufruf) einmal "X-LiteSpeed-Purge: *". Anderen Servern
 * (nginx im Testshop) ist der Header egal.
 */
final class DealCache
{
    public const PURGE_HEADER = 'X-LiteSpeed-Purge: *';

    private static bool $synced = false;

    /** @var string[] Für Tests: gesendete Header */
    public static array $sent = [];

    public function __construct(private readonly DbInterface $db)
    {
    }

    public static function create(): self
    {
        return new self(Shop::Container()->getDB());
    }

    /**
     * HOOK_ROUTER_PRE_DISPATCH: einmal pro Request Zustände abgleichen.
     */
    public function sync(?int $now = null): bool
    {
        if (self::$synced) {
            return false;
        }
        self::$synced = true;
        try {
            $rows = $this->db->getObjects(
                'SELECT id, active, valid_from, valid_until, public_from, cache_state FROM ' . DealPageService::TABLE
            );
        } catch (\Throwable) {
            return false;   // Spalten fehlen (Plugin-Update läuft)
        }
        $changed = false;
        foreach ($rows as $row) {
            $state = DealPageService::cacheState($row, $now);
            if ($state === (string)$row->cache_state) {
                continue;
            }
            $changed = true;
            try {
                $this->db->update(DealPageService::TABLE, 'id', (int)$row->id, (object)['cache_state' => $state]);
            } catch (\Throwable) {
            }
        }
        if ($changed) {
            self::purge();
        }

        return $changed;
    }

    /**
     * Nach Änderungen im Backend (Speichern, Aktivieren, Löschen) sofort leeren.
     */
    public static function purge(): void
    {
        self::$sent[] = self::PURGE_HEADER;
        if (\PHP_SAPI !== 'cli' && !\headers_sent()) {
            \header(self::PURGE_HEADER);
        }
    }

    public static function reset(): void
    {
        self::$synced = false;
        self::$sent   = [];
    }
}
