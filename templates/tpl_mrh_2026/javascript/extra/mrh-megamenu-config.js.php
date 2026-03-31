<?php
/**
 * --------------------------------------------------------------
 * MRH Mega-Menü Config - Frontend JS Output
 * --------------------------------------------------------------
 * Autoinclude Hook: ~/templates/YOUR_TEMPLATE/javascript/extra/
 * Wird automatisch über general_bottom.js.php → auto_include() geladen.
 *
 * Liest die Cache-Datei (megamenu_config.json) und gibt sie als
 * window.MRH_MEGAMENU_CONFIG JavaScript-Objekt aus.
 *
 * Die Cache-Datei wird vom Admin-Panel (Mega-Menü Manager)
 * bei jedem Speichern neu generiert.
 * --------------------------------------------------------------
 */

// Prüfen ob das Modul aktiv ist
if (!defined('MODULE_MRH_DASHBOARD_STATUS') || 'true' !== strtolower(MODULE_MRH_DASHBOARD_STATUS)) {
    return;
}

// Cache-Datei lesen
$cache_file = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/megamenu_config.json';

if (file_exists($cache_file)) {
    $json = file_get_contents($cache_file);
    $config = json_decode($json, true);

    if (is_array($config) && !empty($config)) {
        echo "\n/* MRH Mega-Menü Config (Dashboard) */\n";
        echo "window.MRH_MEGAMENU_CONFIG = " . json_encode($config, JSON_UNESCAPED_UNICODE) . ";\n";
    }
}
