#!/usr/bin/env bash
# macOS/Linux-Pendant zu build.ps1 (siehe docs/entscheidungen.md). Verhalten identisch: dist/
# leeren, Icons synchronisieren, Vite-Assets bauen, Theme-Dateien nach dist/ kopieren.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
dist_path="$repo_root/dist"

# Nicht-PHP-Theme-Dateien mit festem Namen (WP-Konvention). PHP-Templates werden separat unten
# per *.php-Wildcard erfasst (siehe docs/entscheidungen.md), damit neue Top-Level-Templates nach
# WordPress-Template-Hierarchie (z. B. page-{slug}.php, single-{post-type}.php,
# category-{slug}.php) automatisch mitgebaut werden, ohne diese Liste pflegen zu muessen.
theme_static_files=(
    "style.css"
    "theme.json"
    "screenshot.png"
    "screenshot.jpg"
)

# "Quelle:Ziel"-Paare (relativ zum Repo-Root), analog zur $themeDirectories-Tabelle in build.ps1.
theme_directories=(
    "inc:inc"
    "template-parts:template-parts"
    "languages:languages"
    "assets/images:assets/images"
)

mkdir -p "$dist_path"

"$script_dir/clean.sh"
"$script_dir/sync-lucide-icons.sh"
"$script_dir/sync-tabler-icons.sh"

(
    cd "$repo_root"
    pnpm run build:assets
)

for file in "${theme_static_files[@]}"; do
    source_path="$repo_root/$file"
    [ -e "$source_path" ] || continue

    cp -f "$source_path" "$dist_path/$file"
    echo "Copied $file"
done

shopt -s nullglob
for source_path in "$repo_root"/*.php; do
    file="$(basename "$source_path")"
    cp -f "$source_path" "$dist_path/$file"
    echo "Copied $file"
done
shopt -u nullglob

for entry in "${theme_directories[@]}"; do
    source_dir="${entry%%:*}"
    destination_dir="${entry##*:}"
    source_path="$repo_root/$source_dir"
    [ -e "$source_path" ] || continue

    destination_path="$dist_path/$destination_dir"
    mkdir -p "$(dirname "$destination_path")"

    rm -rf "$destination_path"
    cp -R "$source_path" "$destination_path"
    echo "Copied $source_dir"
done
