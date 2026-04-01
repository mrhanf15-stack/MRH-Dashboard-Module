#!/bin/bash
# ============================================================
# MRH 2026 – mmenu Cleanup Script
# Entfernt alle jQuery mmenu Reste vom Server
# ============================================================

BASEDIR="/home/www/doc/28856/dcp288560004/mr-hanf.at/www/templates/tpl_mrh_2026"

echo "=== mmenu Cleanup ==="

# JS-Dateien entfernen
for f in "jquery.mmenu.all.js" "jquery.mmenu.fixedelements.js" "jquery.mmenulight.js"; do
  if [ -f "$BASEDIR/javascript/$f" ]; then
    rm -f "$BASEDIR/javascript/$f"
    echo "GELÖSCHT: javascript/$f"
  else
    echo "NICHT VORHANDEN: javascript/$f"
  fi
done

# CSS-Dateien entfernen
for f in "jquery.mmenu.all.css" "jquery.mmenulight.css"; do
  if [ -f "$BASEDIR/css/$f" ]; then
    rm -f "$BASEDIR/css/$f"
    echo "GELÖSCHT: css/$f"
  else
    echo "NICHT VORHANDEN: css/$f"
  fi
done

echo ""
echo "=== mmenu Cleanup abgeschlossen ==="
echo "Hinweis: Das mobile Menü wird jetzt von mrh-core.js.php (Vanilla JS) übernommen."
