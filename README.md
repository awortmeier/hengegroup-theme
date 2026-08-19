# base-theme

Persönliche Basis-Vorlage für WordPress- und WooCommerce-Themes.

## Neues Projekt aus dieser Vorlage starten

1. Auf der Repo-Seite **"Use this template" → "Create a new repository"**, neuen Namen vergeben
   (z. B. `acme-shop-theme`), erstellen.
2. Kurz warten, bis im neuen Repo ein automatischer Setup-Commit erscheint, dann clonen und in den
   WordPress-`wp-content/themes`-Ordner des Projekts legen (oder dorthin symlinken).
3. Manuell nachziehen — lässt sich nicht sinnvoll aus dem Repo-Namen ableiten:
    - `style.css`-Header: Theme URI, Description, Author URI.
    - Eigenes Logo setzen (WordPress-Customizer oder `header.php`).
    - Projektspezifische Marken-Akzentfarbe/-Schrift in `assets/css/tokens.css`, danach
      `pnpm run sync-theme-tokens` (zieht die Akzentfarbe automatisch in `theme.json` nach).
    - `.env` aus `.env.example` anlegen und mit den echten FTP-Zugangsdaten befüllen.
4. `setup.md` mit Claude Code durchgehen — projektspezifische Grundsatzentscheidungen (z. B.
   verarbeitet die Website personenbezogene Daten?), die sich nicht automatisch ableiten lassen.

## Anforderungen

Stand 2026-08-18, siehe `docs/entscheidungen.md` fuer die Begruendung; bei Uebernahme durch ein
neues Projekt gegen die dann aktuellen Werte pruefen:

| Komponente | Minimum (`style.css`/`composer.json`)                               | Empfohlen / getestet gegen                   |
| ---------- | ------------------------------------------------------------------- | -------------------------------------------- |
| WordPress  | 6.5 (`Requires at least`)                                           | 7.0 (`Tested up to`)                         |
| PHP        | 8.2 (`Requires PHP`, letzte Version mit laufendem Security-Support) | 8.3+ (WordPress-eigene Empfehlung)           |
| Node.js    | —                                                                   | 22 (siehe `.github/workflows/ci.yml`)        |
| pnpm       | —                                                                   | 11.18.0 (`packageManager` in `package.json`) |

PHP-Versionen vor 8.2 sind zum Stand dieser Tabelle End-of-Life (keine Security-Fixes mehr) und
werden deshalb nicht mehr unterstuetzt, auch wenn der Code selbst keine 8.2-spezifische Syntax
nutzt. `docs/entscheidungen.md` haelt zusaetzlich fest, dass "Tested up to" eine deklarierte
Kompatibilitaets-Zielmarke ist, keine durch einen automatisierten WP-Integrationstest verifizierte
Aussage (siehe `docs/to-do.md`, a11y-/Visual-Regression-Punkt zur fehlenden `wp-env`-Infrastruktur).
