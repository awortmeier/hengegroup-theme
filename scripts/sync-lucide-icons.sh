#!/usr/bin/env bash
# macOS/Linux-Pendant zu sync-lucide-icons.ps1 (siehe docs/entscheidungen.md). Verhalten identisch:
# find-lucide-icons.php ermittelt die benoetigten Icon-Namen (Templates + scripts/lucide-icons.json),
# dieses Skript kopiert nur die tatsaechlich benoetigten SVGs aus node_modules/lucide-static.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
icon_scanner_path="$script_dir/find-lucide-icons.php"
source_directory="$repo_root/node_modules/lucide-static/icons"
destination_directory="$repo_root/assets/images/icons/lucide"

if [ ! -d "$source_directory" ]; then
    echo "lucide-static wurde nicht gefunden: $source_directory. Fuehre 'pnpm install' aus." >&2
    exit 1
fi

if [ ! -f "$icon_scanner_path" ]; then
    echo "Icon-Scanner nicht gefunden: $icon_scanner_path" >&2
    exit 1
fi

scan_output_json="$(php "$icon_scanner_path")"

# JSON-Array -> eine Zeile pro Icon-Name (node ist ueber pnpm/vite ohnehin schon Voraussetzung).
icon_names="$(node -e '
    const data = JSON.parse(require("fs").readFileSync(0, "utf8"));
    const names = Array.isArray(data) ? data : [data];
    process.stdout.write(names.join("\n"));
' <<<"$scan_output_json")"

mkdir -p "$destination_directory"

# Vorher aufraeumen, damit im Code nicht mehr referenzierte Icons nicht als Leichen liegen bleiben.
find "$destination_directory" -maxdepth 1 -name "*.svg" -type f -delete

if [ -z "$icon_names" ]; then
    echo "Keine Lucide-Icon-Referenzen im Theme-Code gefunden. Nichts zu synchronisieren."
    exit 0
fi

missing_icons=()
synced_count=0

while IFS= read -r icon_name; do
    [ -z "$icon_name" ] && continue

    source_path="$source_directory/$icon_name.svg"

    if [ ! -f "$source_path" ]; then
        missing_icons+=("$icon_name")
        continue
    fi

    cp -f "$source_path" "$destination_directory/$icon_name.svg"
    echo "Synced $icon_name"
    synced_count=$((synced_count + 1))
done <<<"$icon_names"

if [ "${#missing_icons[@]}" -gt 0 ]; then
    joined="$(printf ', %s' "${missing_icons[@]}")"
    echo "Unbekannte Lucide-Icon-Namen im Theme-Code referenziert: ${joined:2}" >&2
    exit 1
fi

echo "Fertig: $synced_count Icon(s) nach $destination_directory synchronisiert."
