<#
.SYNOPSIS
    Automates step 2 (and optionally 3/4) of "Neues Projekt aus dieser Vorlage starten" in README.md:
    renames the theme's slug/text-domain and PHP function prefix throughout the codebase.

.PARAMETER NewSlug
    New kebab-case slug, e.g. "acme-shop". Replaces every literal occurrence of -OldSlug
    (text domain, enqueue handles, i18n domain, package.json name, ...).

.PARAMETER NewPrefix
    New PHP function prefix, must end with an underscore, e.g. "acme_shop_". Replaces every
    literal occurrence of -OldPrefix. Defaults to $NewSlug with "-" -> "_" plus a trailing "_".

.PARAMETER OldSlug
    Slug to replace, defaults to "base-theme". Override when reversing a previous rename (e.g.
    scripts/pull-base-updates.ps1 calls this with -OldSlug/-NewSlug swapped to temporarily restore
    base-theme naming before merging upstream, then swaps back).

.PARAMETER OldPrefix
    PHP function prefix to replace, defaults to "base_theme_". Same reversal use case as -OldSlug.

.PARAMETER ThemeName
.PARAMETER ThemeUri
.PARAMETER Description
.PARAMETER Author
.PARAMETER AuthorUri
    Optional — when given, also patch the corresponding style.css theme header field
    (README step 3). Omitted fields are left untouched.

.PARAMETER DryRun
    Preview which files would change and how many replacements each contains, without
    writing anything.

.EXAMPLE
    powershell -File scripts/rename-theme.ps1 -NewSlug "acme-shop" -DryRun

