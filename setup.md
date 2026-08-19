# Setup

Anweisungen fuer Claude Code: direkt nachdem ein neues Projekt-Theme aus dieser Vorlage erstellt
wurde (README "Neues Projekt aus dieser Vorlage starten"), diese Liste mit dem Nutzer durchgehen,
bevor normal am Theme weitergearbeitet wird. Jeder Punkt ist eine Frage an den Nutzer, keine
Entscheidung, die Claude selbststaendig/ungefragt trifft — die Basis-Vorlage setzt bewusst
konservative Defaults (siehe Verweise unten), die je nach Projekt aufgehoben werden muessen.

## Verarbeitet diese Website personenbezogene Daten?

Z. B. durch ein Kontakt-/Bewerbungsformular, eine Newsletter-Anmeldung, wieder aktivierte
Kommentare o. Ae. (Kommentare sind in dieser Vorlage standardmaessig geschlossen, siehe
`inc/setup/theme-hardening.php`, `hengegroup_theme_filter_close_comments_for_posts_and_pages()`.)

- **Nein** (Default dieser Vorlage): nichts zu tun, aktuelle Einstellungen passen.
- **Ja**:
    1. In `inc/setup/theme-admin.php`, Funktion `hengegroup_theme_action_admin_menu_cleanup()`: die beiden
       Eintraege `remove_submenu_page('tools.php', 'export-personal-data.php')` und
       `remove_submenu_page('tools.php', 'erase-personal-data.php')` entfernen, damit die
       DSGVO-Tools-Seiten (Werkzeuge → Persoenliche Daten exportieren/loeschen) im Backend wieder
       erreichbar sind.
    2. `wp_add_privacy_policy_content()` fuer das konkrete Feature registrieren (Text, welche Daten
       wo/warum verarbeitet werden — WordPress zeigt das als Vorschlag auf der
       Datenschutz-Einstellungsseite an, veroeffentlicht ihn aber nicht automatisch).
    3. Sobald das PII-sammelnde Feature technisch existiert (z. B. eine Custom-Post-Type fuer
       Bewerbungen mit Bewerber-E-Mail als Meta-Feld): `wp_privacy_personal_data_exporters`- und
       `wp_privacy_personal_data_erasers`-Filter dafuer registrieren (Recht auf Auskunft/Loeschung —
       ohne die aus Schritt 1 wieder sichtbaren Tools-Seiten waeren diese Filter unerreichbar).
