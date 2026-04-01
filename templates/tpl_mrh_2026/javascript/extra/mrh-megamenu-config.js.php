<?php
/* -----------------------------------------------------------------------------------------
   $Id: mrh-megamenu-config.js.php 1.3.0 2026-03-31 Mr. Hanf $

   MRH Mega-Menu Config - Frontend JavaScript Output
   Autoinclude Hook: ~/templates/YOUR_TEMPLATE/javascript/extra/

   Liest Cache-Datei und gibt NUR eingetragene Links als JS-Objekt aus.
   Unterstuetzt DE/EN/FR/ES + Nav-Links mit MRH_-Sprachkonstanten.
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

// Pruefen ob Modul aktiv
if (!defined('MODULE_MRH_DASHBOARD_STATUS') || MODULE_MRH_DASHBOARD_STATUS !== 'true') {
    return;
}

// Sprach-Mapping: language_id => code
$lang_map = array(2 => 'de', 1 => 'en', 5 => 'fr', 7 => 'es');
$active_lang = isset($lang_map[(int)($_SESSION['languages_id'] ?? 2)])
    ? $lang_map[(int)($_SESSION['languages_id'] ?? 2)]
    : 'de';

// Cache-Datei lesen
$cache_file = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/megamenu_config.json';

if (!file_exists($cache_file)) {
    return;
}

$json_raw = file_get_contents($cache_file);
$cache = json_decode($json_raw, true);

if (!is_array($cache) || empty($cache)) {
    return;
}

// Mega-Menu Konfiguration aufbereiten
// Cache-Keys: 'categories' und 'navlinks' (aus MrhMegaMenuManager::regenerateCache)
$megamenu_entries = array();
if (isset($cache['categories'])) {
    $megamenu_entries = $cache['categories'];
} elseif (isset($cache['megamenu'])) {
    $megamenu_entries = $cache['megamenu'];
}
$nav_links = array();
if (isset($cache['navlinks'])) {
    $nav_links = $cache['navlinks'];
} elseif (isset($cache['nav_links'])) {
    $nav_links = $cache['nav_links'];
}

$output_megamenu = array();

foreach ($megamenu_entries as $entry) {
    if (!isset($entry['columns']) || !is_array($entry['columns'])) continue;

    $columns_out = array();
    foreach ($entry['columns'] as $col) {
        // Sprachspezifischen Titel waehlen
        $title = '';
        if (isset($col['titles'][$active_lang]) && $col['titles'][$active_lang] !== '') {
            $title = $col['titles'][$active_lang];
        } elseif (isset($col['titles']['de'])) {
            $title = $col['titles']['de'];
        } elseif (isset($col['title'])) {
            $title = $col['title'];
        }

        // Nur Spalten mit Titel oder Items ausgeben
        $items_out = array();
        if (isset($col['items']) && is_array($col['items'])) {
            foreach ($col['items'] as $item) {
                // Sprachspezifisches Label
                $label = '';
                if (isset($item['labels'][$active_lang]) && $item['labels'][$active_lang] !== '') {
                    $label = $item['labels'][$active_lang];
                } elseif (isset($item['labels']['de'])) {
                    $label = $item['labels']['de'];
                } elseif (isset($item['label'])) {
                    $label = $item['label'];
                }

                // Nur Items mit Label ausgeben
                if ($label === '') continue;

                // v1.3.0: Sprachspezifische URL aus 'urls' Feld waehlen
                $item_url = '';
                if (isset($item['urls'][$active_lang]) && $item['urls'][$active_lang] !== '') {
                    $item_url = $item['urls'][$active_lang];
                } elseif (isset($item['urls']['de'])) {
                    $item_url = $item['urls']['de'];
                } elseif (isset($item['url'])) {
                    $item_url = $item['url'];
                }

                $items_out[] = array(
                    'category_id' => (int)$item['category_id'],
                    'label'       => $label,
                    'cpath'       => isset($item['cpath']) ? $item['cpath'] : '',
                    'url'         => $item_url,
                );
            }
        }

        // Nur Spalten mit mindestens einem Item ausgeben
        if (empty($items_out) && $title === '') continue;

        $columns_out[] = array(
            'title' => $title,
            'icon'  => isset($col['icon']) ? $col['icon'] : '',
            'items' => $items_out,
        );
    }

    // Nur Eintraege mit mindestens einer Spalte ausgeben
    if (empty($columns_out)) continue;

    // Sprachspezifischer Parent-Name
    $parent_name = '';
    if (isset($entry['parent_names'][$active_lang]) && $entry['parent_names'][$active_lang] !== '') {
        $parent_name = $entry['parent_names'][$active_lang];
    } elseif (isset($entry['parent_names']['de'])) {
        $parent_name = $entry['parent_names']['de'];
    } elseif (isset($entry['parent_name'])) {
        $parent_name = $entry['parent_name'];
    }

    $output_megamenu[] = array(
        'parent_id'   => (int)$entry['parent_id'],
        'parent_name' => $parent_name,
        'columns'     => $columns_out,
    );
}

// Nav-Links aufbereiten - MRH_-Konstanten aufloesen
$output_navlinks = array();
foreach ($nav_links as $link) {
    if (!isset($link['is_active']) || !$link['is_active']) continue;

    $name = isset($link['name']) ? $link['name'] : '';

    // MRH_-Konstante aufloesen
    if (strpos($name, 'MRH_') === 0 && defined($name)) {
        $name = constant($name);
    }

    if ($name === '') continue;

    $output_navlinks[] = array(
        'url'  => isset($link['url']) ? $link['url'] : '',
        'name' => $name,
        'icon' => isset($link['icon']) ? $link['icon'] : '',
    );
}

// JavaScript ausgeben
echo "\n/* MRH Mega-Menu Config (Dashboard v1.2.0) */\n";
echo "window.MRH_MEGAMENU_CONFIG = " . json_encode($output_megamenu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
echo "window.MRH_MEGAMENU_NAVLINKS = " . json_encode($output_navlinks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
echo "window.MRH_MEGAMENU_LANG = " . json_encode($active_lang) . ";\n";
