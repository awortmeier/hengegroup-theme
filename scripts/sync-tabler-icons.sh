#!/usr/bin/env bash
# macOS/Linux-Pendant zu sync-tabler-icons.ps1 (siehe docs/entscheidungen.md). Verhalten identisch:
# find-tabler-icons.php ermittelt Name+Variante (outline/filled) aus Templates +
# scripts/tabler-icons.json, dieses Skript kopiert nur die benoetigten SVGs aus @tabler/icons.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
icon_scanner_path="$script_dir/find-tabler-icons.php"
source_root_directory="$repo_root/node_modules/@tabler/icons/icons"
outline_destination_directory="$repo_root/assets/images/icons/tabler/outline"
filled_destination_directory="$repo_root/assets/images/icons/tabler/filled"

if [ ! -d "$source_root_directory" ]; then
    echo "@tabler/icons wurde nicht gefunden: $source_root_directory. Fuehre 'pnpm install' aus." >&2
    exit 1
fi

if [ ! -f "$icon_scanner_path" ]; then
    echo "Icon-Scanner nicht gefunden: $icon_scanner_path" >&2
    exit 1
fi

scan_output_json="$(php "$icon_scanner_path")"

# JSON-Array von {name, variant} -> eine "name<TAB>variant"-Zeile pro Icon.
requested_icons="$(node -e '
    const data = JSON.parse(require("fs").readFileSync(0, "utf8"));
    const icons = Array.isArray(data) ? data : [data];
    process.stdout.write(icons.map((icon) => `${icon.name}\t${icon.variant}`).join("\n"));
' <<<"$scan_output_json")"

for destination_directory in "$outline_destination_directory" "$filled_destination_directory"; do
    mkdir -p "$destination_directory"
    # Vorher aufraeumen, damit nicht mehr benoetigte Icons nicht als Leichen liegen bleiben.
    find "$destination_directory" -maxdepth 1 -name "*.svg" -type f -delete
done

if [ -z "$requested_icons" ]; then
    echo "Keine Tabler-Icon-Referenzen (Code oder tabler-icons.json) gefunden. Nichts zu synchronisieren."
    exit 0
fi

missing_icons=()
synced_count=0

while IFS=$'\t' read -r name variant; do
    [ -z "$name" ] && continue
    [ -z "$variant" ] && continue

    source_path="$source_root_directory/$variant/$name.svg"

    if [ ! -f "$source_path" ]; then
        missing_icons+=("$name ($variant)")
        continue
    fi

    if [ "$variant" = "filled" ]; then
        destination_directory="$filled_destination_directory"
    else
        destination_directory="$outline_destination_directory"
    fi

    cp -f "$source_path" "$destination_directory/$name.svg"
    echo "Synced $name ($variant)"
    synced_count=$((synced_count + 1))
done <<<"$requested_icons"

if [ "${#missing_icons[@]}" -gt 0 ]; then
    joined="$(printf ', %s' "${missing_icons[@]}")"
    echo "Unbekannte Tabler-Icon-Referenzen im Theme-Code oder in tabler-icons.json: ${joined:2}" >&2
    exit 1
fi

echo "Fertig: $synced_count Icon(s) nach $outline_destination_directory / $filled_destination_directory synchronisiert."
