#!/usr/bin/env bash
# Spiegelt --color-henge-green aus tokens.css nach theme.json (Palette "accent" + Link-Farbe).
# Regex statt JSON-Roundtrip, damit die Formatierung von theme.json erhalten bleibt.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
tokens_path="$repo_root/assets/css/tokens.css"
theme_json_path="$repo_root/theme.json"

if [ ! -f "$tokens_path" ]; then
    echo "tokens.css nicht gefunden: $tokens_path" >&2
    exit 1
fi
if [ ! -f "$theme_json_path" ]; then
    echo "theme.json nicht gefunden: $theme_json_path" >&2
    exit 1
fi

result="$(node -e '
    const fs = require("fs");
    const [tokensPath, themeJsonPath] = process.argv.slice(1);

    const tokensCss = fs.readFileSync(tokensPath, "utf8");

    const accentMatch = tokensCss.match(/--color-henge-green:\s*([^;]+?)\s*;/);
    if (!accentMatch) {
        throw new Error("Kein \"--color-henge-green:\"-Eintrag in tokens.css gefunden.");
    }
    const accentColor = accentMatch[1];

    let themeJson = fs.readFileSync(themeJsonPath, "utf8");
    const originalThemeJson = themeJson;

    // settings.color.palette[] Eintrag mit "slug": "accent" -> dessen "color"-Wert.
    const palettePattern = /("slug":\s*"accent".*?"color":\s*)"#?[0-9a-fA-F]{3,8}"/s;
    if (!palettePattern.test(themeJson)) {
        throw new Error("Kein \"accent\"-Palette-Eintrag in theme.json gefunden (settings.color.palette).");
    }
    themeJson = themeJson.replace(palettePattern, (_, prefix) => `${prefix}"${accentColor}"`);

    // styles.elements.link.color.text -> Accent-Farbe (nicht styles.color.text, das ist eine andere Stelle).
    const linkPattern = /("link":\s*\{\s*"color":\s*\{\s*"text":\s*)"#?[0-9a-fA-F]{3,8}"/s;
    if (!linkPattern.test(themeJson)) {
        throw new Error("Kein styles.elements.link.color.text-Eintrag in theme.json gefunden.");
    }
    themeJson = themeJson.replace(linkPattern, (_, prefix) => `${prefix}"${accentColor}"`);

    if (themeJson === originalThemeJson) {
        console.log(`UNCHANGED ${accentColor}`);
        process.exit(0);
    }

    // Sicherheitsnetz: sicherstellen, dass das Ergebnis noch gueltiges JSON ist, bevor es geschrieben wird.
    try {
        JSON.parse(themeJson);
    } catch (error) {
        throw new Error(`Ersetzung haette theme.json invalide gemacht, breche ab ohne zu schreiben: ${error.message}`);
    }

    fs.writeFileSync(themeJsonPath, themeJson, "utf8");
    console.log(`UPDATED ${accentColor}`);
' "$tokens_path" "$theme_json_path")"

status="${result%% *}"
accent_color="${result#* }"

if [ "$status" = "UNCHANGED" ]; then
    echo "theme.json ist bereits auf $accent_color synchronisiert - nichts zu tun."
else
    echo "theme.json synchronisiert: settings.color.palette 'accent' / styles.elements.link.color.text -> $accent_color"
fi
