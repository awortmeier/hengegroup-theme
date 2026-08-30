#!/usr/bin/env bash
# macOS/Linux-Pendant zu i18n-make-pot.ps1 (siehe docs/entscheidungen.md). Erzeugt die
# languages/hengegroup-theme.pot ueber WP-CLI (`wp i18n make-pot`).
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
languages_path="$repo_root/languages"
pot_file_path="$languages_path/hengegroup-theme.pot"

mkdir -p "$languages_path"

if ! command -v wp >/dev/null 2>&1; then
    echo "WP-CLI wurde nicht gefunden. Installiere 'wp' und fuehre dann 'pnpm i18n' erneut aus." >&2
    exit 1
fi

(
    cd "$repo_root"
    wp i18n make-pot . "$pot_file_path" --domain=hengegroup-theme --exclude=node_modules,dist,.git,.deploy-state,languages
)

echo "Generated $pot_file_path"
