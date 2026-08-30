#!/usr/bin/env bash
#
# macOS/Linux-Pendant zu rename-theme.ps1 (siehe docs/entscheidungen.md). Automatisiert Schritt 2
# (und optional 3/4) von "Neues Projekt aus dieser Vorlage starten" in README.md: benennt Slug/
# Text-Domain und PHP-Funktions-Praefix im gesamten Code um.
#
# --new-slug <slug>        Neuer kebab-case Slug, z. B. "acme-shop". Ersetzt jedes Vorkommen von
#                           --old-slug (Text-Domain, Enqueue-Handles, i18n-Domain, package.json
#                           "name", ...). Pflicht.
# --new-prefix <prefix>    Neues PHP-Funktions-Praefix, muss mit "_" enden, z. B. "acme_shop_".
#                           Ersetzt jedes Vorkommen von --old-prefix. Default: --new-slug mit
#                           "-" -> "_" plus trailendem "_".
# --old-slug <slug>        Zu ersetzender Slug, Default "base-theme". Ueberschreiben beim
#                           Rueckgaengigmachen eines vorherigen Renames (siehe pull-base-updates.sh,
#                           das dieses Skript mit vertauschten --old-slug/--new-slug aufruft, um vor
#                           dem Upstream-Merge temporaer wieder auf base-theme-Naming zu wechseln).
# --old-prefix <prefix>    Zu ersetzendes PHP-Funktions-Praefix, Default "base_theme_". Gleicher
#                           Rueckgaengigmachen-Anwendungsfall wie --old-slug.
# --theme-name / --theme-uri / --description / --author / --author-uri
#                           Optional — wenn gesetzt, wird zusaetzlich das jeweilige
#                           style.css-Theme-Header-Feld gepatcht (README Schritt 3). Ausgelassene
#                           Felder bleiben unangetastet.
# --dry-run                 Zeigt nur, welche Dateien sich aendern wuerden (und wie viele
#                           Ersetzungen je Datei), ohne etwas zu schreiben.
#
# Beispiele:
#   bash scripts/rename-theme.sh --new-slug acme-shop --dry-run
#   bash scripts/rename-theme.sh --new-slug acme-shop \
#       --theme-name "Acme Shop" --theme-uri "https://acme.example/" --author-uri "https://agency.example/"
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"

new_slug=""
new_prefix=""
old_slug="base-theme"
old_prefix="base_theme_"
theme_name=""
theme_uri=""
description=""
author=""
author_uri=""
dry_run=0

while [ "$#" -gt 0 ]; do
    case "$1" in
        --new-slug) new_slug="$2"; shift 2 ;;
        --new-prefix) new_prefix="$2"; shift 2 ;;
        --old-slug) old_slug="$2"; shift 2 ;;
        --old-prefix) old_prefix="$2"; shift 2 ;;
        --theme-name) theme_name="$2"; shift 2 ;;
        --theme-uri) theme_uri="$2"; shift 2 ;;
        --description) description="$2"; shift 2 ;;
        --author) author="$2"; shift 2 ;;
        --author-uri) author_uri="$2"; shift 2 ;;
        --dry-run) dry_run=1; shift ;;
        *)
            echo "Unbekanntes Argument: $1" >&2
            exit 1
            ;;
    esac
done

if [ -z "$new_slug" ]; then
    echo "--new-slug ist Pflicht." >&2
    exit 1
fi

if ! [[ "$new_slug" =~ ^[a-z0-9]+(-[a-z0-9]+)*$ ]]; then
    echo "--new-slug muss kebab-case sein (nur a-z, 0-9, Bindestrich), z. B. 'acme-shop'. Erhalten: '$new_slug'" >&2
    exit 1
fi

if [ -z "$new_prefix" ]; then
    new_prefix="${new_slug//-/_}_"
fi

if ! [[ "$new_prefix" =~ ^[a-z0-9_]+_$ ]]; then
    echo "--new-prefix muss aus a-z, 0-9, Unterstrich bestehen und mit '_' enden, z. B. 'acme_shop_'. Erhalten: '$new_prefix'" >&2
    exit 1
fi

# Siehe rename-theme.ps1s Kopfkommentar fuer die ausfuehrliche Begruendung dieser Ausschlussliste
# (kurz: dieses Skript selbst, pull-base-updates.(ps1|sh) und template-init.yml sind fixe,
# wiederholbare Bootstrap/Update-Schritte, deren -OldSlug/-OldPrefix-Defaults nicht mitumbenannt
# werden duerfen, sonst zeigen sie nach einem Rename ins Leere).
excluded_directory_names=("node_modules" "dist" ".git" ".deploy-state" "vendor")
included_extensions=(".php" ".css" ".js" ".mjs" ".json" ".md" ".ps1" ".sh" ".txt" ".xml")
excluded_relative_files=(
    "scripts/rename-theme.ps1"
    "scripts/rename-theme.sh"
    "scripts/pull-base-updates.ps1"
    "scripts/pull-base-updates.sh"
    ".github/workflows/template-init.yml"
)

is_excluded_relative_file() {
    local rel="$1" entry
    for entry in "${excluded_relative_files[@]}"; do
        [ "$rel" = "$entry" ] && return 0
    done
    return 1
}

