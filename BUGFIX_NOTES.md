# MRH Dashboard v1.2.1 – Bugfix Notes

## Bug 1: Alte MrhMegaMenuManager.php auf Server (CRITICAL)
- **Symptom**: Cache-Datei wird nach Speichern nicht aktualisiert (Timestamp bleibt bei 09:50)
- **Ursache**: Die korrigierte Version wurde nie auf den Server deployt. Die alte Version hat einen Syntax-Fehler (`Unclosed '{' on line 406`), der die gesamte Klasse unbenutzbar macht.
- **Fix**: Korrigierte Version deployen

## Bug 2: Nav-Links fehlen im Frontend (is_active fehlt in Cache)
- **Symptom**: Nav-Links werden im Admin gespeichert, erscheinen aber nicht im Frontend
- **Ursache**: `regenerateCache()` schreibt Nav-Links OHNE `is_active` Feld. Das Frontend-Script `mrh-megamenu-config.js.php` (Zeile 130) filtert aber: `if (!isset($link['is_active']) || !$link['is_active']) continue;`
- **Fix**: Entweder `is_active` in Cache aufnehmen ODER Frontend-Filter entfernen (da nur aktive Links in den Cache geschrieben werden)

## Bug 3: Falsche Language-ID Zuordnung (CRITICAL)
- **Symptom**: Falsche Sprachen werden angezeigt
- **Ursache**: In MrhMegaMenuManager.php steht `2 => 'de', 1 => 'en'` aber laut Anforderung ist `German=1, English=2`
- **Fix**: Lang-Map korrigieren auf `1 => 'de', 2 => 'en'`
- **ACHTUNG**: Gleicher Fehler in mrh-megamenu-config.js.php Zeile 20: `$lang_map = array(2 => 'de', 1 => 'en', 5 => 'fr', 7 => 'es');`
- **Korrekt**: `1 => 'de', 2 => 'en', 3 => 'fr', 7 => 'es'` (French ist ID 3, nicht 5!)
