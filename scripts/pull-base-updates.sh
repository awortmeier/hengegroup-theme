#!/usr/bin/env bash
#
# macOS/Linux-Pendant zu pull-base-updates.ps1 (siehe docs/entscheidungen.md). Zieht spaetere
# Aenderungen aus dem base-theme-Vorlagen-Repo in ein Projekt-Theme, das daraus gestartet wurde
# (README "Neues Projekt aus dieser Vorlage starten"), oben auf dessen eigenen Slug/Praefix-Rename
# (rename-theme.sh).
#
# Ein umbenanntes Projekt-Theme matcht textlich nicht mehr mit dem Basis-Repo (rename-theme.sh hat
# jedes "base-theme"/"base_theme_"-Vorkommen durch den Projekt-eigenen Slug/Praefix ersetzt), daher
# wuerde ein einfacher `git merge`/`git subtree pull` gegen das Basis-Repo auf fast jeder Zeile
# konfligieren, die der Rename beruehrt hat, auch wenn sich der eigentliche Basis-Inhalt nicht
# geaendert hat. Dieses Skript umgeht das, indem es den Merge auf einem temporaeren Branch macht,
# auf dem das Naming wieder passt:
#
#   1. Basis-Repo als Git-Remote hinzufuegen/fetchen.
#   2. Temporaeren Branch vom aktuellen Branch erzeugen.
#   3. Diesen Branch zurueck auf base-theme/base_theme_-Naming umbenennen (rename-theme.sh mit
#      vertauschten --old-slug/--old-prefix), committen.
#   4. Basis-Repo-Branch mergen — jetzt ein echter, inhaltlicher Diff/Merge.
#   5. Projekt-eigenes Slug/Praefix-Naming wieder anwenden (rename-theme.sh erneut vorwaerts),
#      committen.
#
# Nichts wird automatisch in den tatsaechlichen Branch zurueckgemerged — den Diff des temporaeren
# Branches selbst pruefen, dann manuell mergen/rebasen (Anleitung wird am Ende ausgegeben).
#
# Braucht einen sauberen Working Tree zum Start (uncommittete Aenderungen werden nie angefasst,
# aber das Skript muss sicher Branches erzeugen/wechseln koennen).
#
# --current-slug <slug>    Aktueller kebab-case Slug dieses Projekts (was rename-theme.shs
#                           --new-slug beim Projektstart war). Default: package.json "name".
# --current-prefix <pfx>   Aktuelles PHP-Funktions-Praefix. Default: --current-slug mit "-" -> "_"
#                           plus trailendem "_" — gleiche Herleitung wie rename-theme.sh ohne
#                           explizites --new-prefix. Bei einem projekteigenen --new-prefix
#                           ueberschreiben.
# --remote-url <url>       Git-URL des base-theme-Vorlagen-Repos. Default: dessen eigenes
#                           GitHub-Repo.
# --remote-branch <branch> Branch im Basis-Repo, von dem gezogen wird. Default "main".
# --remote-name <name>     Lokaler Name fuer den Basis-Repo-Remote. Default
#                           "base-theme-upstream" (wird angelegt falls fehlend, sonst aktualisiert).
#
# Beispiele:
#   bash scripts/pull-base-updates.sh
#   bash scripts/pull-base-updates.sh --current-slug acme-shop --current-prefix acme_shop_
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
rename_theme_script="$script_dir/rename-theme.sh"

current_slug=""
current_prefix=""
remote_url="git@github.com:awortmeier/wordpress-base-theme.git"
remote_branch="main"
remote_name="base-theme-upstream"
base_slug="base-theme"
base_prefix="base_theme_"

while [ "$#" -gt 0 ]; do
    case "$1" in
        --current-slug) current_slug="$2"; shift 2 ;;
        --current-prefix) current_prefix="$2"; shift 2 ;;
        --remote-url) remote_url="$2"; shift 2 ;;
        --remote-branch) remote_branch="$2"; shift 2 ;;
        --remote-name) remote_name="$2"; shift 2 ;;
        --base-slug) base_slug="$2"; shift 2 ;;
        --base-prefix) base_prefix="$2"; shift 2 ;;
        *)
            echo "Unbekanntes Argument: $1" >&2
            exit 1
            ;;
    esac
done

if ! command -v git >/dev/null 2>&1; then
    echo "git wurde nicht gefunden. Ohne git kann kein Basis-Update gezogen werden." >&2
    exit 1
fi

if [ ! -d "$repo_root/.git" ]; then
    echo "Kein Git-Repository in $repo_root. 'git init' (siehe README) ist Voraussetzung fuer diesen Workflow." >&2
    exit 1
fi

cd "$repo_root"

