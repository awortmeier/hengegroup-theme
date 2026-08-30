#!/usr/bin/env bash
#
# macOS/Linux-Pendant zu sync-theme-version.ps1 (siehe docs/entscheidungen.md). Spiegelt die
# Versionsnummer aus package.json ("version") in den style.css-WordPress-Theme-Header
# ("Version:"-Feld). package.json ist die Single Source of Truth (siehe README "Versionierung").
#
# Gedacht als "version"-Lifecycle-Script fuer `pnpm version <patch|minor|major>` (siehe
# package.json "scripts.version") — npm/pnpm bumpen dabei zuerst package.json, fuehren dann dieses
# Skript aus, bevor sie den Versions-Commit + Git-Tag erzeugen. Alles, was dieses Skript per
# `git add` staged, landet automatisch im selben Commit wie der package.json-Bump.
#
# Kann auch manuell aufgerufen werden (`pnpm run sync-theme-version`), z. B. um eine von Hand in
# package.json geaenderte Version nachzuziehen, ohne `pnpm version` (inkl. Commit/Tag) auszuloesen.
#
# --no-git-add: ueberspringt `git add style.css` — nur fuer den manuellen Aufruf ausserhalb des
# Versions-Lifecycles sinnvoll, wo kein automatischer Commit folgt. Pendant zu -NoGitAdd.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
package_json_path="$repo_root/package.json"
style_css_path="$repo_root/style.css"

no_git_add=0
for arg in "$@"; do
    case "$arg" in
        --no-git-add) no_git_add=1 ;;
        *)
            echo "Unbekanntes Argument: $arg" >&2
            exit 1
            ;;
    esac
done

if [ ! -f "$package_json_path" ]; then
    echo "package.json nicht gefunden: $package_json_path" >&2
    exit 1
fi
if [ ! -f "$style_css_path" ]; then
    echo "style.css nicht gefunden: $style_css_path" >&2
    exit 1
fi

result="$(node -e '
    const fs = require("fs");
    const [packageJsonPath, styleCssPath] = process.argv.slice(1);

    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, "utf8"));
    const version = packageJson.version;

    if (!version) {
        throw new Error("package.json enthaelt kein '"'"'version'"'"'-Feld.");
    }
    if (!/^\d+\.\d+\.\d+/.test(version)) {
        throw new Error(`package.json '"'"'version'"'"' sieht nicht wie SemVer aus: '"'"'${version}'"'"'`);
    }

    const styleCss = fs.readFileSync(styleCssPath, "utf8");
    const pattern = /^Version:.*$/m;
    if (!pattern.test(styleCss)) {
        throw new Error("style.css enthaelt kein '"'"'Version:'"'"'-Feld im Theme-Header.");
    }

    const updated = styleCss.replace(pattern, `Version: ${version}`);

    if (updated === styleCss) {
        console.log(`UNCHANGED ${version}`);
        process.exit(0);
    }

    fs.writeFileSync(styleCssPath, updated, "utf8");
    console.log(`UPDATED ${version}`);
' "$package_json_path" "$style_css_path")"

status="${result%% *}"
version="${result#* }"

if [ "$status" = "UNCHANGED" ]; then
    echo "style.css ist bereits auf Version $version - nichts zu tun."
    exit 0
fi

echo "style.css 'Version:' -> $version"

if [ "$no_git_add" -eq 0 ] && command -v git >/dev/null 2>&1; then
    git -C "$repo_root" add -- style.css
fi
