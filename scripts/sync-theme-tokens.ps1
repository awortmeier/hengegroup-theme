<#
.SYNOPSIS
    Spiegelt die Marken-Akzentfarbe aus assets/css/tokens.css (--color-accent) in theme.json
    (settings.color.palette "accent"-Eintrag + styles.elements.link.color.text). tokens.css ist die
    Single Source of Truth (siehe deren Kopfkommentar) — theme.json kann sie nicht importieren
    (reines JSON, kein CSS-Pipeline-Zugriff), daher dieser Sync per Skript statt von Hand.

.DESCRIPTION
    Reine Text-/Regex-Ersetzung auf beiden Dateien, kein JSON-Parse/Reserialize-Roundtrip — das
    wuerde die bestehende Prettier-Formatierung von theme.json durcheinanderbringen (Key-Reihenfolge,
    Einrueckung, Escaping). Nach dem Schreiben wird das Ergebnis trotzdem als JSON geparst, um
    sicherzustellen, dass theme.json dabei nicht kaputt geht.

    Manuell auszufuehren, nachdem --color-accent in tokens.css geaendert wurde (z. B. Schritt 5 von
    README "Neues Projekt aus dieser Vorlage starten" bzw. setup.md).

.EXAMPLE
    powershell -File scripts/sync-theme-tokens.ps1
#>

[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$tokensPath = Join-Path $repoRoot "assets\css\tokens.css"
$themeJsonPath = Join-Path $repoRoot "theme.json"

if (-not (Test-Path -LiteralPath $tokensPath)) {
    throw "tokens.css nicht gefunden: $tokensPath"
}
if (-not (Test-Path -LiteralPath $themeJsonPath)) {
    throw "theme.json nicht gefunden: $themeJsonPath"
}

$tokensCss = [System.IO.File]::ReadAllText($tokensPath, [System.Text.Encoding]::UTF8)

$accentMatch = [regex]::Match($tokensCss, '--color-accent:\s*([^;]+?)\s*;')
if (-not $accentMatch.Success) {
    throw "Kein '--color-accent:'-Eintrag in tokens.css gefunden."
}
$accentColor = $accentMatch.Groups[1].Value

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
$themeJson = [System.IO.File]::ReadAllText($themeJsonPath, [System.Text.Encoding]::UTF8)
$originalThemeJson = $themeJson

# settings.color.palette[] Eintrag mit "slug": "accent" -> dessen "color"-Wert.
$palettePattern = '(?s)("slug":\s*"accent".*?"color":\s*)"#?[0-9a-fA-F]{3,8}"'
if ($themeJson -notmatch $palettePattern) {
    throw "Kein 'accent'-Palette-Eintrag in theme.json gefunden (settings.color.palette)."
}
$themeJson = [regex]::Replace($themeJson, $palettePattern, { param($m) "$($m.Groups[1].Value)`"$accentColor`"" }, 1)

# styles.elements.link.color.text -> Accent-Farbe (nicht styles.color.text, das ist eine andere Stelle).
$linkPattern = '(?s)("link":\s*\{\s*"color":\s*\{\s*"text":\s*)"#?[0-9a-fA-F]{3,8}"'
if ($themeJson -notmatch $linkPattern) {
    throw "Kein styles.elements.link.color.text-Eintrag in theme.json gefunden."
}
$themeJson = [regex]::Replace($themeJson, $linkPattern, { param($m) "$($m.Groups[1].Value)`"$accentColor`"" }, 1)

if ($themeJson -eq $originalThemeJson) {
    Write-Output "theme.json ist bereits auf $accentColor synchronisiert - nichts zu tun."
    exit 0
}

# Sicherheitsnetz: sicherstellen, dass das Ergebnis noch gueltiges JSON ist, bevor es geschrieben wird.
try {
    $themeJson | ConvertFrom-Json -ErrorAction Stop | Out-Null
} catch {
    throw "Ersetzung haette theme.json invalide gemacht, breche ab ohne zu schreiben: $($_.Exception.Message)"
}

[System.IO.File]::WriteAllText($themeJsonPath, $themeJson, $utf8NoBom)
Write-Output "theme.json synchronisiert: settings.color.palette 'accent' / styles.elements.link.color.text -> $accentColor"
