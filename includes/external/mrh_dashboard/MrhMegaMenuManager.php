<?php
/**
 * --------------------------------------------------------------
 * MrhMegaMenuManager
 * --------------------------------------------------------------
 * Backend-Logik für den Mega-Menü Manager.
 * Liest Kategorien aus der modified eCommerce DB,
 * speichert/lädt die Spalten-Konfiguration,
 * generiert den Frontend-Cache (JSON).
 * --------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class MrhMegaMenuManager
{
    /** @var int Aktive Sprach-ID */
    private int $language_id;

    /** @var string Pfad zur Cache-Datei */
    private string $cache_file;

    public function __construct()
    {
        $this->language_id = (int)($_SESSION['languages_id'] ?? 2); // 2 = Deutsch
        $this->cache_file  = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/megamenu_config.json';
    }

    /**
     * Holt alle Hauptkategorien (Level 0) die Unterkategorien haben.
     * Das sind die Kategorien die ein Mega-Dropdown bekommen können.
     *
     * @return array
     */
    public function getMainCategories(): array
    {
        $categories = [];

        $query = 'SELECT c.categories_id, cd.categories_name, c.sort_order
                    FROM ' . TABLE_CATEGORIES . ' c
                    JOIN ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                      ON c.categories_id = cd.categories_id
                   WHERE c.parent_id = 0
                     AND c.categories_status = 1
                     AND cd.language_id = ' . $this->language_id . '
                   ORDER BY c.sort_order, cd.categories_name';

        $result = xtc_db_query($query);
        while ($row = xtc_db_fetch_array($result)) {
            // Nur Kategorien mit Unterkategorien
            $sub_query = 'SELECT COUNT(*) as cnt FROM ' . TABLE_CATEGORIES . '
                          WHERE parent_id = ' . (int)$row['categories_id'] . '
                            AND categories_status = 1';
            $sub_result = xtc_db_fetch_array(xtc_db_query($sub_query));

            if ($sub_result['cnt'] > 0) {
                $categories[] = $row;
            }
        }

        return $categories;
    }

    /**
     * Holt alle Unterkategorien einer Hauptkategorie (rekursiv, alle Level).
     * Gibt ein flaches Array mit Level-Information zurück.
     *
     * @param int $parent_id
     * @param int $level
     * @return array
     */
    public function getAllSubcategories(int $parent_id, int $level = 0): array
    {
        $categories = [];

        $query = 'SELECT c.categories_id, cd.categories_name, c.sort_order, c.parent_id
                    FROM ' . TABLE_CATEGORIES . ' c
                    JOIN ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                      ON c.categories_id = cd.categories_id
                   WHERE c.parent_id = ' . $parent_id . '
                     AND c.categories_status = 1
                     AND cd.language_id = ' . $this->language_id . '
                   ORDER BY c.sort_order, cd.categories_name';

        $result = xtc_db_query($query);
        while ($row = xtc_db_fetch_array($result)) {
            $row['level'] = $level;
            $categories[] = $row;

            // Rekursiv Unterkategorien holen
            $sub = $this->getAllSubcategories((int)$row['categories_id'], $level + 1);
            $categories = array_merge($categories, $sub);
        }

        return $categories;
    }

    /**
     * Baut den cPath-String für eine Kategorie (z.B. "581210_581211_581215").
     *
     * @param int $category_id
     * @return string
     */
    public function buildCPath(int $category_id): string
    {
        $path = [];
        $current_id = $category_id;

        // Maximal 10 Level nach oben traversieren
        for ($i = 0; $i < 10; $i++) {
            $path[] = $current_id;

            $query = 'SELECT parent_id FROM ' . TABLE_CATEGORIES . '
                      WHERE categories_id = ' . $current_id;
            $result = xtc_db_fetch_array(xtc_db_query($query));

            if (!$result || (int)$result['parent_id'] === 0) {
                break;
            }
            $current_id = (int)$result['parent_id'];
        }

        return implode('_', array_reverse($path));
    }

    /**
     * Prüft ob für eine Hauptkategorie eine Konfiguration existiert.
     *
     * @param int $parent_category_id
     * @return bool
     */
    public function hasConfig(int $parent_category_id): bool
    {
        $query = 'SELECT COUNT(*) as cnt FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . $parent_category_id;
        $result = xtc_db_fetch_array(xtc_db_query($query));
        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Lädt die gespeicherte Konfiguration für eine Hauptkategorie.
     *
     * @param int $parent_category_id
     * @return array|null
     */
    public function getConfig(int $parent_category_id): ?array
    {
        $columns = [];

        $query = 'SELECT * FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . $parent_category_id . '
                  ORDER BY column_index';
        $result = xtc_db_query($query);

        while ($row = xtc_db_fetch_array($result)) {
            $items = [];

            $items_query = 'SELECT mi.*, cd.categories_name
                            FROM `mrh_megamenu_items` mi
                            LEFT JOIN ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                              ON mi.category_id = cd.categories_id
                              AND cd.language_id = ' . $this->language_id . '
                            WHERE mi.config_id = ' . (int)$row['id'] . '
                              AND mi.is_active = 1
                            ORDER BY mi.sort_order';
            $items_result = xtc_db_query($items_query);

            while ($item = xtc_db_fetch_array($items_result)) {
                $items[] = [
                    'category_id' => (int)$item['category_id'],
                    'label'       => $item['custom_label'] ?: $item['categories_name'],
                    'custom_url'  => $item['custom_url'] ?: null,
                ];
            }

            $columns[] = [
                'title' => $row['column_title'],
                'icon'  => $row['column_icon'],
                'items' => $items,
            ];
        }

        return !empty($columns) ? $columns : null;
    }

    /**
     * Speichert die Konfiguration für eine Hauptkategorie.
     * Löscht zuerst die alte Config und schreibt die neue.
     *
     * @param int $parent_category_id
     * @param array $columns
     * @return bool
     */
    public function saveConfig(int $parent_category_id, array $columns): bool
    {
        try {
            // Alte Konfiguration löschen
            $this->deleteConfig($parent_category_id);

            $now = date('Y-m-d H:i:s');

            foreach ($columns as $col_idx => $column) {
                $title = xtc_db_input($column['title'] ?? '');
                $icon  = xtc_db_input($column['icon'] ?? '');

                // Spalte einfügen
                $query = 'INSERT INTO `mrh_megamenu_config` (
                    parent_category_id, column_index, column_title, column_icon, sort_order, date_added, last_modified
                ) VALUES (
                    ' . $parent_category_id . ',
                    ' . (int)$col_idx . ',
                    "' . $title . '",
                    "' . $icon . '",
                    ' . (int)$col_idx . ',
                    "' . $now . '",
                    "' . $now . '"
                )';
                xtc_db_query($query);

                // Config-ID der gerade eingefügten Spalte
                $config_id = xtc_db_insert_id();

                // Items einfügen
                $items = $column['items'] ?? [];
                foreach ($items as $item_idx => $item) {
                    $cat_id = (int)($item['category_id'] ?? 0);
                    $label  = xtc_db_input($item['label'] ?? '');
                    $url    = xtc_db_input($item['custom_url'] ?? '');

                    if ($cat_id > 0) {
                        $query = 'INSERT INTO `mrh_megamenu_items` (
                            config_id, category_id, custom_label, custom_url, sort_order, is_active
                        ) VALUES (
                            ' . $config_id . ',
                            ' . $cat_id . ',
                            ' . ($label ? '"' . $label . '"' : 'NULL') . ',
                            ' . ($url ? '"' . $url . '"' : 'NULL') . ',
                            ' . (int)$item_idx . ',
                            1
                        )';
                        xtc_db_query($query);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('MRH Dashboard - Mega-Menü Save Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Löscht die Konfiguration für eine Hauptkategorie.
     *
     * @param int $parent_category_id
     * @return void
     */
    public function deleteConfig(int $parent_category_id): void
    {
        // Items löschen (CASCADE sollte das auch machen, aber sicherheitshalber)
        $query = 'DELETE mi FROM `mrh_megamenu_items` mi
                  JOIN `mrh_megamenu_config` mc ON mi.config_id = mc.id
                  WHERE mc.parent_category_id = ' . $parent_category_id;
        xtc_db_query($query);

        // Config löschen
        $query = 'DELETE FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . $parent_category_id;
        xtc_db_query($query);
    }

    /**
     * Generiert die JSON-Cache-Datei für den Frontend-Output.
     * Diese Datei wird von mrh-megamenu-config.js.php gelesen.
     *
     * @return bool
     */
    public function regenerateCache(): bool
    {
        try {
            $config = [];

            // Alle konfigurierten Hauptkategorien laden
            $query = 'SELECT DISTINCT parent_category_id FROM `mrh_megamenu_config` ORDER BY parent_category_id';
            $result = xtc_db_query($query);

            while ($row = xtc_db_fetch_array($result)) {
                $parent_id = (int)$row['parent_category_id'];

                // Kategoriename holen
                $name_query = 'SELECT cd.categories_name FROM ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                               WHERE cd.categories_id = ' . $parent_id . '
                                 AND cd.language_id = ' . $this->language_id;
                $name_result = xtc_db_fetch_array(xtc_db_query($name_query));
                $cat_name = $name_result['categories_name'] ?? '';

                // Spalten laden
                $columns = $this->getConfig($parent_id);
                if (!$columns) continue;

                // Für jedes Item den cPath berechnen
                foreach ($columns as &$column) {
                    foreach ($column['items'] as &$item) {
                        $item['cpath'] = $this->buildCPath($item['category_id']);
                        $item['url']   = $item['custom_url'] ?: 'index.php?cPath=' . $item['cpath'];
                    }
                    unset($item);
                }
                unset($column);

                $config[] = [
                    'parent_id'   => $parent_id,
                    'parent_name' => $cat_name,
                    'columns'     => $columns,
                ];
            }

            // JSON-Datei schreiben
            $dir = dirname($this->cache_file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return (bool)file_put_contents($this->cache_file, $json);

        } catch (Exception $e) {
            error_log('MRH Dashboard - Cache Regeneration Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liest die Cache-Datei und gibt die Config zurück.
     *
     * @return array|null
     */
    public function getCachedConfig(): ?array
    {
        if (!file_exists($this->cache_file)) {
            return null;
        }

        $json = file_get_contents($this->cache_file);
        $config = json_decode($json, true);

        return is_array($config) ? $config : null;
    }
}
