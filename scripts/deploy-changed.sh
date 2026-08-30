#!/usr/bin/env bash
#
# macOS/Linux-Pendant zu deploy-changed.ps1 (siehe docs/entscheidungen.md). Wie deploy.sh, laedt
# aber nur Dateien hoch, deren SHA-256-Hash sich seit dem letzten Deploy geaendert hat (Zustand in
# .deploy-state/ftp-theme-state.json, gebunden an FTP_REMOTE_PATH). Gleiche Abweichung zum
# PowerShell-Original wie deploy.sh: xargs -P statt Job-Kontrolle, siehe dort fuer die Begruendung.
# Gleiche Fortschrittsanzeige (Write-Progress-Pendant) wie deploy.sh, siehe dort fuer Details.
#
# --skip-build: ueberspringt den Build-Schritt (Pendant zu -SkipBuild).
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/.." && pwd)"
env_file_path="$repo_root/.env"
dist_path="$repo_root/dist"
manifest_path="$dist_path/assets/.vite/manifest.json"
build_script="$script_dir/build.sh"
state_file_path="$repo_root/.deploy-state/ftp-theme-state.json"

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

# relativePath<TAB>sha256 fuer jede Datei im aktuellen dist/-Build.
current_state="$(
    find "$dist_path" -type f | LC_ALL=C sort | while IFS= read -r file; do
        relative_path="${file#"$dist_path"/}"
        hash="$(shasum -a 256 "$file" | awk '{print $1}')"
        printf '%s\t%s\n' "$relative_path" "$hash"
    done
)"

# Vorheriger Stand aus state_file_path, nur wenn er zum aktuellen FTP_REMOTE_PATH gehoert; sonst
# leer (= alles gilt als geaendert), analog Get-StateMap in deploy-changed.ps1.
previous_state="$(node -e '
    const fs = require("fs");
    const [stateFilePath, remoteBasePath] = process.argv.slice(1);

    if (!fs.existsSync(stateFilePath)) {
        process.exit(0);
    }

    let state;
    try {
        state = JSON.parse(fs.readFileSync(stateFilePath, "utf8"));
    } catch {
        console.error("Ignoring invalid deploy state file: " + stateFilePath);
        process.exit(0);
    }

    if (!state || state.remoteBasePath !== remoteBasePath || !Array.isArray(state.files)) {
        process.exit(0);
    }

    for (const entry of state.files) {
        if (entry && entry.relativePath && entry.hash) {
            process.stdout.write(`${entry.relativePath}\t${entry.hash}\n`);
        }
    }
' "$state_file_path" "$remote_base_path")"

# Diff: nur Dateien, die in previous_state fehlen oder einen anderen Hash haben. Ueber temporaere
# Dateien statt stdin, weil Bash-Variablen keine NUL-Bytes als Trenner zwischen den beiden
# Zustaenden transportieren koennen.
current_state_file="$(mktemp)"
previous_state_file="$(mktemp)"
printf '%s' "$current_state" >"$current_state_file"
printf '%s' "$previous_state" >"$previous_state_file"

files_to_upload="$(
    node -e '
        const fs = require("fs");
        const [currentStateFile, previousStateFile] = process.argv.slice(1);

        const parseMap = (raw) => {
            const map = new Map();
            for (const line of raw.split("\n")) {
                if (!line) continue;
                const [relativePath, hash] = line.split("\t");
                map.set(relativePath, hash);
            }
            return map;
        };

        const current = parseMap(fs.readFileSync(currentStateFile, "utf8"));
        const previous = parseMap(fs.readFileSync(previousStateFile, "utf8"));

        for (const [relativePath, hash] of current) {
            if (previous.get(relativePath) !== hash) {
                console.log(relativePath);
            }
        }
    ' "$current_state_file" "$previous_state_file"
)"
rm -f "$current_state_file" "$previous_state_file"

if [ -z "$files_to_upload" ]; then
    echo "No changed files detected in dist. Nothing to upload."
    exit 0
fi

changed_count=$(printf '%s\n' "$files_to_upload" | wc -l | tr -d '[:space:]')

max_parallel_uploads=3
fail_log="$(mktemp)"
progress_log="$(mktemp)"
trap 'rm -f "$fail_log" "$progress_log"' EXIT

echo "Uploading $changed_count changed file(s) from dist to $ftp_host/$remote_base_path ..."

is_tty=0
[ -t 1 ] && is_tty=1

total_files="$changed_count"
activity_label="Uploading changed theme files via FTP"
export dist_path ftp_scheme ftp_host remote_base_path ftp_user ftp_password fail_log progress_log total_files is_tty activity_label

printf '%s\n' "$files_to_upload" | xargs -P "$max_parallel_uploads" -I{} bash -c '
    relative_path="$1"
    file="$dist_path/$relative_path"
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

# Neuen Stand nur bei vollstaendigem Erfolg schreiben (gleiches Verhalten wie Save-StateMap: state
# spiegelt immer den zuletzt tatsaechlich hochgeladenen dist/-Inhalt).
mkdir -p "$(dirname "$state_file_path")"
node -e '
    const fs = require("fs");
    const [stateFilePath, remoteBasePath] = process.argv.slice(1);
    const currentState = fs.readFileSync(0, "utf8");

    const files = currentState
        .split("\n")
        .filter(Boolean)
        .map((line) => {
            const [relativePath, hash] = line.split("\t");
            return { relativePath, hash };
        })
        .sort((a, b) => a.relativePath.localeCompare(b.relativePath));

    const state = {
        remoteBasePath,
        updatedAt: new Date().toISOString(),
        files,
    };

    fs.writeFileSync(stateFilePath, JSON.stringify(state, null, 2) + "\n", "utf8");
' "$state_file_path" "$remote_base_path" <<<"$current_state"

echo "Uploaded $changed_count changed file(s) from dist to $ftp_host/$remote_base_path."
