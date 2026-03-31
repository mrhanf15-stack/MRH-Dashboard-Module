<?php
/**
 * MRH Dashboard - Menüeintrag im Admin unter "Hilfsprogramme"
 * Autoinclude Hook: ~/admin/includes/extra/menu/
 */
defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

if (defined('MODULE_MRH_DASHBOARD_STATUS') && 'true' === strtolower(MODULE_MRH_DASHBOARD_STATUS)) {
    $add_contents[BOX_HEADING_TOOLS][] = array(
        'admin_access_name' => 'mrh_dashboard',
        'filename'          => 'mrh_dashboard.php',
        'boxname'           => defined('BOX_MRH_DASHBOARD') ? BOX_MRH_DASHBOARD : 'MRH Dashboard',
        'parameters'        => '',
        'ssl'               => '',
    );
}
