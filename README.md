# MRH Dashboard – modified eCommerce System-Modul

Modulares Dashboard für Mr. Hanf Template-Funktionen. Erstes Modul: **Mega-Menü Manager**.

## Architektur

Dieses Modul folgt der offiziellen [modified eCommerce Autoinclude-Architektur](https://www.modified-shop.org/wiki/Auto_include_Modul_System):

- **System-Modul**: Installation/Deinstallation über Admin → Module → System-Module
- **Autoinclude-Hooks**: Menüeintrag, Filenames, Sprachdateien – alles über `extra/` Ordner
- **Keine Core-Dateien geändert**: Updatesicher, alle Dateien in `extra/` oder eigenen Ordnern
- **Vanilla JS**: Kein jQuery, modernes ES2020+ JavaScript

## Dateistruktur

Alle Pfade relativ zum **Shop-Root** (z.B. `/home/www/doc/.../mr-hanf.at/www/`):

```
admin/
  mrh_dashboard.php                              ← Admin-Seite (Haupt-UI)
  includes/
    modules/system/
      mrh_dashboard.php                          ← System-Modul Klasse (Install/Deinstall)
    extra/
      menu/mrh_dashboard.php                     ← Menüeintrag unter "Hilfsprogramme"
      filenames/mrh_dashboard.php                ← FILENAME Konstante

includes/
  external/
    mrh_dashboard/
      MrhMegaMenuManager.php                     ← Backend-Logik (Kategorien, Config, Cache)

lang/
  german/extra/admin/mrh_dashboard.php           ← Deutsche Sprachkonstanten
  english/extra/admin/mrh_dashboard.php          ← Englische Sprachkonstanten

templates/tpl_mrh_2026/
  javascript/extra/
    mrh-megamenu-config.js.php                   ← Frontend JS Output (auto_include)
  config/
    megamenu_config.json                         ← Cache-Datei (wird automatisch generiert)
```

## Installation

### Schritt 1: Dateien kopieren

Alle Dateien aus diesem Repo in den Shop-Root kopieren (Ordnerstruktur beibehalten).

### Schritt 2: DB-Tabellen erstellen

Im phpMyAdmin oder per SSH folgenden SQL ausführen:

```sql
CREATE TABLE IF NOT EXISTS `mrh_megamenu_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `parent_category_id` INT(11) NOT NULL,
  `column_index` INT(2) NOT NULL DEFAULT 0,
  `column_title` VARCHAR(255) NOT NULL DEFAULT '',
  `column_icon` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT(5) NOT NULL DEFAULT 0,
  `date_added` DATETIME DEFAULT NULL,
  `last_modified` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mrh_megamenu_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `config_id` INT(11) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `custom_label` VARCHAR(255) DEFAULT NULL,
  `custom_url` VARCHAR(500) DEFAULT NULL,
  `sort_order` INT(5) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_config` (`config_id`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_megamenu_config` FOREIGN KEY (`config_id`)
    REFERENCES `mrh_megamenu_config` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Schritt 3: Modul aktivieren

1. Admin → Module → System-Module
2. "MRH Dashboard" finden → Installieren
3. Status wird auf "True" gesetzt

### Schritt 4: Mega-Menü konfigurieren

1. Admin → Hilfsprogramme → MRH Dashboard
2. Hauptkategorie als Tab auswählen (z.B. "Samen Shop")
3. Spalten hinzufügen, Überschriften vergeben
4. Unterkategorien per Drag & Drop oder Dropdown zuordnen
5. Speichern → Frontend-Cache wird automatisch regeneriert

## Funktionsweise

### Datenfluss

```
Admin-UI → PHP speichert in DB → Cache-JSON wird generiert
                                        ↓
Frontend: mrh-megamenu-config.js.php liest Cache → window.MRH_MEGAMENU_CONFIG
                                        ↓
mrh-core.js.php: buildDropdown() prüft Dashboard-Config → Fallback auf Hardcoded
```

### Prioritätskette im Frontend

1. **Dashboard-Config** (`window.MRH_MEGAMENU_CONFIG`) → System-URLs mit `cPath`
2. **Fallback: Hardcoded Config** (`getCategoryConfig()`) → Statische Links oder Keyword-Zuordnung

### URLs

Das Dashboard generiert **System-URLs** (`index.php?cPath=581210_581211`), nicht SEO-URLs. Das SEO-Modul von modified eCommerce schreibt diese automatisch um. Vorteile:

- Funktioniert auch wenn sich Kategorienamen ändern
- Unabhängig von der URL-Rewrite-Konfiguration
- Zukunftssicher

## Erweiterbarkeit

Das Dashboard ist modular aufgebaut. Weitere Module können hinzugefügt werden:

- Banner-Manager
- Promo-Konfigurator
- SEO-Tools
- Newsletter-Konfiguration

Jedes Modul nutzt die gleiche Architektur: eigene DB-Tabellen, eigene Admin-UI, eigener Frontend-Output.

## Voraussetzungen

- modified eCommerce 3.x
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Template: tpl_mrh_2026

## Version

- **v1.0.0** (2026-03-31) – Erstveröffentlichung mit Mega-Menü Manager
