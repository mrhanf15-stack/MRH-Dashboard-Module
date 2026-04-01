<?php
/**
 * --------------------------------------------------------------
 * MrhMegaMenuManager
 * Version: 1.2.1
 * --------------------------------------------------------------
 * Backend-Logik fuer den Mega-Menu Manager.
 * Liest Kategorien aus der modified eCommerce DB,
 * speichert/laedt die Spalten-Konfiguration (4 Sprachen: DE/EN/FR/ES),
 * verwaltet zusaetzliche Nav-Links mit MRH_-Sprachkonstanten,
 * bietet Sprachdatei-Editor fuer MRH_-Konstanten,
 * generiert den Frontend-Cache (JSON).
 * --------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class MrhMegaMenuManager
{
    /** @var int Aktive Sprach-ID */
    var $language_id;

    /** @var string Sprach-Code (de, en, fr, es) */
    var $language_code;

    /** @var string Pfad zur Cache-Datei */
    var $cache_file;

    /** @var array Mapping language_id => Sprach-Suffix */
    var $lang_map = array(
        2 => 'de',
        1 => 'en',
        5 => 'fr',
        7 => 'es',
    );

    /** @var array Mapping Sprach-Code => Sprachdatei-Pfad (relativ zu DIR_FS_CATALOG) */
    var $lang_file_map = array(
        'de' => 'lang/german/extra/admin/mrh_dashboard.php',
        'en' => 'lang/english/extra/admin/mrh_dashboard.php',
        'fr' => 'lang/french/extra/admin/mrh_dashboard.php',
        'es' => 'lang/spanish/extra/admin/mrh_dashboard.php',
    );

    public function __construct()
    {
        $this->language_id   = (int)($_SESSION['languages_id'] ?? 2);
        $this->language_code = $this->lang_map[$this->language_id] ?? 'de';
        $this->cache_file    = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/megamenu_config.json';
    }

    /**
     * Gibt den Spalten-Titel-Feldnamen fuer die aktive Sprache zurueck.
     */
    public function getTitleField($lang_code = null)
    {
        $code = $lang_code ?: $this->language_code;
        return 'column_title_' . $code;
    }

    /**
     * Gibt alle verfuegbaren Sprachen zurueck.
     */
    public function getAvailableLanguages()
    {
        return array(
            'de' => 'Deutsch',
            'en' => 'English',
            'fr' => 'Francais',
            'es' => 'Espanol',
        );
    }

    /**
     * Gibt das lang_map Array zurueck.
     */
    public function getLangMap()
    {
        return $this->lang_map;
    }

    /**
     * Holt alle Hauptkategorien (Level 0) die Unterkategorien haben.
     */
    public function getMainCategories()
    {
        $categories = array();

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
     */
    public function getAllSubcategories($parent_id, $level = 0)
    {
        $categories = array();

        $query = 'SELECT c.categories_id, cd.categories_name, c.sort_order, c.parent_id
                    FROM ' . TABLE_CATEGORIES . ' c
                    JOIN ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                      ON c.categories_id = cd.categories_id
                   WHERE c.parent_id = ' . (int)$parent_id . '
                     AND c.categories_status = 1
                     AND cd.language_id = ' . $this->language_id . '
                   ORDER BY c.sort_order, cd.categories_name';

        $result = xtc_db_query($query);
        while ($row = xtc_db_fetch_array($result)) {
            $row['level'] = $level;
            $categories[] = $row;

            $sub = $this->getAllSubcategories((int)$row['categories_id'], $level + 1);
            $categories = array_merge($categories, $sub);
        }

        return $categories;
    }

    /**
     * Baut den cPath-String fuer eine Kategorie.
     */
    public function buildCPath($category_id)
    {
        $path = array();
        $current_id = (int)$category_id;

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
     * Prueft ob fuer eine Hauptkategorie eine Konfiguration existiert.
     */
    public function hasConfig($parent_category_id)
    {
        $query = 'SELECT COUNT(*) as cnt FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . (int)$parent_category_id;
        $result = xtc_db_fetch_array(xtc_db_query($query));
        return ($result['cnt'] ?? 0) > 0;
    }

    /**
     * Laedt die gespeicherte Konfiguration fuer eine Hauptkategorie.
     * Gibt alle Sprachversionen der Titel zurueck (inkl. ES).
     */
    public function getConfig($parent_category_id)
    {
        $columns = array();

        $query = 'SELECT * FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . (int)$parent_category_id . '
                  ORDER BY column_index';
        $result = xtc_db_query($query);

        while ($row = xtc_db_fetch_array($result)) {
            $items = array();

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
                $items[] = array(
                    'category_id' => (int)$item['category_id'],
                    'label'       => $item['custom_label'] ? $item['custom_label'] : $item['categories_name'],
                    'custom_url'  => $item['custom_url'] ? $item['custom_url'] : null,
                );
            }

            $columns[] = array(
                'title_de' => $row['column_title_de'],
                'title_en' => $row['column_title_en'],
                'title_fr' => $row['column_title_fr'],
                'title_es' => $row['column_title_es'] ?? '',
                'title'    => $row['column_title_' . $this->language_code] ?? $row['column_title_de'],
                'icon'     => $row['column_icon'],
                'items'    => $items,
            );
        }

        return !empty($columns) ? $columns : null;
    }

    /**
     * Speichert die Konfiguration fuer eine Hauptkategorie.
     * Unterstuetzt 4 Sprachen: DE/EN/FR/ES.
     */
    public function saveConfig($parent_category_id, $columns)
    {
        try {
            $this->deleteConfig((int)$parent_category_id);

            $now = date('Y-m-d H:i:s');

            foreach ($columns as $col_idx => $column) {
                $title_de = xtc_db_input($column['title_de'] ?? $column['title'] ?? '');
                $title_en = xtc_db_input($column['title_en'] ?? '');
                $title_fr = xtc_db_input($column['title_fr'] ?? '');
                $title_es = xtc_db_input($column['title_es'] ?? '');
                $icon     = xtc_db_input($column['icon'] ?? '');

                $query = 'INSERT INTO `mrh_megamenu_config` (
                    parent_category_id, column_index, column_title_de, column_title_en, column_title_fr, column_title_es, column_icon, sort_order, date_added, last_modified
                ) VALUES (
                    ' . (int)$parent_category_id . ',
                    ' . (int)$col_idx . ',
                    "' . $title_de . '",
                    "' . $title_en . '",
                    "' . $title_fr . '",
                    "' . $title_es . '",
                    "' . $icon . '",
                    ' . (int)$col_idx . ',
                    "' . $now . '",
                    "' . $now . '"
                )';
                xtc_db_query($query);

                $config_id = xtc_db_insert_id();

                $items = $column['items'] ?? array();
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
            error_log('MRH Dashboard - Mega-Menu Save Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Loescht die Konfiguration fuer eine Hauptkategorie.
     */
    public function deleteConfig($parent_category_id)
    {
        $query = 'DELETE mi FROM `mrh_megamenu_items` mi
                  JOIN `mrh_megamenu_config` mc ON mi.config_id = mc.id
                  WHERE mc.parent_category_id = ' . (int)$parent_category_id;
        xtc_db_query($query);

        $query = 'DELETE FROM `mrh_megamenu_config`
                  WHERE parent_category_id = ' . (int)$parent_category_id;
        xtc_db_query($query);
    }

    // ============================================================
    // Nav-Links Verwaltung
    // ============================================================

    /**
     * Holt alle Nav-Links.
     */
    public function getNavLinks()
    {
        $links = array();

        $query = 'SELECT * FROM `mrh_megamenu_navlinks` ORDER BY sort_order';
        $result = xtc_db_query($query);

        while ($row = xtc_db_fetch_array($result)) {
            $links[] = array(
                'id'         => (int)$row['id'],
                'url'        => $row['link_url'],
                'name'       => $row['link_name'],
                'icon'       => $row['link_icon'],
                'sort_order' => (int)$row['sort_order'],
                'is_active'  => (int)$row['is_active'],
            );
        }

        return $links;
    }

    /**
     * Speichert Nav-Links (loescht alle und fuegt neu ein).
     */
    public function saveNavLinks($links)
    {
        try {
            xtc_db_query("DELETE FROM `mrh_megamenu_navlinks`");

            $now = date('Y-m-d H:i:s');

            foreach ($links as $idx => $link) {
                $url  = xtc_db_input(trim($link['url'] ?? ''));
                $name = xtc_db_input(trim($link['name'] ?? ''));
                $icon = xtc_db_input(trim($link['icon'] ?? ''));
                $active = isset($link['is_active']) ? (int)$link['is_active'] : 1;

                if ($url === '' && $name === '') continue;

                xtc_db_query("INSERT INTO `mrh_megamenu_navlinks`
                    (link_url, link_name, link_icon, sort_order, is_active, date_added)
                    VALUES (
                        '" . $url . "',
                        '" . $name . "',
                        '" . $icon . "',
                        " . (int)$idx . ",
                        " . $active . ",
                        '" . $now . "'
                    )");
            }

            return true;
        } catch (Exception $e) {
            error_log('MRH Dashboard - Nav-Links Save Error: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // Sprachdatei-Editor
    // ============================================================

    /**
     * Liest alle MRH_-Konstanten aus einer Sprachdatei.
     */
    public function readLangConstants($lang_code)
    {
        $file = $this->_getLangFilePath($lang_code);
        if (!$file || !file_exists($file)) {
            return array();
        }

        $content = file_get_contents($file);
        $constants = array();

        // Alle define('MRH_...', '...') extrahieren
        $regex = '/define\s*\(\s*\'(MRH_[A-Z0-9_]+)\'\s*,\s*\'([^\']*)\'\s*\)/';
        if (preg_match_all($regex, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $constants[$match[1]] = $match[2];
            }
        }

        return $constants;
    }

    /**
     * Schreibt/aktualisiert MRH_-Konstanten in einer Sprachdatei.
     * Bestehende Konstanten werden aktualisiert, neue am Ende eingefuegt.
     */
    public function writeLangConstants($lang_code, $constants)
    {
        $file = $this->_getLangFilePath($lang_code);
        if (!$file) return false;

        if (!file_exists($file)) {
            // Neue Datei erstellen
            $php_open = chr(60) . '?php';
            $lines = array();
            $lines[] = $php_open;
            $lines[] = '/**';
            $lines[] = ' * MRH Dashboard - Sprachkonstanten (' . strtoupper($lang_code) . ')';
            $lines[] = ' * Auto-generated by MRH Dashboard';
            $lines[] = ' */';
            $lines[] = "defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');";
            $lines[] = '';

            foreach ($constants as $key => $value) {
                if (strpos($key, 'MRH_') === 0) {
                    $lines[] = "define('" . $key . "', '" . addslashes($value) . "');";
                }
            }

            $content = implode("\n", $lines) . "\n";

            $dir = dirname($file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            return (bool)file_put_contents($file, $content);
        }

        // Bestehende Datei aktualisieren
        $content = file_get_contents($file);
        $updated_keys = array();

        foreach ($constants as $key => $value) {
            if (strpos($key, 'MRH_') !== 0) continue;

            $escaped_value = addslashes($value);
            $search = "define('" . $key . "'";

            if (strpos($content, $search) !== false) {
                // Zeile finden und ersetzen
                $pattern = '/define\s*\(\s*\'' . preg_quote($key, '/') . '\'\s*,\s*\'[^\']*\'\s*\)/';
                $replacement = "define('" . $key . "', '" . $escaped_value . "')";
                $content = preg_replace($pattern, $replacement, $content);
                $updated_keys[] = $key;
            }
        }

        // Neue Konstanten am Ende einfuegen
        $new_constants = array_diff_key($constants, array_flip($updated_keys));
        if (!empty($new_constants)) {
            $new_lines = "\n// --- Zusaetzliche MRH-Konstanten (auto-generated) ---\n";
            foreach ($new_constants as $key => $value) {
                if (strpos($key, 'MRH_') === 0) {
                    $new_lines .= "define('" . $key . "', '" . addslashes($value) . "');\n";
                }
            }
            $content .= $new_lines;
        }

        return (bool)file_put_contents($file, $content);
    }

    /**
     * Gibt den absoluten Pfad zur Sprachdatei zurueck.
     */
    function _getLangFilePath($lang_code)
    {
        if (!isset($this->lang_file_map[$lang_code])) {
            return null;
        }
        return DIR_FS_CATALOG . $this->lang_file_map[$lang_code];
    }

    // ============================================================
    // Cache-Generierung
    // ============================================================

    /**
     * Generiert die JSON-Cache-Datei fuer den Frontend-Output.
     * Enthaelt alle 4 Sprachen + Nav-Links.
     */
    public function regenerateCache()
    {
        try {
            $data = array(
                'categories' => array(),
                'navlinks'   => array(),
            );

            // === Mega-Menu Kategorien ===
            $query = 'SELECT DISTINCT parent_category_id FROM `mrh_megamenu_config` ORDER BY parent_category_id';
            $result = xtc_db_query($query);

            while ($row = xtc_db_fetch_array($result)) {
                $parent_id = (int)$row['parent_category_id'];

                // Kategoriename in allen Sprachen holen
                $names = array();
                foreach ($this->lang_map as $lid => $lcode) {
                    $name_query = 'SELECT cd.categories_name FROM ' . TABLE_CATEGORIES_DESCRIPTION . ' cd
                                   WHERE cd.categories_id = ' . $parent_id . '
                                     AND cd.language_id = ' . (int)$lid;
                    $name_result = xtc_db_fetch_array(xtc_db_query($name_query));
                    $names[$lcode] = $name_result['categories_name'] ?? '';
                }

                // Spalten laden
                $columns_raw = array();
                $col_query = 'SELECT * FROM `mrh_megamenu_config`
                              WHERE parent_category_id = ' . $parent_id . '
                              ORDER BY column_index';
                $col_result = xtc_db_query($col_query);

                while ($col = xtc_db_fetch_array($col_result)) {
                    $items = array();
                    $items_query = 'SELECT mi.category_id, mi.custom_label, mi.custom_url
                                    FROM `mrh_megamenu_items` mi
                                    WHERE mi.config_id = ' . (int)$col['id'] . '
                                      AND mi.is_active = 1
                                    ORDER BY mi.sort_order';
                    $items_result = xtc_db_query($items_query);

                    while ($item = xtc_db_fetch_array($items_result)) {
                        $cat_id = (int)$item['category_id'];
                        $cpath  = $this->buildCPath($cat_id);

                        // Labels in allen Sprachen
                        $labels = array();
                        foreach ($this->lang_map as $lid => $lcode) {
                            $label_query = 'SELECT categories_name FROM ' . TABLE_CATEGORIES_DESCRIPTION . '
                                            WHERE categories_id = ' . $cat_id . '
                                              AND language_id = ' . (int)$lid;
                            $label_result = xtc_db_fetch_array(xtc_db_query($label_query));
                            $labels[$lcode] = $item['custom_label'] ? $item['custom_label'] : ($label_result['categories_name'] ?? '');
                        }

                        $items[] = array(
                            'category_id' => $cat_id,
                            'labels'      => $labels,
                            'cpath'       => $cpath,
                            'url'         => $item['custom_url'] ? $item['custom_url'] : 'index.php?cPath=' . $cpath,
                        );
                    }

                    $columns_raw[] = array(
                        'titles' => array(
                            'de' => $col['column_title_de'],
                            'en' => $col['column_title_en'],
                            'fr' => $col['column_title_fr'],
                            'es' => $col['column_title_es'] ?? '',
                        ),
                        'icon'  => $col['column_icon'],
                        'items' => $items,
                    );
                }

                if (!empty($columns_raw)) {
                    $data['categories'][] = array(
                        'parent_id'    => $parent_id,
                        'parent_names' => $names,
                        'columns'      => $columns_raw,
                    );
                }
            }

            // === Nav-Links ===
            $navlinks = $this->getNavLinks();
            foreach ($navlinks as $link) {
                if ($link['is_active']) {
                    $data['navlinks'][] = array(
                        'url'       => $link['url'],
                        'name'      => $link['name'],
                        'icon'      => $link['icon'],
                        'is_active' => 1,
                    );
                }
            }

            // JSON-Datei schreiben
            $dir = dirname($this->cache_file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            return (bool)file_put_contents($this->cache_file, $json);

        } catch (Exception $e) {
            error_log('MRH Dashboard - Cache Regeneration Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Liest die Cache-Datei und gibt die Config zurueck.
     */
    public function getCachedConfig()
    {
        if (!file_exists($this->cache_file)) {
            return null;
        }

        $json = file_get_contents($this->cache_file);
        $config = json_decode($json, true);

        return is_array($config) ? $config : null;
    }
}
