<?php
/**
 * --------------------------------------------------------------
 * File: mrh_dashboard.php
 * Version: 1.0.0
 * Date: 2026-03-31
 *
 * Author: Mr. Hanf Development Team
 * Copyright: (c) 2026 Mr. Hanf
 * Web: https://mr-hanf.at
 * --------------------------------------------------------------
 * MRH Dashboard - Modulares System-Modul für modified eCommerce
 * Ermöglicht die modulare Verwaltung von Template-Funktionen
 * wie Mega-Menü, Banner, Promotions etc.
 * --------------------------------------------------------------
 * Released under the GNU General Public License
 * --------------------------------------------------------------
 */

defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

class mrh_dashboard
{
    const VERSION = '1.0.0';

    public string $code;
    public string $name;
    public string $title;
    public string $description;
    public int $sort_order;
    public bool $enabled;

    public function __construct()
    {
        $this->name        = strtoupper(self::class);
        $this->code        = self::class;
        $this->title       = $this->name . '_TITLE';
        $this->description = $this->name . '_DESC';
        $this->sort_order  = $this->name . '_SORT_ORDER';
        $this->enabled     = defined($this->name . '_STATUS')
                             && 'True' === constant($this->name . '_STATUS');
    }

    /**
     * Prüft ob das Modul installiert ist.
     *
     * @return int
     */
    public function check(): int
    {
        $query   = 'SELECT `configuration_value`
                      FROM `' . TABLE_CONFIGURATION . '`
                     WHERE `configuration_key` = "' . $this->name . '_STATUS"';
        $perform = xtc_db_query($query);
        $result  = xtc_db_fetch_array($perform);

        return (null !== $result) ? 1 : 0;
    }

    /**
     * Konfigurations-Keys die vom Modul verwendet werden.
     *
     * @return array
     */
    public function keys(): array
    {
        return array(
            $this->name . '_VERSION',
            $this->name . '_STATUS',
            $this->name . '_SORT_ORDER',
        );
    }

    /**
     * Installation: Konfigurationswerte in die DB schreiben.
     *
     * @return void
     */
    public function install(): void
    {
        // Konfigurationswerte einfügen
        $query = 'INSERT INTO `' . TABLE_CONFIGURATION . '` (
            `configuration_key`,
            `configuration_value`,
            `configuration_group_id`,
            `sort_order`,
            `date_added`,
            `set_function`
        )
        VALUES
        (
            "' . $this->name . '_VERSION",
            "' . self::VERSION . '",
            6,
            0,
            NOW(),
            "' . self::class . '->configurationFieldVersion"
        ),
        (
            "' . $this->name . '_STATUS",
            "True",
            6,
            1,
            NOW(),
            "xtc_cfg_select_option(array(\'True\', \'False\'),"
        ),
        (
            "' . $this->name . '_SORT_ORDER",
            "0",
            6,
            2,
            NOW(),
            NULL
        )';
        xtc_db_query($query);

        // Eigene Tabelle für Mega-Menü Config erstellen
        $this->createTables();

        // Config-Verzeichnis im Template erstellen
        $tpl_dir = DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/config/';
        if (!is_dir($tpl_dir)) {
            @mkdir($tpl_dir, 0755, true);
        }
    }

    /**
     * Deinstallation: Konfigurationswerte und Tabellen entfernen.
     *
     * @return void
     */
    public function remove(): void
    {
        $keys  = '"' . implode('", "', $this->keys()) . '"';
        $query = 'DELETE FROM `' . TABLE_CONFIGURATION . '`
                        WHERE `configuration_key` IN (' . $keys . ')';
        xtc_db_query($query);

        // Tabellen NICHT löschen bei Deinstallation (Datensicherheit)
        // Nur bei explizitem Wunsch: $this->dropTables();
    }

    /**
     * Update-Logik für Versionswechsel.
     *
     * @return void
     */
    public function update(): void
    {
        // Aktuelle Version aus DB lesen
        $query   = 'SELECT `configuration_value`
                      FROM `' . TABLE_CONFIGURATION . '`
                     WHERE `configuration_key` = "' . $this->name . '_VERSION"';
        $perform = xtc_db_query($query);
        $result  = xtc_db_fetch_array($perform);

        $installed_version = $result['configuration_value'] ?? '0.0.0';

        if (version_compare($installed_version, self::VERSION, '<')) {
            // Hier zukünftige Migrations-Logik einfügen
            // z.B. if (version_compare($installed_version, '1.1.0', '<')) { ... }

            // Version aktualisieren
            $query = 'UPDATE `' . TABLE_CONFIGURATION . '`
                         SET `configuration_value` = "' . self::VERSION . '"
                       WHERE `configuration_key` = "' . $this->name . '_VERSION"';
            xtc_db_query($query);
        }
    }

    /**
     * Wird bei jedem Admin-Seitenaufruf ausgeführt (wenn Modul aktiv).
     *
     * @return void
     */
    public function process(): void
    {
        // Update-Check bei jedem Aufruf
        if ($this->enabled) {
            $this->update();
        }
    }

    /**
     * Zusätzliches HTML in der System-Modul Konfiguration.
     *
     * @return array
     */
    public function display(): array
    {
        return array(
            'text' => implode(
                ' ',
                array(
                    xtc_button(BUTTON_SAVE),
                    xtc_button_link(
                        BUTTON_CANCEL,
                        xtc_href_link(
                            FILENAME_MODULE_EXPORT,
                            'set=' . filter_input(INPUT_GET, 'set') . '&module=' . $this->code
                        )
                    ),
                )
            )
        );
    }

    /**
     * Versionsfeld (readonly) in der Modulkonfiguration.
     *
     * @param string $value
     * @param string $constant
     * @return string
     */
    public function configurationFieldVersion(string $value, string $constant): string
    {
        return xtc_draw_input_field(
            'configuration[' . $constant . ']',
            $value,
            'readonly="true" style="opacity: 0.4;"'
        );
    }

    /**
     * Erstellt die Datenbanktabelle für die Mega-Menü Konfiguration.
     *
     * @return void
     */
    private function createTables(): void
    {
        // Tabelle für Mega-Menü Spalten-Konfiguration
        $query = 'CREATE TABLE IF NOT EXISTS `mrh_megamenu_config` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `parent_category_id` INT(11) NOT NULL,
            `column_index` TINYINT(1) NOT NULL DEFAULT 0,
            `column_title` VARCHAR(255) NOT NULL DEFAULT "",
            `column_icon` VARCHAR(64) NOT NULL DEFAULT "",
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `date_added` DATETIME NOT NULL,
            `last_modified` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `parent_col` (`parent_category_id`, `column_index`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        xtc_db_query($query);

        // Tabelle für Kategorie-Zuordnung zu Spalten
        $query = 'CREATE TABLE IF NOT EXISTS `mrh_megamenu_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `config_id` INT(11) NOT NULL,
            `category_id` INT(11) NOT NULL,
            `custom_label` VARCHAR(255) DEFAULT NULL,
            `custom_url` VARCHAR(512) DEFAULT NULL,
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `config_id` (`config_id`),
            CONSTRAINT `fk_megamenu_config` FOREIGN KEY (`config_id`)
                REFERENCES `mrh_megamenu_config` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        xtc_db_query($query);
    }

    /**
     * Entfernt die Datenbanktabellen (nur bei explizitem Wunsch).
     *
     * @return void
     */
    private function dropTables(): void
    {
        xtc_db_query('DROP TABLE IF EXISTS `mrh_megamenu_items`');
        xtc_db_query('DROP TABLE IF EXISTS `mrh_megamenu_config`');
    }
}
