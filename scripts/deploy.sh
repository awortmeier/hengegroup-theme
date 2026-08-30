#!/usr/bin/env bash
#
# macOS/Linux-Pendant zu deploy.ps1 (siehe docs/entscheidungen.md). Baut das Theme und laedt das
# komplette dist/-Verzeichnis per FTP(S) hoch (curl --upload-file, bis zu 3 Uploads parallel).
#
# Abweichung zum PowerShell-Original: dort wird nach dem ersten fehlgeschlagenen Upload nicht mehr
# nachgelegt (laufende Jobs werden noch fertig abgewartet, neue aber nicht mehr gestartet). Diese
# Fassung nutzt `xargs -P` fuer die Parallelisierung (portabel, kein bash>=4 noetig fuer
# Job-Kontrolle/`wait -n`) und laesst xargs dafuer alle bereits aufgereihten Uploads durchlaufen,
# auch wenn einer scheitert — danach wird trotzdem mit Exit-Code 1 abgebrochen. Ergebnis
# (Fehlschlag = nicht deployed) ist gleich, nur der Abbruchzeitpunkt etwas spaeter.
#
# Fortschrittsanzeige (Pendant zu Write-Progress im PowerShell-Original): in einem echten Terminal
# (stdout ist ein TTY) eine sich per \r ueberschreibende Zeile "[erledigt/gesamt] X% - Datei";
# ohne TTY (Log-Datei, CI) stattdessen eine Zeile pro Upload wie zuvor, damit das Log lesbar
# bleibt statt voller Steuerzeichen.
#
# --skip-build: ueberspringt den Build-Schritt (Pendant zu -SkipBuild), z. B. wenn dist/ bereits
# frisch gebaut ist.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
env_file_path="$repo_root/.env"
dist_path="$repo_root/dist"
manifest_path="$dist_path/assets/.vite/manifest.json"
build_script="$script_dir/build.sh"

skip_build=0
for arg in "$@"; do
    case "$arg" in
        --skip-build) skip_build=1 ;;
        *)
            echo "Unbekanntes Argument: $arg" >&2
            exit 1
            ;;
    esac
done

import_dotenv_file() {
    local path="$1"
    [ -f "$path" ] || return 0

    local line key value first_char last_char
    while IFS= read -r line || [ -n "$line" ]; do
        line="$(echo "$line" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
        [ -z "$line" ] && continue
        case "$line" in \#*) continue ;; esac

        case "$line" in
            *=*) ;;
            *) continue ;;
        esac

        key="${line%%=*}"
        key="$(echo "$key" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
        value="${line#*=}"
        value="$(echo "$value" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"

        first_char="${value:0:1}"
        last_char="${value: -1}"
        if { [ "$first_char" = '"' ] && [ "$last_char" = '"' ]; } || { [ "$first_char" = "'" ] && [ "$last_char" = "'" ]; }; then
            value="${value:1:${#value}-2}"
        fi

        if [ -z "${!key:-}" ]; then
            export "$key=$value"
        fi
    done <"$path"
}

require_env_vars() {
    local missing=() name
    for name in "$@"; do
        if [ -z "${!name:-}" ]; then
            missing+=("$name")
        fi
    done

    if [ "${#missing[@]}" -gt 0 ]; then
        local joined
        joined="$(printf ', %s' "${missing[@]}")"
        echo "Missing required environment variables: ${joined:2}" >&2
        exit 1
    fi
}

import_dotenv_file "$env_file_path"
require_env_vars FTP_HOST FTP_USER FTP_PASSWORD FTP_REMOTE_PATH

if ! command -v curl >/dev/null 2>&1; then
    echo "curl is required for deploys but was not found in PATH." >&2
    exit 1
fi

if [ "$skip_build" -eq 0 ]; then
    "$build_script"
fi

if [ ! -d "$dist_path" ]; then
    echo "Theme package directory not found: $dist_path. Run 'pnpm run build' first." >&2
    exit 1
fi

if [ ! -f "$manifest_path" ]; then
    echo "Vite manifest not found: $manifest_path. Run 'pnpm run build' first." >&2
    exit 1
fi

if ! node -e '
    const fs = require("fs");
    let manifest;
    try {
        manifest = JSON.parse(fs.readFileSync(process.argv[1], "utf8"));
    } catch {
        console.error("Invalid Vite manifest JSON: " + process.argv[1]);
        process.exit(1);
    }
    if (!Object.prototype.hasOwnProperty.call(manifest, "assets/js/app.js")) {
        console.error("Vite manifest is missing required app entry: assets/js/app.js");
        process.exit(1);
    }
' "$manifest_path"; then
    exit 1
fi

ftp_scheme="$(echo "${FTP_SCHEME:-ftp}" | tr '[:upper:]' '[:lower:]' | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
ftp_host="$(echo "$FTP_HOST" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')"
ftp_user="$FTP_USER"
ftp_password="$FTP_PASSWORD"
remote_base_path="$(echo "$FTP_REMOTE_PATH" | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's:^/*::' -e 's:/*$::')"

file_count=$(find "$dist_path" -type f | wc -l | tr -d '[:space:]')
if [ "$file_count" -eq 0 ]; then
    echo "Theme package directory is empty: $dist_path" >&2
    exit 1
fi

max_parallel_uploads=3
fail_log="$(mktemp)"
progress_log="$(mktemp)"
trap 'rm -f "$fail_log" "$progress_log"' EXIT

echo "Uploading $file_count file(s) from dist to $ftp_host/$remote_base_path ..."

is_tty=0
[ -t 1 ] && is_tty=1

total_files="$file_count"
activity_label="Uploading theme via FTP"
export dist_path ftp_scheme ftp_host remote_base_path ftp_user ftp_password fail_log progress_log total_files is_tty activity_label

find "$dist_path" -type f -print0 | sort -z | xargs -0 -P "$max_parallel_uploads" -I{} bash -c '
    file="$1"
    relative_path="${file#$dist_path/}"
    remote_url="${ftp_scheme}://${ftp_host}/${remote_base_path}/${relative_path}"

    if curl --ftp-create-dirs --user "${ftp_user}:${ftp_password}" --silent --show-error --fail --upload-file "$file" "$remote_url"; then
        echo "$relative_path" >>"$progress_log"
        completed=$(wc -l <"$progress_log" | tr -d "[:space:]")
        percent=$(( completed * 100 / total_files ))
        if [ "$is_tty" -eq 1 ]; then
            printf "\r\033[K%s: [%d/%d] %d%% - %s" "$activity_label" "$completed" "$total_files" "$percent" "$relative_path"
        else
            echo "[$completed/$total_files] Uploaded $relative_path"
        fi
    else
        echo "$relative_path" >>"$progress_log"
        [ "$is_tty" -eq 1 ] && printf "\n"
        echo "FTP upload failed for $relative_path" >&2
        echo "$relative_path" >>"$fail_log"
        exit 1
    fi
' _ {} || true

[ "$is_tty" -eq 1 ] && printf "\r\033[K"

if [ -s "$fail_log" ]; then
    failed_uploads="$(paste -sd ',' "$fail_log" | sed 's/,/, /g')"
    echo "FTP upload failed for $failed_uploads" >&2
    exit 1
fi

echo "Uploaded $file_count file(s) from dist to $ftp_host/$remote_base_path."