if [ -z "$current_slug" ]; then
    if [ ! -f "$repo_root/package.json" ]; then
        echo "Kein --current-slug angegeben und package.json fehlt, um es abzuleiten." >&2
        exit 1
    fi
    current_slug="$(node -e 'console.log(JSON.parse(require("fs").readFileSync(process.argv[1], "utf8")).name || "")' "$repo_root/package.json")"
fi

if [ -z "$current_slug" ]; then
    echo "Konnte CurrentSlug nicht ermitteln (package.json 'name' ist leer). Bitte --current-slug explizit angeben." >&2
    exit 1
fi

if [ -z "$current_prefix" ]; then
    current_prefix="${current_slug//-/_}_"
fi

if [ "$current_slug" = "$base_slug" ]; then
    echo "CurrentSlug ('$current_slug') entspricht BaseSlug ('$base_slug') - dieses Projekt scheint noch nicht umbenannt zu sein (siehe README 'Neues Projekt aus dieser Vorlage starten', Schritt 2: scripts/rename-theme.sh). Ohne abweichenden Slug/Praefix gibt es hier nichts zurueckzu-/wieder-umzubenennen." >&2
    exit 1
fi

git_status="$(git status --porcelain)"
if [ -n "$git_status" ]; then
    echo "Working Tree ist nicht sauber (uncommitted changes). Bitte erst committen/stashen, dann erneut ausfuehren." >&2
    exit 1
fi

original_branch="$(git rev-parse --abbrev-ref HEAD)"

echo "Aktueller Branch: $original_branch"
echo "Projekt-Naming:   $current_slug / $current_prefix"
echo "Basis-Remote:     $remote_name -> $remote_url ($remote_branch)"
echo ""

if git remote | grep -qx "$remote_name"; then
    git remote set-url "$remote_name" "$remote_url"
else
    git remote add "$remote_name" "$remote_url"
fi

echo "Hole $remote_name/$remote_branch ..."
git fetch "$remote_name" "$remote_branch"

if git merge-base --is-ancestor "$remote_name/$remote_branch" HEAD 2>/dev/null; then
    echo "Basis ist bereits aktuell - $remote_name/$remote_branch ist bereits in $original_branch enthalten. Nichts zu tun."
    exit 0
fi

temp_branch="base-update/$(date -u '+%Y%m%d-%H%M%S')"
echo "Erzeuge temporaeren Branch '$temp_branch' ..."
git checkout -b "$temp_branch"

echo ""
echo "--- Schritt 1/2: temporaer zurueck auf $base_slug/$base_prefix umbenennen ---"
"$rename_theme_script" --new-slug "$base_slug" --new-prefix "$base_prefix" --old-slug "$current_slug" --old-prefix "$current_prefix"

status_after_reverse="$(git status --porcelain)"
if [ -n "$status_after_reverse" ]; then
    git add -A
    git commit -m "chore: temporarily revert to $base_slug naming for upstream merge" --quiet
else
    echo "(keine Aenderungen - Projekt-Naming entsprach bereits $base_slug/$base_prefix)"
fi

echo ""
echo "--- Merge $remote_name/$remote_branch ---"
if ! git merge "$remote_name/$remote_branch" --allow-unrelated-histories --no-edit; then
    echo ""
    echo "Merge-Konflikte auf Branch '$temp_branch'. Manuell weitermachen:"
    echo "  1. Konflikte in den betroffenen Dateien aufloesen, dann:"
    echo "     git add <geloeste Dateien>"
    echo "     git commit --no-edit"
    echo "  2. Projekt-Naming wiederherstellen:"
    echo "     bash scripts/rename-theme.sh --new-slug $current_slug --new-prefix $current_prefix --old-slug $base_slug --old-prefix $base_prefix"
    echo "     git add -A; git commit -m \"chore: reapply $current_slug naming after base update\""
    echo "  3. Diff pruefen und in '$original_branch' mergen:"
    echo "     git diff $original_branch..$temp_branch"
    echo "     git checkout $original_branch; git merge $temp_branch"
    exit 1
fi

echo ""
echo "--- Schritt 2/2: $current_slug/$current_prefix wiederherstellen ---"
"$rename_theme_script" --new-slug "$current_slug" --new-prefix "$current_prefix" --old-slug "$base_slug" --old-prefix "$base_prefix"

status_after_forward="$(git status --porcelain)"
if [ -n "$status_after_forward" ]; then
    git add -A
    git commit -m "chore: reapply $current_slug naming after base update" --quiet
else
    echo "(keine Aenderungen)"
fi

echo ""
echo "Fertig. Basis-Update steht committet auf Branch '$temp_branch', dein Branch '$original_branch' ist"
echo "unveraendert. Vor dem Zusammenfuehren pruefen:"
echo ""
echo "  git diff $original_branch..$temp_branch"
echo ""
echo "Danach manuell mergen:"
echo ""
echo "  git checkout $original_branch"
echo "  git merge $temp_branch"
