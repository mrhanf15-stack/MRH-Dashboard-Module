#!/bin/bash
# ============================================================
# DEPLOY v1.8.11 – Color Configurator Fix
# Entfernt hardcoded CSS-Variablen aus mrh-custom.css
# Ergaenzt fehlende mrh-menu-* Aliase im Smarty-Plugin
# ============================================================
# DATUM: 2026-04-02
# BACKUP-TAG: backup-liveshop-2026-04-02 (v1.8.10)
# ============================================================

# Server-Pfade
SERVER_ROOT="/home/www/doc/28856/dcp288560004/mr-hanf.at/www"
TPL_PATH="$SERVER_ROOT/templates/tpl_mrh_2026"
OPCACHE_URL="https://mr-hanf.at/opcache_reset.php?token=MrHanf2024Reset"

echo "============================================================"
echo "  DEPLOY v1.8.11 – Color Configurator Fix"
echo "============================================================"
echo ""

# ---- 1. Backup der aktuellen Dateien ----
echo "[1/4] Backup erstellen..."
cp "$TPL_PATH/css/mrh-custom.css" "$TPL_PATH/css/mrh-custom.css.bak-$(date +%Y%m%d-%H%M%S)"
cp "$TPL_PATH/smarty/mrh_color_vars.php" "$TPL_PATH/smarty/mrh_color_vars.php.bak-$(date +%Y%m%d-%H%M%S)"
echo "  ✓ Backups erstellt"

# ---- 2. mrh-custom.css deployen ----
echo ""
echo "[2/4] mrh-custom.css deployen..."
curl -sS "https://raw.githubusercontent.com/mrhanf15-stack/MRH-Dashboard-Module/v1.8.11/templates/tpl_mrh_2026/css/mrh-custom.css" \
  -o "$TPL_PATH/css/mrh-custom.css"
echo "  ✓ mrh-custom.css aktualisiert"

# ---- 3. mrh_color_vars.php deployen ----
echo ""
echo "[3/4] mrh_color_vars.php deployen..."
curl -sS "https://raw.githubusercontent.com/mrhanf15-stack/MRH-Dashboard-Module/v1.8.11/templates/tpl_mrh_2026/smarty/mrh_color_vars.php" \
  -o "$TPL_PATH/smarty/mrh_color_vars.php"
echo "  ✓ mrh_color_vars.php aktualisiert"

# ---- 4. OPcache leeren ----
echo ""
echo "[4/4] OPcache leeren..."
curl -s "$OPCACHE_URL"
echo ""
echo "  ✓ OPcache geleert"

echo ""
echo "============================================================"
echo "  DEPLOY ABGESCHLOSSEN!"
echo ""
echo "  NAECHSTE SCHRITTE:"
echo "  1. Seite im Browser oeffnen (Strg+Shift+R fuer Hard-Reload)"
echo "  2. Template-Anpasser oeffnen (Admin → Seitenleiste rechts)"
echo "  3. Farbe aendern → Speichern → Seite neu laden"
echo "  4. Pruefen ob die Farbe sich aendert"
echo ""
echo "  BEI PROBLEMEN:"
echo "  Die .bak Dateien koennen wiederhergestellt werden:"
echo "  cp mrh-custom.css.bak-DATUM mrh-custom.css"
echo "============================================================"
