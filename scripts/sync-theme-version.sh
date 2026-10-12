#!/usr/bin/env bash
# Spiegelt die Version aus package.json nach style.css; laeuft als "version"-Lifecycle von
# `pnpm version`. --no-git-add fuer den manuellen Aufruf.
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