has_included_extension() {
    local lower_file ext
    lower_file="$(echo "$1" | tr '[:upper:]' '[:lower:]')"
    for ext in "${included_extensions[@]}"; do
        case "$lower_file" in
            *"$ext") return 0 ;;
        esac
    done
    return 1
}

prune_expr=()
for name in "${excluded_directory_names[@]}"; do
    prune_expr+=(-name "$name" -o)
done
unset 'prune_expr[${#prune_expr[@]}-1]'

candidate_files_file="$(mktemp)"
: >"$candidate_files_file"

while IFS= read -r -d '' file; do
    relative_path="${file#"$repo_root"/}"
    has_included_extension "$file" || continue
    is_excluded_relative_file "$relative_path" && continue
    printf '%s\0' "$file" >>"$candidate_files_file"
done < <(find "$repo_root" \( -type d \( "${prune_expr[@]}" \) -prune \) -o -type f -print0)

result_tsv="$(
    node -e '
        const fs = require("fs");
        const [oldPrefix, newPrefix, oldSlug, newSlug, dryRunFlag, repoRoot] = process.argv.slice(1);
        const dryRun = dryRunFlag === "1";

        const countOccurrences = (haystack, needle) => {
            if (!needle) return 0;
            let count = 0;
            let index = 0;
            while ((index = haystack.indexOf(needle, index)) !== -1) {
                count++;
                index += needle.length;
            }
            return count;
        };

        const files = fs.readFileSync(0, "utf8").split("\0").filter(Boolean);

        for (const file of files) {
            const original = fs.readFileSync(file, "utf8");
            const prefixMatches = countOccurrences(original, oldPrefix);
            const slugMatches = countOccurrences(original, oldSlug);

            if (prefixMatches === 0 && slugMatches === 0) continue;

            const updated = original.split(oldPrefix).join(newPrefix).split(oldSlug).join(newSlug);
            const relativePath = file.startsWith(repoRoot + "/") ? file.slice(repoRoot.length + 1) : file;
            const fileReplacements = prefixMatches + slugMatches;

            if (!dryRun) {
                fs.writeFileSync(file, updated, "utf8");
            }

            console.log(`${relativePath}\t${fileReplacements}`);
        }
    ' "$old_prefix" "$new_prefix" "$old_slug" "$new_slug" "$dry_run" "$repo_root" <"$candidate_files_file"
)"
rm -f "$candidate_files_file"

style_css_path="$repo_root/style.css"
style_css_header_changes=""

if [ -f "$style_css_path" ] && { [ -n "$theme_name" ] || [ -n "$theme_uri" ] || [ -n "$description" ] || [ -n "$author" ] || [ -n "$author_uri" ]; }; then
    style_css_header_changes="$(
        node -e '
            const fs = require("fs");
            const [styleCssPath, dryRunFlag, themeName, themeUri, description, author, authorUri] = process.argv.slice(1);
            const dryRun = dryRunFlag === "1";

            const fields = [
                ["Theme Name", themeName],
                ["Theme URI", themeUri],
                ["Description", description],
                ["Author", author],
                ["Author URI", authorUri],
            ];

            let styleCss = fs.readFileSync(styleCssPath, "utf8");
            const changed = [];

            for (const [fieldName, value] of fields) {
                if (!value) continue;

                const pattern = new RegExp(`^${fieldName}:.*$`, "m");
                if (!pattern.test(styleCss)) continue;

                styleCss = styleCss.replace(pattern, `${fieldName}: ${value}`);
                changed.push(fieldName);
            }

            if (changed.length > 0 && !dryRun) {
                fs.writeFileSync(styleCssPath, styleCss, "utf8");
            }

            console.log(changed.join("\t"));
        ' "$style_css_path" "$dry_run" "$theme_name" "$theme_uri" "$description" "$author" "$author_uri"
    )"
fi

echo "Slug: $old_slug -> $new_slug"
echo "Praefix: $old_prefix -> $new_prefix"
echo ""

if [ -z "$result_tsv" ]; then
    echo "Keine Dateien mit '$old_slug' oder '$old_prefix' gefunden."
else
    changed_count=0
    total_replacements=0
    while IFS=$'\t' read -r relative_path count; do
        [ -z "$relative_path" ] && continue
        echo "  $relative_path ($count)"
        changed_count=$((changed_count + 1))
        total_replacements=$((total_replacements + count))
    done <<<"$result_tsv"
    echo ""
    echo "$changed_count Datei(en), $total_replacements Ersetzung(en) insgesamt."
fi

if [ -n "$style_css_header_changes" ]; then
    echo ""
    echo "style.css-Header aktualisiert: $(echo "$style_css_header_changes" | tr '\t' ',' | sed 's/,/, /g')"
fi

if [ "$dry_run" -eq 1 ]; then
    echo ""
    echo "Dry-Run: keine Datei wurde geschrieben. Ohne --dry-run ausfuehren, um die Aenderungen zu uebernehmen."
else
    echo ""
    echo "Fertig. Verbleibend laut README: package.json 'name' pruefen (wird durch den Slug bereits mit umbenannt,"
    echo "falls PackageName = NewSlug gewuenscht war), eigenes Logo setzen, .env aus .env.example anlegen."
fi
