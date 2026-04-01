# MRH Dashboard Module v1.1.0

Modulares Dashboard für **modified eCommerce v2.0.7.2** mit dem Template `tpl_mrh_2026`.

## Features

- **Mega-Menü Manager** mit Drag & Drop Sortierung
- **Mehrsprachige Spaltenüberschriften** (DE/EN/FR) pro Spalte
- **Font Awesome 4.7 Icon-Picker** mit Suchfunktion
- **Standard-Kategorien** werden bei Installation automatisch eingefügt (Samen Shop, Growshop, Headshop)
- **JSON-Cache** für schnelle Frontend-Ausgabe
- **System-Modul** unter Erweiterte Konfiguration

## Voraussetzungen

- modified eCommerce v2.0.7.2+
- PHP 8.1+
- Template: `tpl_mrh_2026`
- Font Awesome 4.7 (wird per CDN geladen)

## Dateistruktur

```
admin/
  mrh_dashboard.php                              ← Admin-Seite (Dashboard UI)
  includes/
    modules/system/mrh_dashboard.php             ← System-Modul Klasse
    extra/
      menu/mrh_dashboard.php                     ← Menüeintrag (Erw. Konfiguration)
      filenames/mrh_dashboard.php                ← FILENAME Konstante
includes/
  external/mrh_dashboard/
    MrhMegaMenuManager.php                       ← Backend-Logik
lang/
  german/
    extra/admin/mrh_dashboard.php                ← DE Admin-Sprachkonstanten
    modules/system/mrh_dashboard.php             ← DE System-Modul Sprachkonstanten
  english/
    extra/admin/mrh_dashboard.php                ← EN Admin-Sprachkonstanten
    modules/system/mrh_dashboard.php             ← EN System-Modul Sprachkonstanten
  french/
    extra/admin/mrh_dashboard.php                ← FR Admin-Sprachkonstanten
    modules/system/mrh_dashboard.php             ← FR System-Modul Sprachkonstanten
templates/tpl_mrh_2026/
  javascript/extra/mrh-megamenu-config.js.php    ← Frontend JS Output (mehrsprachig)
```

## Installation

DB-Tabellen werden automatisch bei Installation erstellt.

1. Dateien in den Shop-Root kopieren (Ordnerstruktur beibehalten)
2. Admin → Module → System-Module → MRH Dashboard → Installieren
3. Erweiterte Konfiguration → MRH Dashboard

## Changelog

### v1.1.0 (2026-03-31)
- Mehrsprachige Spaltenüberschriften (DE/EN/FR)
- Font Awesome 4.7 Icon-Picker mit Suchfunktion
- Standard-Kategorien bei Installation (Samen Shop, Growshop, Headshop)
- Sprachdateien in korrekten Pfad verschoben (`lang/` statt `admin/lang/`)
- Frontend JS Output mit Sprachauswahl und Fallback

### v1.0.1 (2026-03-31)
- Typed Properties durch `var` ersetzt (PHP 8.1 Kompatibilität)
- Sprachkonstanten-Pattern korrigiert
- Menüeintrag unter Erweiterte Konfiguration

### v1.0.0 (2026-03-28)
- Initiale Version mit Mega-Menü Manager
