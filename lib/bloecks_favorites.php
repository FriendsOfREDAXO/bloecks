<?php

namespace FriendsOfRedaxo\Bloecks;

use rex;
use rex_addon;
use rex_i18n;
use rex_sql;

use function is_array;

/**
 * Favoriten: gemeinsame Slice-Vorlagen (1:1-Kopie der Slice-Werte) in rex_bloecks_favorite.
 *
 * Einfuegen darf, wer kopieren darf; Anlegen und Loeschen nur Admins und "bloecks[favorites]".
 */
class Favorites
{
    /** @var list<array{id: int, name: string, module_id: int, module_name: string, createuser: string, createdate: string}>|null */
    private static ?array $listCache = null;

    public static function table(): string
    {
        return rex::getTable('bloecks_favorite');
    }

    /** Feature eingeschaltet und Copy & Paste aktiv (Favoriten haengen an dessen Oberflaeche). */
    public static function isEnabled(): bool
    {
        $addon = rex_addon::get('bloecks');

        return (bool) $addon->getConfig('enable_favorites', false)
            && (bool) $addon->getConfig('enable_copy_paste', false);
    }

    public static function canUse(): bool
    {
        $user = rex::getUser();

        return self::isEnabled() && null !== $user
            && ($user->hasPerm('bloecks[]') || $user->hasPerm('bloecks[copy]'));
    }

    public static function canManage(): bool
    {
        $user = rex::getUser();

        return self::canUse() && null !== $user
            && ($user->isAdmin() || $user->hasPerm('bloecks[favorites]'));
    }

    /**
     * Spalten, die eine Slice-Kopie ausmachen -- deckungsgleich mit Copy/Cut.
     *
     * @return list<string>
     */
    public static function sliceFields(): array
    {
        $fields = ['module_id', 'status'];
        for ($i = 1; $i <= 20; ++$i) {
            $fields[] = 'value' . $i;
        }
        for ($i = 1; $i <= 5; ++$i) {
            $fields[] = 'media' . $i;
            $fields[] = 'medialist' . $i;
            $fields[] = 'link' . $i;
            $fields[] = 'linklist' . $i;
        }

        return $fields;
    }

    /**
     * Favoriten fuer die Auswahl, nur Module, die der Benutzer verwenden darf.
     *
     * @return list<array{id: int, name: string, module_id: int, module_name: string, createuser: string, createdate: string}>
     */
    public static function getList(): array
    {
        $user = rex::getUser();
        if (!self::canUse() || null === $user) {
            return [];
        }
        if (null !== self::$listCache) {
            return self::$listCache;
        }

        $rows = rex_sql::factory()->getArray(
            'SELECT f.id, f.name, f.module_id, f.createuser, f.createdate, m.name AS module_name
             FROM ' . self::table() . ' f
             LEFT JOIN ' . rex::getTable('module') . ' m ON m.id = f.module_id
             ORDER BY f.name, f.id',
        );

        $modulePerm = $user->getComplexPerm('modules');
        $list = [];
        foreach ($rows as $row) {
            $moduleId = (int) $row['module_id'];
            if (!$user->isAdmin() && !$modulePerm->hasPerm($moduleId)) {
                continue;
            }
            $list[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'module_id' => $moduleId,
                'module_name' => null !== $row['module_name'] ? (string) $row['module_name'] : rex_i18n::msg('bloecks_unknown_module'),
                'createuser' => (string) $row['createuser'],
                'createdate' => (string) $row['createdate'],
            ];
        }

        return self::$listCache = $list;
    }

    public static function hasAny(): bool
    {
        return [] !== self::getList();
    }

    /**
     * @return array{id: int, name: string, data: array<string, mixed>}|null
     */
    public static function get(int $id): ?array
    {
        $rows = rex_sql::factory()->getArray('SELECT id, name, data FROM ' . self::table() . ' WHERE id = ?', [$id]);
        if (!$rows) {
            return null;
        }
        $data = json_decode((string) $rows[0]['data'], true);

        return [
            'id' => (int) $rows[0]['id'],
            'name' => (string) $rows[0]['name'],
            'data' => is_array($data) ? $data : [],
        ];
    }

    /**
     * Speichert die Werte eines Slices als Favorit.
     *
     * @param array<string, mixed> $sliceRow komplette Zeile aus rex_article_slice
     */
    public static function add(array $sliceRow, string $name): int
    {
        $data = [];
        foreach (self::sliceFields() as $field) {
            $data[$field] = $sliceRow[$field] ?? null;
        }

        $sql = rex_sql::factory();
        $sql->setTable(self::table());
        $sql->setValue('name', mb_substr($name, 0, 191));
        $sql->setValue('module_id', (int) $sliceRow['module_id']);
        $sql->setValue('data', json_encode($data));
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();
        $sql->insert();
        self::$listCache = null;

        return (int) $sql->getLastId();
    }

    public static function delete(int $id): bool
    {
        $sql = rex_sql::factory();
        $sql->setQuery('DELETE FROM ' . self::table() . ' WHERE id = ?', [$id]);
        self::$listCache = null;

        return $sql->getRows() > 0;
    }
}
