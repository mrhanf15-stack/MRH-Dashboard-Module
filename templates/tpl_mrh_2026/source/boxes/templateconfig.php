<?php
/*
-----------------------------------------------------------------------------------------
 REVPLUS - TEMPLATEKONFIGURATOR
 FUER FARBEN UND LAYOUT-ELEMENTE

 IN DER DEMO IMMER SICHTBAR, BEI LIVESHOPS NUR FUER ADMINS EINSEHBAR.
 BESONDERHEIT IN DEMO: ZURUECKSETZEN DER WERTE DURCH CRONJOB (ALLE 30 MIN)

 GEFIXT: Speichert nur Farb-Keys (nicht ganzes $_POST mit Submit-Button)
 ERWEITERT: Sekundärfarbe (tpl-secondary-color) hinzugefügt
-----------------------------------------------------------------------------------------
*/

/**
 * Die Box soll entweder nur dann ausgefuehrt werden,
 * wenn wir uns innerhalb der RevPLUS Demo-Installation
 * befinden, oder der eingeloggte Nutzer ein Administra-
 * tor ist.
 * @see https://www.modifiedtemplate.de/demo-revplus/
 */
if (stristr($_SERVER['SERVER_NAME'], 'modifiedtemplate.de') || (isset($_SESSION['customers_status']['customers_status']) && $_SESSION['customers_status']['customers_status'] == 0)) {
        // INCLUDE SMARTY
        include(DIR_FS_BOXES_INC .'smarty_default.php');
        defined('JSON_PRETTY_PRINT') or define('JSON_PRETTY_PRINT', FALSE);

        // SET FILE PATHS
        $myTplFilePath           = DIR_FS_CATALOG .'templates/'. CURRENT_TEMPLATE .'/';
        $fileDefaultColors       = $myTplFilePath .'config/default_colors.json';      // COLOR DEFAULT
        $fileCustomColors        = $myTplFilePath .'config/colors.json';              // COLOR CUSTOM
        $fileDefaultTplSettings  = $myTplFilePath .'config/default_tplsettings.json'; // SETTINGS DEFAULT
        $fileTplSettings         = $myTplFilePath .'config/tplsettings.json';         // SETTINGS CUSTOM
        $fileDefaultLogos        = $myTplFilePath .'config/default_logos.json';       // LOGOS DEFAULT
        $fileCustomLogos         = $myTplFilePath .'config/logos.json';               // LOGOS CUSTOM
        $fileDefaultSocial       = $myTplFilePath .'config/default_social.json';      // SOCIAL DEFAULT
        $fileCustomSocial        = $myTplFilePath .'config/social.json';              // SOCIAL CUSTOM

        // GET DEFAULT COLOR CONFIGURATION
        $jsonDefaultColors = file_get_contents($fileDefaultColors,0);
        $arrDefaultColors  = json_decode($jsonDefaultColors, TRUE);

        // ======================================================================
        // Liste aller gültigen Farb-Keys (nur diese werden gespeichert)
        // ======================================================================
        $validColorKeys = [
                'tpl-main-color',
                'tpl-main-color-2',
                'tpl-secondary-color',
                'tpl-bg-color',
                'tpl-bg-color-2',
                'tpl-bg-productbox',
                'tpl-bg-footer',
                'tpl-text-standard',
                'tpl-text-headings',
                'tpl-text-button',
                'tpl-text-footer',
                'tpl-text-footer-headings',
                'tpl-menu-bg',
                'tpl-menu-hover',
                'tpl-menu-text',
                'tpl-menu-active',
                'tpl-topbar-bg',
                'tpl-topbar-text',
                'tpl-sticky-bg',
                'tpl-sticky-text',
        ];

        /**
         * 1.
         *
         * KONFIGURATION SCHREIBEN
         *
         * Wenn die Formulare abgeschickt wurden, werden die
         * JSON-Dateien neu beschrieben. Das Beschreiben der Da-
         * teien wurde nach oben geschoben, damit nach dem Spei-
         * chern die neuen Farben korrekt ausgelesen werden und
         * keine Alt-Farben in der Konfigurationsbox ausgegeben
         * werden.
         *
         * FIX: Nur gültige Farb-Keys speichern, nicht das gesamte $_POST
         */
        if (isset($_POST['submit-colorsettings'])) {
                $saveColors = [];
                foreach ($validColorKeys as $key) {
                        if (isset($_POST[$key]) && !empty(trim($_POST[$key]))) {
                                $saveColors[$key] = trim($_POST[$key]);
                        } elseif (isset($arrDefaultColors[$key])) {
                                $saveColors[$key] = $arrDefaultColors[$key];
                        }
                }
                $jsonCustomColors = json_encode($saveColors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($fileCustomColors, $jsonCustomColors);
        }

        if (isset($_POST['submit-tplsettings'])) {
                // Nur bekannte Settings-Keys speichern
                $validTplKeys = ['tpl_cfg_ssl', 'tpl_cfg_ts', 'tpl_cfg_menu', 'tpl_cfg_infinitescroll', 'tpl_cfg_barrierefreiTool'];
                $saveTpl = [];
                foreach ($validTplKeys as $key) {
                        if (isset($_POST[$key])) {
                                $saveTpl[$key] = trim($_POST[$key]);
                        }
                }
                $jsonTplSettings = json_encode($saveTpl, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($fileTplSettings, $jsonTplSettings);
        }

        if (isset($_POST['submit-logosettings'])) {
                // Nur Logo-Arrays speichern
                $saveLogos = [
                        'tpl_cfg_payment_logos'  => isset($_POST['tpl_cfg_payment_logos']) ? $_POST['tpl_cfg_payment_logos'] : [],
                        'tpl_cfg_shipping_logos' => isset($_POST['tpl_cfg_shipping_logos']) ? $_POST['tpl_cfg_shipping_logos'] : [],
                ];
                $jsonLogoSettings = json_encode($saveLogos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($fileCustomLogos, $jsonLogoSettings);
        }

        if (isset($_POST['submit-socialsettings'])) {
                // Nur Social-Media-Keys speichern
                $validSocialKeys = ['facebook', 'twitter', 'instagram', 'pinterest', 'linkedin', 'tiktok', 'youtube'];
                $saveSocial = [];
                foreach ($validSocialKeys as $key) {
                        $saveSocial[$key] = isset($_POST[$key]) ? filter_var(trim($_POST[$key]), FILTER_SANITIZE_URL) : '';
                }
                $jsonSocialSettings = json_encode($saveSocial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($fileCustomSocial, $jsonSocialSettings);
        }

        /**
         * 2.
         *
         * TEMPLATE DEMO
         *
         * In der RevPLUS-Standard-Installation werden alle Kon-
         * figurationswerte mithilfe eines angelegten CronJobs
         * alle 30 Minuten auf den Standard zurueckgesetzt. Das
         * sollte bei einer Kunden-Installation natuerlich nicht
         * passieren, daher wird der SERVER_NAME hier erneut ab-
         * gefragt.
         */
        if (stristr($_SERVER['SERVER_NAME'], 'modifiedtemplate.de') && isset($_GET['reset-revplus'])) {
                // RESET COLORS
                file_put_contents($fileCustomColors, $jsonDefaultColors);

                // RESET TPL SETTINGS
                $jsonDefaultTplSettings = file_get_contents($fileDefaultTplSettings,0);
                file_put_contents($fileTplSettings, $jsonDefaultTplSettings);

                // RESET LOGOS SETTINGS
                if (file_exists($fileDefaultLogos)) {
                        $jsonDefaultLogos = file_get_contents($fileDefaultLogos,0);
                        file_put_contents($fileCustomLogos, $jsonDefaultLogos);
                }

                // RESET SOCIAL SETTINGS
                if (file_exists($fileDefaultSocial)) {
                        $jsonDefaultSocial = file_get_contents($fileDefaultSocial,0);
                        file_put_contents($fileCustomSocial, $jsonDefaultSocial);
                }
        }

        /**
         * 3.
         *
         * TEMPLATE RENDERN
         *
         * Als letztes wird das Template gerendert. Die Konfigu-
         * rationswerte werden dabei frisch aus den jeweiligen
         * JSON-Dateien ausgelesen.
         */
        // GET CUSTOMER'S COLORS CONFIGURATION
        $jsonCustomColors  = file_get_contents($fileCustomColors,0);
        $arrCustomColors   = json_decode($jsonCustomColors, TRUE);

        // MERGE DEFAULT COLORS WITH CUSTOM COLORS
        if (is_array($arrCustomColors) && count($arrCustomColors)) {
                $arrCustomColors = array_merge($arrDefaultColors, $arrCustomColors);
        }
        else {
                $arrCustomColors = $arrDefaultColors;
        }

        // GET CUSTOMER'S LOGOS CONFIGURATION
        $arrCustomLogos = [];

        if (file_exists($fileCustomLogos)) {
                $jsonCustomLogos = file_get_contents($fileCustomLogos, 0);
                $arrCustomLogos = json_decode($jsonCustomLogos, TRUE);
        }

        // DEFAULT LOGOS WENN KEINE KONFIGURATION EXISTIERT
        $defaultPaymentLogos = ['vorkasse', 'paypal', 'kreditkarten', 'applepay', 'googlepay', 'amazon', 'klarna', 'lastschrift', 'rechnung'];
        $defaultShippingLogos = ['hermes', 'dhl', 'dpd', 'ups', 'gls', 'fedex'];

        $tpl_cfg_payment_logos = isset($arrCustomLogos['tpl_cfg_payment_logos']) ? $arrCustomLogos['tpl_cfg_payment_logos'] : $defaultPaymentLogos;
        $tpl_cfg_shipping_logos = isset($arrCustomLogos['tpl_cfg_shipping_logos']) ? $arrCustomLogos['tpl_cfg_shipping_logos'] : $defaultShippingLogos;

        // GET CUSTOMER'S SOCIAL MEDIA CONFIGURATION
        $arrCustomSocial = [];

        if (file_exists($fileCustomSocial)) {
                $jsonCustomSocial = file_get_contents($fileCustomSocial, 0);
                $arrCustomSocial = json_decode($jsonCustomSocial, TRUE);
        }

        // DEFAULT SOCIAL MEDIA LINKS WENN KEINE KONFIGURATION EXISTIERT
        $defaultSocialLinks = [
                'facebook' => '',
                'twitter' => '',
                'instagram' => '',
                'pinterest' => '',
                'linkedin' => '',
                'tiktok' => '',
                'youtube' => '',
        ];

        $tpl_cfg_social_links = array_merge($defaultSocialLinks, $arrCustomSocial);

        // ASSIGN TEMPLATE VARIABLES
        $box_smarty->assign(array(
                'tpl_cfg_colors' => $arrCustomColors,
                'tpl_cfg_ts'     => $smarty->get_template_vars('tpl_cfg_ts'),
                'tpl_cfg_ssl'    => $smarty->get_template_vars('tpl_cfg_ssl'),
                'tpl_cfg_menu'   => $smarty->get_template_vars('tpl_cfg_menu'),
                'tpl_cfg_infinitescroll'   => $smarty->get_template_vars('tpl_cfg_infinitescroll'),
                'tpl_cfg_barrierefreiTool'   => $smarty->get_template_vars('tpl_cfg_barrierefreiTool'),
                'tpl_cfg_payment_logos' => $tpl_cfg_payment_logos,
                'tpl_cfg_shipping_logos' => $tpl_cfg_shipping_logos,
                'tpl_cfg_social_links' => $tpl_cfg_social_links,
        ));

        $box_templateconfig = $box_smarty->fetch(CURRENT_TEMPLATE .'/boxes/box_templateconfig.html');
        $smarty->assign('box_templateconfig', $box_templateconfig);

        // Seitenreload nach erfolgreicher Konfiguration (ohne exit)
        if (isset($_POST['submit-colorsettings']) ||
                isset($_POST['submit-tplsettings']) ||
                isset($_POST['submit-logosettings']) ||
                isset($_POST['submit-socialsettings'])) {

                // Redirect ohne exit - Browser macht automatisch neuen GET-Request
                echo '<script>setTimeout(function(){window.location.href = window.location.pathname + window.location.search;}, 300);</script>';
        }
}

?>
