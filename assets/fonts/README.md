# Self-gehostete Marken-Schriften

Erwartete Ablage pro Font-Familie in einem eigenen Unterordner, referenziert von
`assets/css/fonts.css` (`@font-face`, WOFF2 bevorzugt, TTF/OTF als Fallback-Quelle). Aktuell
vorhandene Dateien:

- `outfit/outfit.woff2` + `outfit/outfit.ttf` — Primaer-/Fliesstext-Schrift (`--font-primary` in
  `assets/css/tokens.css`). **Variable Font** (Achse `wght`, 100–900) — deckt alle Gewichte aus
  einer Datei ab, `font-weight: 100 900` in `fonts.css`.
- `crillee/crillee.woff2` + `crillee/crillee.otf` — Akzent-/Display-Schrift (`--font-accent` in
  `assets/css/tokens.css`), statischer Schnitt. **Nur Schnitt 700 (Bold) vorhanden** — kein
  Regular/400. Siehe Kopfkommentar von `assets/css/fonts.css` fuer die Konsequenz (Browser matcht
  diesen einen Schnitt fuer jede angeforderte Font-Weight).

WOFF2-Dateien per `npx ttf2woff2 < input.ttf > output.woff2` (bzw. `.otf`) aus der jeweiligen
TTF/OTF-Quelle erzeugt.

Weitere Schnitte/Familien nach demselben Muster ablegen (TTF/OTF-Quelle + daraus erzeugtes WOFF2)
und in `assets/css/fonts.css` als zusaetzlichen `@font-face`-Block mit passendem
`font-weight`/`font-style` ergaenzen.
