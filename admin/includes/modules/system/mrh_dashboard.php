<?php
/* -----------------------------------------------------------------------------------------
   $Id: mrh_dashboard.php 1.2.0 2026-03-31 Mr. Hanf $

   MRH Dashboard - Modulares System-Modul fuer modified eCommerce
   https://mr-hanf.at

   Copyright (c) 2026 Mr. Hanf
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class mrh_dashboard {

  const VERSION = '1.2.0';

  var $code;
  var $title;
  var $description;
  var $sort_order;
  var $enabled;
  var $_check;

  function __construct() {
    $this->code        = 'mrh_dashboard';
    $this->title       = defined('MODULE_MRH_DASHBOARD_TEXT_TITLE') ? MODULE_MRH_DASHBOARD_TEXT_TITLE : 'MRH Dashboard';
    $this->description = defined('MODULE_MRH_DASHBOARD_TEXT_DESCRIPTION') ? MODULE_MRH_DASHBOARD_TEXT_DESCRIPTION : 'Modulares Dashboard fuer Mr. Hanf Template-Funktionen.';
    $this->sort_order  = defined('MODULE_MRH_DASHBOARD_SORT_ORDER') ? MODULE_MRH_DASHBOARD_SORT_ORDER : '';
    $this->enabled     = ((defined('MODULE_MRH_DASHBOARD_STATUS') && MODULE_MRH_DASHBOARD_STATUS == 'true') ? true : false);
  }

  function process($file) {
    // Wird bei jedem Admin-Seitenaufruf ausgefuehrt (wenn Modul aktiv)
  }

  function display() {
    return array('text' => '<br /><div align="center">' . xtc_button(BUTTON_SAVE) .
                           xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module=mrh_dashboard')) . "</div>");
  }

  function check() {
    if (!isset($this->_check)) {
      if (defined('MODULE_MRH_DASHBOARD_STATUS')) {
        $this->_check = true;
      } else {
        $check_query = xtc_db_query("SELECT configuration_value
                                       FROM " . TABLE_CONFIGURATION . "
                                      WHERE configuration_key = 'MODULE_MRH_DASHBOARD_STATUS'");
        $this->_check = xtc_db_num_rows($check_query);
      }
    }
    return $this->_check;
  }

  function keys() {
    return array(
      'MODULE_MRH_DASHBOARD_STATUS',
      'MODULE_MRH_DASHBOARD_VERSION',
      'MODULE_MRH_DASHBOARD_SORT_ORDER',
    );
  }

  function install() {
    // Status
    xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_MRH_DASHBOARD_STATUS', 'true', 6, 1, 'xtc_cfg_select_option(array(\'true\', \'false\'),', now())");

    // Version
    xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_MRH_DASHBOARD_VERSION', '" . self::VERSION . "', 6, 2, now())");

    // Sortierreihenfolge
    xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('MODULE_MRH_DASHBOARD_SORT_ORDER', '0', 6, 3, now())");

    // Tabellen erstellen
    $this->_createTables();

    // Standard-Kategorien einfuegen
    $this->_seedDefaultConfig();

    // Standard Nav-Links einfuegen
    $this->_seedDefaultNavLinks();

    // Admin-Access Spalte anlegen
    $this->_addAdminAccess();
  }

  function remove() {
    xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");

    // Admin-Access Spalte entfernen
    $this->_removeAdminAccess();

    // Tabellen NICHT loeschen (Datensicherheit)
    // Bei Bedarf: $this->_dropTables();
  }

  // --- Private Hilfsfunktionen ---

  function _addAdminAccess() {
    $check = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_access' AND COLUMN_NAME = 'mrh_dashboard'");
    if (!xtc_db_fetch_array($check)) {
      xtc_db_query("ALTER TABLE admin_access ADD COLUMN mrh_dashboard INT(1) NOT NULL DEFAULT 1");
    }
  }

  function _removeAdminAccess() {
    $check = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_access' AND COLUMN_NAME = 'mrh_dashboard'");
    if (xtc_db_fetch_array($check)) {
      xtc_db_query("ALTER TABLE admin_access DROP COLUMN mrh_dashboard");
    }
  }

  function _createTables() {
    // Mega-Menu Config mit mehrsprachigen Spalten-Titeln (DE/EN/FR/ES)
    xtc_db_query("CREATE TABLE IF NOT EXISTS mrh_megamenu_config (
      id INT(11) NOT NULL AUTO_INCREMENT,
      parent_category_id INT(11) NOT NULL,
      column_index TINYINT(3) NOT NULL DEFAULT 0,
      column_title_de VARCHAR(255) NOT NULL DEFAULT '',
      column_title_en VARCHAR(255) NOT NULL DEFAULT '',
      column_title_fr VARCHAR(255) NOT NULL DEFAULT '',
      column_title_es VARCHAR(255) NOT NULL DEFAULT '',
      column_icon VARCHAR(64) NOT NULL DEFAULT '',
      sort_order INT(11) NOT NULL DEFAULT 0,
      date_added DATETIME NOT NULL,
      last_modified DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY parent_col (parent_category_id, column_index)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Mega-Menu Items
    xtc_db_query("CREATE TABLE IF NOT EXISTS mrh_megamenu_items (
      id INT(11) NOT NULL AUTO_INCREMENT,
      config_id INT(11) NOT NULL,
      category_id INT(11) NOT NULL,
      custom_label VARCHAR(255) DEFAULT NULL,
      custom_url VARCHAR(512) DEFAULT NULL,
      sort_order INT(11) NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      PRIMARY KEY (id),
      KEY config_id (config_id),
      CONSTRAINT fk_megamenu_config FOREIGN KEY (config_id)
        REFERENCES mrh_megamenu_config (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Nav-Links Tabelle fuer zusaetzliche Navigationslinks
    xtc_db_query("CREATE TABLE IF NOT EXISTS mrh_megamenu_navlinks (
      id INT(11) NOT NULL AUTO_INCREMENT,
      link_url VARCHAR(512) NOT NULL DEFAULT '',
      link_name VARCHAR(255) NOT NULL DEFAULT '',
      link_icon VARCHAR(64) NOT NULL DEFAULT '',
      sort_order INT(11) NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      date_added DATETIME NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Migration: ES-Spalte hinzufuegen falls noch nicht vorhanden
    $this->_migrateAddES();
  }

  function _migrateAddES() {
    // Pruefen ob column_title_es existiert
    $check = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE()
                             AND TABLE_NAME = 'mrh_megamenu_config'
                             AND COLUMN_NAME = 'column_title_es'");
    if (!xtc_db_fetch_array($check)) {
      xtc_db_query("ALTER TABLE mrh_megamenu_config ADD COLUMN column_title_es VARCHAR(255) NOT NULL DEFAULT '' AFTER column_title_fr");
    }

    // Pruefen ob alte column_title Spalte existiert (Migration von v1.0)
    $check_old = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                               WHERE TABLE_SCHEMA = DATABASE()
                                 AND TABLE_NAME = 'mrh_megamenu_config'
                                 AND COLUMN_NAME = 'column_title'");
    if (xtc_db_fetch_array($check_old)) {
      $check_de = xtc_db_query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                                WHERE TABLE_SCHEMA = DATABASE()
                                  AND TABLE_NAME = 'mrh_megamenu_config'
                                  AND COLUMN_NAME = 'column_title_de'");
      if (!xtc_db_fetch_array($check_de)) {
        xtc_db_query("ALTER TABLE mrh_megamenu_config ADD COLUMN column_title_de VARCHAR(255) NOT NULL DEFAULT '' AFTER column_index");
        xtc_db_query("ALTER TABLE mrh_megamenu_config ADD COLUMN column_title_en VARCHAR(255) NOT NULL DEFAULT '' AFTER column_title_de");
        xtc_db_query("ALTER TABLE mrh_megamenu_config ADD COLUMN column_title_fr VARCHAR(255) NOT NULL DEFAULT '' AFTER column_title_en");
        xtc_db_query("ALTER TABLE mrh_megamenu_config ADD COLUMN column_title_es VARCHAR(255) NOT NULL DEFAULT '' AFTER column_title_fr");
        xtc_db_query("UPDATE mrh_megamenu_config SET column_title_de = column_title, column_title_en = column_title, column_title_fr = column_title, column_title_es = column_title");
        xtc_db_query("ALTER TABLE mrh_megamenu_config DROP COLUMN column_title");
      }
    }
  }

  function _seedDefaultConfig() {
    // Pruefen ob bereits Daten vorhanden
    $check = xtc_db_query("SELECT COUNT(*) as cnt FROM mrh_megamenu_config");
    $row = xtc_db_fetch_array($check);
    if ($row['cnt'] > 0) return;

    $now = date('Y-m-d H:i:s');

    // ============================================================
    // SAMEN SHOP (parent_category_id = 581210)
    // 3 Spalten wie im Testshop
    // ============================================================

    // Spalte 1: Cannabis Samen kaufen
    $this->_insertColumn(581210, 0, 'Cannabis Samen kaufen', 'Buy Cannabis Seeds', 'Acheter des Graines', 'Comprar Semillas', 'fa-leaf', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581346, 0); // Feminisierte Samen
    $this->_insertItem($config_id, 58000,  1); // Autoflowering Samen
    $this->_insertItem($config_id, 581002, 2); // Reguläre Samen
    $this->_insertItem($config_id, 581868, 3); // F1 Cannabis Sorten
    $this->_insertItem($config_id, 581291, 4); // CBD-Reiche Sorten

    // Spalte 2: Beliebte Auswahl
    $this->_insertColumn(581210, 1, 'Beliebte Auswahl', 'Popular Selection', 'Sélection Populaire', 'Selección Popular', 'fa-diamond', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581882, 0); // Top-Seller
    $this->_insertItem($config_id, 581866, 1); // Anfänger Samen
    $this->_insertItem($config_id, 581290, 2); // THC-Reiche Sorten
    $this->_insertItem($config_id, 581829, 3); // USA Genetik
    $this->_insertItem($config_id, 581883, 4); // Klassiker

    // Spalte 3: Anbau & Spezial
    $this->_insertColumn(581210, 2, 'Anbau & Spezial', 'Growing & Special', 'Culture & Spécial', 'Cultivo & Especial', 'fa-pagelines', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581894, 0); // Reine Indoor Samen
    $this->_insertItem($config_id, 581895, 1); // Reine Outdoor Samen
    $this->_insertItem($config_id, 581867, 2); // Fast Flowering Samen
    $this->_insertItem($config_id, 58002,  3); // Medizinische Samen
    $this->_insertItem($config_id, 581858, 4); // Bulk Samen

    // ============================================================
    // CANNABISPFLANZEN (parent_category_id = 581964)
    // 1 Spalte wie im Testshop
    // ============================================================

    // Spalte 1: Pflanzen kaufen
    $this->_insertColumn(581964, 0, 'Pflanzen kaufen', 'Buy Plants', 'Acheter des Plantes', 'Comprar Plantas', 'fa-pagelines', $now);
    $config_id = xtc_db_insert_id();
    // Sämlinge - ID wird beim Deploy ermittelt, vorerst leer

    // ============================================================
    // GROWSHOP (parent_category_id = 195)
    // 3 Spalten wie im Testshop
    // ============================================================

    // Spalte 1: Grow Grundausstattung
    $this->_insertColumn(195, 0, 'Grow Grundausstattung', 'Grow Basics', 'Équipement de Base', 'Equipamiento Básico', 'fa-cogs', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581322, 0); // Beleuchtungstechnik
    $this->_insertItem($config_id, 581961, 1); // Growbox Zubehör
    $this->_insertItem($config_id, 581201, 2); // Growboxen & Growzelte
    $this->_insertItem($config_id, 581186, 3); // Komplett-Sets
    $this->_insertItem($config_id, 581185, 4); // Töpfe und Behälter

    // Spalte 2: Nährstoffe & Pflege
    $this->_insertColumn(195, 1, 'Nährstoffe & Pflege', 'Nutrients & Care', 'Nutriments & Soins', 'Nutrientes & Cuidado', 'fa-tint', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581959, 0); // Anzucht & Propagation
    $this->_insertItem($config_id, 581200, 1); // Schädlingsbekämpfung
    $this->_insertItem($config_id, 581202, 2); // Erden & Substrate
    $this->_insertItem($config_id, 581817, 3); // Bewässerung
    $this->_insertItem($config_id, 581203, 4); // Dünger

    // Spalte 3: Zubehör & Ernte
    $this->_insertColumn(195, 2, 'Zubehör & Ernte', 'Accessories & Harvest', 'Accessoires & Récolte', 'Accesorios & Cosecha', 'fa-wrench', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581779, 0); // Ernte u. Verarbeitung
    $this->_insertItem($config_id, 581187, 1); // Lüftung - Klima

    // ============================================================
    // HEADSHOP (parent_category_id = 581287)
    // 2 Spalten wie im Testshop
    // ============================================================

    // Spalte 1: Rauchen & Dampfen
    $this->_insertColumn(581287, 0, 'Rauchen & Dampfen', 'Smoking & Vaping', 'Fumer & Vapoter', 'Fumar & Vapear', 'fa-cloud', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 581280, 0); // Terpene Shop
    $this->_insertItem($config_id, 581314, 1); // Bongs
    $this->_insertItem($config_id, 581818, 2); // Pfeifen
    $this->_insertItem($config_id, 581173, 3); // Verdampfer - Vaporizer
    $this->_insertItem($config_id, 210,    4); // Waagen

    // Spalte 2: Zubehör & Tools
    $this->_insertColumn(581287, 1, 'Zubehör & Tools', 'Accessories & Tools', 'Accessoires & Outils', 'Accesorios & Herramientas', 'fa-wrench', $now);
    $config_id = xtc_db_insert_id();
    $this->_insertItem($config_id, 211,    0); // Zubehör
    $this->_insertItem($config_id, 581822, 1); // Bücher & Multimedia
    $this->_insertItem($config_id, 209,    2); // Grinder
    $this->_insertItem($config_id, 581842, 3); // Mischtabletts
    $this->_insertItem($config_id, 581812, 4); // Verarbeitung & Extraktion
  }

  function _seedDefaultNavLinks() {
    // Pruefen ob bereits Nav-Links vorhanden
    $check = xtc_db_query("SELECT COUNT(*) as cnt FROM mrh_megamenu_navlinks");
    $row = xtc_db_fetch_array($check);
    if ($row['cnt'] > 0) return;

    $now = date('Y-m-d H:i:s');

    // Standard Nav-Links wie im Testshop
    // Name mit MRH_ Prefix = Sprachkonstante, ohne = fester Text
    $links = array(
      array('specials.php', 'MRH_NAV_ANGEBOTE', 'fa-tag', 1),
      array('products_new.php', 'MRH_NAV_NEUE_ARTIKEL', 'fa-star', 2),
      array('shop.php?do=CreateManufacturersList', 'MRH_NAV_MARKEN', 'fa-users', 3),
      array('blog.php', 'MRH_NAV_BLOG', 'fa-newspaper-o', 4),
      array('seedfinder.php', 'MRH_NAV_SEEDFINDER', 'fa-search', 5),
    );

    foreach ($links as $link) {
      xtc_db_query("INSERT INTO mrh_megamenu_navlinks
        (link_url, link_name, link_icon, sort_order, is_active, date_added)
        VALUES (
          '" . xtc_db_input($link[0]) . "',
          '" . xtc_db_input($link[1]) . "',
          '" . xtc_db_input($link[2]) . "',
          " . (int)$link[3] . ",
          1,
          '" . $now . "'
        )");
    }
  }

  /**
   * Hilfsfunktion: Spalte einfuegen (4 Sprachen: DE/EN/FR/ES)
   */
  function _insertColumn($parent_id, $col_index, $title_de, $title_en, $title_fr, $title_es, $icon, $now) {
    xtc_db_query("INSERT INTO mrh_megamenu_config
      (parent_category_id, column_index, column_title_de, column_title_en, column_title_fr, column_title_es, column_icon, sort_order, date_added, last_modified)
      VALUES (
        " . (int)$parent_id . ",
        " . (int)$col_index . ",
        '" . xtc_db_input($title_de) . "',
        '" . xtc_db_input($title_en) . "',
        '" . xtc_db_input($title_fr) . "',
        '" . xtc_db_input($title_es) . "',
        '" . xtc_db_input($icon) . "',
        " . (int)$col_index . ",
        '" . $now . "',
        '" . $now . "'
      )");
  }

  /**
   * Hilfsfunktion: Item einfuegen
   */
  function _insertItem($config_id, $category_id, $sort_order) {
    xtc_db_query("INSERT INTO mrh_megamenu_items
      (config_id, category_id, sort_order, is_active)
      VALUES (
        " . (int)$config_id . ",
        " . (int)$category_id . ",
        " . (int)$sort_order . ",
        1
      )");
  }

  function _dropTables() {
    xtc_db_query("DROP TABLE IF EXISTS mrh_megamenu_items");
    xtc_db_query("DROP TABLE IF EXISTS mrh_megamenu_config");
    xtc_db_query("DROP TABLE IF EXISTS mrh_megamenu_navlinks");
  }
}
