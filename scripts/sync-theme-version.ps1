<#
.SYNOPSIS
    Spiegelt die Versionsnummer aus package.json ("version") in den style.css-WordPress-Theme-Header
    ("Version:"-Feld). package.json ist die Single Source of Truth (siehe README "Versionierung").

.DESCRIPTION
    Gedacht als "version"-Lifecycle-Script fuer `pnpm version <patch|minor|major>` (siehe
    package.json "scripts.version") — npm/pnpm bumpen dabei zuerst package.json, fuehren dann dieses
    Skript aus, bevor sie den Versions-Commit + Git-Tag erzeugen. Alles, was dieses Skript per
    `git add` staged, landet automatisch im selben Commit wie der package.json-Bump.

    Kann auch manuell aufgerufen werden (`pnpm run sync-theme-version`), z. B. um eine von Hand in
    package.json geaenderte Version nachzuziehen, ohne `pnpm version` (inkl. Commit/Tag) auszuloesen.

.PARAMETER NoGitAdd
    Ueberspringt `git add style.css` — nur fuer den manuellen Aufruf ausserhalb des
    Versions-Lifecycles sinnvoll, wo kein automatischer Commit folgt.
#>

[CmdletBinding()]
param(
    [switch] $NoGitAdd
)

$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$packageJsonPath = Join-Path $repoRoot "package.json"
$styleCssPath = Join-Path $repoRoot "style.css"

if (-not (Test-Path -LiteralPath $packageJsonPath)) {
    throw "package.json nicht gefunden: $packageJsonPath"
}
if (-not (Test-Path -LiteralPath $styleCssPath)) {
    throw "style.css nicht gefunden: $styleCssPath"
}

$packageJson = Get-Content -LiteralPath $packageJsonPath -Raw -Encoding UTF8 | ConvertFrom-Json
$version = $packageJson.version

if (-not $version) {
    throw "package.json enthaelt kein 'version'-Feld."
}

if ($version -notmatch '^\d+\.\d+\.\d+') {
    throw "package.json 'version' sieht nicht wie SemVer aus: '$version'"
}

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$styleCss = [System.IO.File]::ReadAllText($styleCssPath, [System.Text.Encoding]::UTF8)

$pattern = "(?m)^Version:.*$"
if ($styleCss -notmatch $pattern) {
    throw "style.css enthaelt kein 'Version:'-Feld im Theme-Header."
}

$updated = [regex]::Replace($styleCss, $pattern, { "Version: $version" }, 1)

if ($updated -eq $styleCss) {
    Write-Output "style.css ist bereits auf Version $version - nichts zu tun."
    exit 0
}

[System.IO.File]::WriteAllText($styleCssPath, $updated, $utf8NoBom)
Write-Output "style.css 'Version:' -> $version"

if (-not $NoGitAdd) {
    $gitCommand = Get-Command git -ErrorAction SilentlyContinue
    if ($gitCommand) {
        git -C $repoRoot add -- style.css
    }
}
