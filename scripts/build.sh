#!/usr/bin/env bash
# Baut das Theme nach dist/.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
dist_path="$repo_root/dist"

# Top-Level-PHP-Templates kommen separat per *.php-Wildcard dazu.
theme_static_files=(
    "style.css"
    "theme.json"
    "screenshot.png"
    "screenshot.jpg"
)

theme_directories=(
    "inc:inc"
    "template-parts:template-parts"
    "languages:languages"
    "assets/images:assets/images"
    # Unterordner werden nicht per Wildcard erfasst, deshalb hier explizit.
    "woocommerce:woocommerce"
)

mkdir -p "$dist_path"

"$script_dir/clean.sh"
"$script_dir/sync-lucide-icons.sh"
"$script_dir/sync-tabler-icons.sh"

(
    cd "$repo_root"
    pnpm run build:assets
)

# Produktions-vendor/ (svg-sanitize) mitliefern, weil auf dem Server kein composer laeuft.
# Danach per trap wieder den Dev-vendor/ herstellen, damit lint/test lokal weiter funktionieren.
(
    cd "$repo_root"
    trap 'composer install' EXIT
    composer install --no-dev --optimize-autoloader
    rm -rf "$dist_path/vendor"
    cp -R "$repo_root/vendor" "$dist_path/vendor"
    echo "Copied vendor (production-only)"
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