.EXAMPLE
    powershell -File scripts/rename-theme.ps1 -NewSlug "acme-shop" `
        -ThemeName "Acme Shop" -ThemeUri "https://acme.example/" -AuthorUri "https://agency.example/"
#>

[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [string] $NewSlug,

    [string] $NewPrefix,

    [string] $OldSlug = "base-theme",
    [string] $OldPrefix = "base_theme_",

    [string] $ThemeName,
    [string] $ThemeUri,
    [string] $Description,
    [string] $Author,
    [string] $AuthorUri,

    [switch] $DryRun
)

$ErrorActionPreference = "Stop"

if ($NewSlug -notmatch '^[a-z0-9]+(-[a-z0-9]+)*$') {
    throw "NewSlug muss kebab-case sein (nur a-z, 0-9, Bindestrich), z. B. 'acme-shop'. Erhalten: '$NewSlug'"
}

if (-not $NewPrefix) {
    $NewPrefix = ($NewSlug -replace '-', '_') + '_'
}

if ($NewPrefix -notmatch '^[a-z0-9_]+_$') {
    throw "NewPrefix muss aus a-z, 0-9, Unterstrich bestehen und mit '_' enden, z. B. 'acme_shop_'. Erhalten: '$NewPrefix'"
}

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))

$excludedDirectoryNames = @("node_modules", "dist", ".git", ".deploy-state", "vendor")
$includedExtensions = @(".php", ".css", ".js", ".mjs", ".json", ".md", ".ps1", ".txt", ".xml")

# This script itself, pull-base-updates.ps1, and template-init.yml are excluded: all three are
# fixed, repeatable bootstrap/update steps whose job is always -OldSlug/-OldPrefix -> whatever you
# pass in. rename-theme.ps1 must not rewrite its own -OldSlug/-OldPrefix *default* literals
# ("base-theme"/"base_theme_"), or a later re-run without explicit -OldSlug/-OldPrefix would
# silently look for the wrong "old" identifier. pull-base-updates.ps1 has the same problem one
# level removed: its -BaseSlug/-BasePrefix/-RemoteUrl/-RemoteName defaults must keep pointing at
# the literal base-theme repo even after *this* project has been renamed away from it — otherwise
# it would try to merge a project's own fork with itself instead of the actual upstream base.
# template-init.yml compares package.json's *pre-rename* name against the literal "base-theme"
# sentinel and points its github.repository guard at the literal upstream repo — both must stay
# unrenamed too. Currently also protected by accident (.yml isn't in $includedExtensions below),
# but listed explicitly so that stays true even if .yml/.yaml is ever added to that list for some
# other file.
$excludedRelativeFiles = @(
    "scripts\rename-theme.ps1", "scripts/rename-theme.ps1",
    "scripts\pull-base-updates.ps1", "scripts/pull-base-updates.ps1",
    ".github\workflows\template-init.yml", ".github/workflows/template-init.yml"
)

$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

function Test-IsExcludedPath {
    param([string] $FullPath, [string] $RepoRoot, [string[]] $ExcludedDirectoryNames, [string[]] $ExcludedRelativeFiles)

    $relative = $FullPath.Substring($RepoRoot.Length).TrimStart('\', '/')

    if ($ExcludedRelativeFiles -contains $relative) {
        return $true
    }

    $segments = $relative -split '[\\/]'

    foreach ($segment in $segments) {
        if ($ExcludedDirectoryNames -contains $segment) {
            return $true
        }
    }

    return $false
}

$allFiles = Get-ChildItem -LiteralPath $repoRoot -Recurse -File -Force |
    Where-Object {
        $includedExtensions -contains $_.Extension.ToLowerInvariant() -and
        -not (Test-IsExcludedPath -FullPath $_.FullName -RepoRoot $repoRoot -ExcludedDirectoryNames $excludedDirectoryNames -ExcludedRelativeFiles $excludedRelativeFiles)
    }

$changedFiles = New-Object System.Collections.Generic.List[string]
$totalReplacements = 0

foreach ($file in $allFiles) {
    $original = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)

    $prefixMatches = ([regex]::Matches($original, [regex]::Escape($OldPrefix))).Count
    $slugMatches = ([regex]::Matches($original, [regex]::Escape($OldSlug))).Count

    if ($prefixMatches -eq 0 -and $slugMatches -eq 0) {
        continue
    }

    $updated = $original.Replace($OldPrefix, $NewPrefix).Replace($OldSlug, $NewSlug)

    $relativePath = $file.FullName.Substring($repoRoot.Length).TrimStart('\', '/')
    $fileReplacements = $prefixMatches + $slugMatches
    $totalReplacements += $fileReplacements
    $changedFiles.Add("$relativePath ($fileReplacements)")

    if (-not $DryRun) {
        [System.IO.File]::WriteAllText($file.FullName, $updated, $utf8NoBom)
    }
}

$styleCssPath = Join-Path $repoRoot "style.css"
$styleCssHeaderFields = @{
    "Theme Name"  = $ThemeName
    "Theme URI"   = $ThemeUri
    "Description" = $Description
    "Author"      = $Author
    "Author URI"  = $AuthorUri
}
$styleCssHeaderChanges = New-Object System.Collections.Generic.List[string]

if ((Test-Path -LiteralPath $styleCssPath) -and ($styleCssHeaderFields.Values | Where-Object { $_ })) {
    $styleCss = [System.IO.File]::ReadAllText($styleCssPath, [System.Text.Encoding]::UTF8)

    foreach ($fieldName in $styleCssHeaderFields.Keys) {
        $value = $styleCssHeaderFields[$fieldName]
        if (-not $value) {
            continue
        }

        $pattern = "(?m)^$([regex]::Escape($fieldName)):.*$"
        $replacement = "$($fieldName): $value"

        if ($styleCss -match $pattern) {
            $styleCss = [regex]::Replace($styleCss, $pattern, { param($m) $replacement }, 1)
            $styleCssHeaderChanges.Add($fieldName)
        }
    }

    if ($styleCssHeaderChanges.Count -gt 0 -and -not $DryRun) {
        [System.IO.File]::WriteAllText($styleCssPath, $styleCss, $utf8NoBom)
    }
}

Write-Output "Slug: $OldSlug -> $NewSlug"
Write-Output "Praefix: $OldPrefix -> $NewPrefix"
Write-Output ""

if ($changedFiles.Count -eq 0) {
    Write-Output "Keine Dateien mit '$OldSlug' oder '$OldPrefix' gefunden."
} else {
    foreach ($entry in $changedFiles) {
        Write-Output "  $entry"
    }
    Write-Output ""
    Write-Output "$($changedFiles.Count) Datei(en), $totalReplacements Ersetzung(en) insgesamt."
}

if ($styleCssHeaderChanges.Count -gt 0) {
    Write-Output ""
    Write-Output "style.css-Header aktualisiert: $($styleCssHeaderChanges -join ', ')"
}

if ($DryRun) {
    Write-Output ""
    Write-Output "Dry-Run: keine Datei wurde geschrieben. Ohne -DryRun ausfuehren, um die Aenderungen zu uebernehmen."
} else {
    Write-Output ""
    Write-Output "Fertig. Verbleibend laut README: package.json 'name' pruefen (wird durch den Slug bereits mit umbenannt,"
    Write-Output "falls PackageName = NewSlug gewuenscht war), eigenes Logo setzen, .env aus .env.example anlegen."
}
