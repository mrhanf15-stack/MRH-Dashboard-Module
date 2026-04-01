<?php
/**
 * MRH Dashboard - Menüeintrag im Admin unter "Erweiterte Konfiguration"
 * Autoinclude Hook: ~/admin/includes/extra/menu/
 *
 * modified eCommerce Shopsoftware
 * @package MRH Dashboard
 */
defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

if (defined('MODULE_MRH_DASHBOARD_STATUS') && MODULE_MRH_DASHBOARD_STATUS == 'true') {
    $add_contents[BOX_HEADING_CONFIGURATION][] = array(
        'admin_access_name' => 'mrh_dashboard',
        'filename'          => 'mrh_dashboard.php',
        'boxname'           => defined('BOX_MRH_DASHBOARD') ? BOX_MRH_DASHBOARD : 'MRH Dashboard',
        'parameters'        => '',
        'ssl'               => '',
    );
}
