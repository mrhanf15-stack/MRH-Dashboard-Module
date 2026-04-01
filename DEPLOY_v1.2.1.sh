#!/bin/bash
# ============================================================
# MRH Dashboard v1.2.1 - Deployment Script
# Fuehre dieses Script auf dem Server aus (SSH)
# ============================================================

SHOP="/home/www/doc/28856/dcp288560004/mr-hanf.at/www"
ADMIN="admin_q9wKj6Ds"
TMP="/tmp/mrh_deploy_v121"

echo "=== MRH Dashboard v1.2.1 Deployment ==="
echo ""

# 1. Altes Deployment-Verzeichnis aufraumen
rm -rf "$TMP"

# 2. Repo klonen
echo "[1/5] Repository klonen..."
git clone https://github.com/mrhanf15-stack/MRH-Dashboard-Module.git "$TMP"
if [ $? -ne 0 ]; then
    echo "FEHLER: Git clone fehlgeschlagen!"
    exit 1
fi

# 3. Dateien kopieren
echo "[2/5] Dateien kopieren..."

# Kern-Datei (MegaMenuManager)
cp -f "$TMP/includes/external/mrh_dashboard/MrhMegaMenuManager.php" \
      "$SHOP/includes/external/mrh_dashboard/MrhMegaMenuManager.php"

# Admin-Dateien
cp -f "$TMP/admin/mrh_dashboard.php" \
      "$SHOP/$ADMIN/mrh_dashboard.php"

cp -f "$TMP/admin/includes/modules/system/mrh_dashboard.php" \
      "$SHOP/$ADMIN/includes/modules/system/mrh_dashboard.php"

cp -f "$TMP/admin/includes/extra/menu/mrh_dashboard.php" \
      "$SHOP/$ADMIN/includes/extra/menu/mrh_dashboard.php"

# Frontend JS-Dateien
cp -f "$TMP/templates/tpl_mrh_2026/javascript/extra/mrh-megamenu-config.js.php" \
      "$SHOP/templates/tpl_mrh_2026/javascript/extra/mrh-megamenu-config.js.php"

# v1.1.0 FIX: mrh-core.js.php mit Bugfixes fuer Dashboard-Config
cp -f "$TMP/templates/tpl_mrh_2026/javascript/extra/mrh-core.js.php" \
      "$SHOP/templates/tpl_mrh_2026/javascript/extra/mrh-core.js.php"

# Sprachdateien
for lang in german english french spanish; do
    # Extra Admin Sprachdateien
    if [ -f "$TMP/lang/$lang/extra/admin/mrh_dashboard.php" ]; then
        mkdir -p "$SHOP/lang/$lang/extra/admin/"
        cp -f "$TMP/lang/$lang/extra/admin/mrh_dashboard.php" \
              "$SHOP/lang/$lang/extra/admin/mrh_dashboard.php"
    fi
    # System Modul Sprachdateien
    if [ -f "$TMP/lang/$lang/modules/system/mrh_dashboard.php" ]; then
        mkdir -p "$SHOP/lang/$lang/modules/system/"
        cp -f "$TMP/lang/$lang/modules/system/mrh_dashboard.php" \
              "$SHOP/lang/$lang/modules/system/mrh_dashboard.php"
    fi
done

# 4. OPcache leeren
echo "[3/5] OPcache leeren..."
curl -s "https://mr-hanf.at/opcache_reset.php?token=MrHanf2024Reset"
echo ""

# 5. Aufraumen
echo "[4/5] Temporaere Dateien aufraumen..."
rm -rf "$TMP"

# 6. Pruefen
echo "[5/5] Dateien pruefen..."
echo "--- MrhMegaMenuManager.php ---"
ls -la "$SHOP/includes/external/mrh_dashboard/MrhMegaMenuManager.php"
echo "--- mrh-core.js.php ---"
ls -la "$SHOP/templates/tpl_mrh_2026/javascript/extra/mrh-core.js.php"
echo "--- mrh-megamenu-config.js.php ---"
ls -la "$SHOP/templates/tpl_mrh_2026/javascript/extra/mrh-megamenu-config.js.php"
echo ""
echo "=== Deployment abgeschlossen! ==="
echo ""
echo "NAECHSTE SCHRITTE:"
echo "1. Admin oeffnen: https://mr-hanf.at/$ADMIN/mrh_dashboard.php"
echo "2. Beliebige Kategorie waehlen und 'Speichern' klicken"
echo "3. Pruefen ob Cache-Datei aktualisiert wird:"
echo "   ls -la $SHOP/templates/tpl_mrh_2026/config/megamenu_config.json"
echo "4. Frontend pruefen (Inkognito!): https://mr-hanf.at/"
echo ""
