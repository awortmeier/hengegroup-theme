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

# Ships a production-only vendor/ (enshrined/svg-sanitize, no phpcs/PHPUnit/wordpress-stubs/
# brain-monkey -- see docs/entscheidungen.md "SVG-Upload-Support") inside dist/: this theme
# deploys as a fertiges Bundle per FTP (siehe deploy/deploy-changed in package.json), keine
# Server-seitige `composer install`. Swaps the REPO's OWN vendor/ to --no-dev just long enough to
# copy it, then restores the dev vendor/ (phpcs/PHPUnit/...) in `finally` so `composer lint`/
# `composer test` keep working locally afterwards -- even if a step below throws.
Push-Location $repoRoot
try {
    & composer install --no-dev --optimize-autoloader
    if ($LASTEXITCODE -ne 0) {
        throw "Production-only composer install failed."
    }

    $vendorDestinationPath = Join-Path $distPath "vendor"
    if (Test-Path -LiteralPath $vendorDestinationPath) {
        Remove-Item -LiteralPath $vendorDestinationPath -Recurse -Force
    }
    Copy-Item -LiteralPath (Join-Path $repoRoot "vendor") -Destination $vendorDestinationPath -Recurse -Force
    Write-Output "Copied vendor (production-only)"
}
finally {
    & composer install
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
