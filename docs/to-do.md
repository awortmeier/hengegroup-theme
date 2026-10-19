# To-Do

Offene Punkte. Erledigtes entfernen; bewusste Entscheidungen nach `entscheidungen.md`.

## Fehler & Risiken

- **`<body>` ohne `body_class()`** (`header.php`). WooCommerce, Plugins und Admin-Bar erwarten die
  Klassen.
- **`page.php` gibt immer `<h1>` mit dem Seitentitel aus**: ungestylt, unter dem fixierten Header,
  die Buehne bekommt keine echte H1.
- **Vorlagen-Templates nicht umgebaut**: `single.php`, `archive.php`, `search.php`, `index.php`,
  `content-loop.php` nutzen nicht existierende Klassen; die Suche sieht kaputt aus.

## Fehlende Features

- **Mobile-Navigation** (Design fehlt).
- **Sprachumschalter** verlinkt auf `#`, bis das Multisite-Netzwerk existiert.
- **Dark Mode** ueber `tokens.css` (ob ueberhaupt).
- **Automatisierte a11y-/Visual-Regression-Tests** (Playwright + axe gegen die Seiten auf Dev).
- **DSGVO**: Exporter/Eraser (`wp_privacy_personal_data_exporters`/`_erasers`) und
  `wp_add_privacy_policy_content()` fuer Bewerbungen und Produktanfragen.
- **Product-JSON-LD** (optional, fuer KI-Suchen).
- **Google Indexing API** fuer Stellen (optional, braucht Service-Konto).
- **Anwendungen von der Anwendung aus zuordnen** (optional).
- **Bestellbarkeit**: Koernungen als Varianten, Warenkorb/Kasse, deaktivierte WC-Teile
  (`theme-hardening-woocommerce.php`, Admin-Menue) wieder zuschalten.
- **Site-Editor-/Customizer-Ausblendung** (`theme-admin.php`) pruefen, falls Bloecke sie brauchen.

## Pro Instanz (v. a. Live)

- `post_max_size` mind. 40M (Lebenslauf + 5 Zeugnisse).
- Datenschutzerklaerung um Bewerbungen/Anfragen ergaenzen und unter Einstellungen > Datenschutz
  setzen.
- Bewerbungsunterlagen: Testbewerbung, Datei-URL unter `uploads/hengegroup-bewerbungen/` muss
  403/404 liefern. Optional `open_basedir` auf `{WEBSPACEROOT}` fuer Ablage ausserhalb des
  Web-Roots.
- Seiten /karriere/ und /produkte/ einrichten (siehe `how-to.md`), Google Rich Results Test.
