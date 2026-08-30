$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$distPath = [System.IO.Path]::GetFullPath((Join-Path $repoRoot "dist"))
$cleanScriptPath = Join-Path $PSScriptRoot "clean.ps1"
$syncLucideIconsScriptPath = Join-Path $PSScriptRoot "sync-lucide-icons.ps1"
$syncTablerIconsScriptPath = Join-Path $PSScriptRoot "sync-tabler-icons.ps1"

# Nicht-PHP-Theme-Dateien mit festem Namen (WP-Konvention). PHP-Templates werden separat unten
# per *.php-Wildcard erfasst (siehe docs/entscheidungen.md), damit neue Top-Level-Templates nach
# WordPress-Template-Hierarchie (z. B. page-{slug}.php, single-{post-type}.php,
# category-{slug}.php) automatisch mitgebaut werden, ohne diese Liste pflegen zu muessen.
$themeStaticFiles = @(
    "style.css",
    "theme.json",
    "screenshot.png",
    "screenshot.jpg"
)

$themeDirectories = @(
    @{ Source = "inc"; Destination = "inc" },
    @{ Source = "template-parts"; Destination = "template-parts" },
    @{ Source = "languages"; Destination = "languages" },
    @{ Source = "assets/images"; Destination = "assets/images" }
)

if (-not (Test-Path -LiteralPath $distPath)) {
    New-Item -ItemType Directory -Path $distPath | Out-Null
}

& $cleanScriptPath
& $syncLucideIconsScriptPath
& $syncTablerIconsScriptPath

Push-Location $repoRoot
try {
    & pnpm.cmd run build:assets
    if ($LASTEXITCODE -ne 0) {
        throw "Asset build failed."
    }
}
finally {
    Pop-Location
}

foreach ($file in $themeStaticFiles) {
    $sourcePath = Join-Path $repoRoot $file
    if (-not (Test-Path -LiteralPath $sourcePath)) {
        continue
    }

    Copy-Item -LiteralPath $sourcePath -Destination (Join-Path $distPath $file) -Force
    Write-Output "Copied $file"
}

Get-ChildItem -LiteralPath $repoRoot -Filter "*.php" -File | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $distPath $_.Name) -Force
    Write-Output "Copied $($_.Name)"
}

foreach ($directory in $themeDirectories) {
    $sourcePath = Join-Path $repoRoot $directory.Source
    if (-not (Test-Path -LiteralPath $sourcePath)) {
        continue
    }

    $destinationPath = Join-Path $distPath $directory.Destination
    $destinationParent = Split-Path -Parent $destinationPath

    if (-not (Test-Path -LiteralPath $destinationParent)) {
        New-Item -ItemType Directory -Path $destinationParent -Force | Out-Null
    }

    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Recurse -Force
    Write-Output "Copied $($directory.Source)"
}
