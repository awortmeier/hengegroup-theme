#!/usr/bin/env bash
# macOS/Linux-Pendant zu clean.ps1 (siehe docs/entscheidungen.md fuer die Begruendung, warum
# beide Fassungen parallel gepflegt werden statt nur PowerShell Core vorauszusetzen). Verhalten
# 1:1 identisch: dist/ leeren, aber nie ausserhalb des Repos loeschen.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
dist_path="$repo_root/dist"

case "$dist_path" in
    "$repo_root"/*) ;;
    *)
        echo "Refusing to clean a dist path outside the repository: $dist_path" >&2
        exit 1
        ;;
esac

if [ ! -d "$dist_path" ]; then
    echo "Nothing to clean. Dist folder not found: $dist_path"
    exit 0
fi

shopt -s nullglob dotglob
targets=("$dist_path"/*)
shopt -u nullglob dotglob

if [ "${#targets[@]}" -eq 0 ]; then
    echo "Dist folder is already empty: $dist_path"
    exit 0
fi

for target in "${targets[@]}"; do
    rm -rf -- "$target"
    echo "Removed $target"
done
