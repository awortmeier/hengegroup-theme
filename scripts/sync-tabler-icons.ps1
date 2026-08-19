[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$iconScannerPath = Join-Path $PSScriptRoot "find-tabler-icons.php"
$sourceRootDirectory = Join-Path $repoRoot "node_modules\@tabler\icons\icons"
$outlineDestinationDirectory = Join-Path $repoRoot "assets\images\icons\tabler\outline"
$filledDestinationDirectory = Join-Path $repoRoot "assets\images\icons\tabler\filled"

if (-not (Test-Path -LiteralPath $sourceRootDirectory)) {
    throw "@tabler/icons wurde nicht gefunden: $sourceRootDirectory. Fuehre 'pnpm install' aus."
}

if (-not (Test-Path -LiteralPath $iconScannerPath)) {
    throw "Icon-Scanner nicht gefunden: $iconScannerPath"
}

# Ermittelt die benoetigten Icons automatisch aus den Templates (siehe find-tabler-icons.php),
# ergaenzt um alles, was zusaetzlich in scripts/tabler-icons.json gelistet ist.
$scanOutputLines = & php $iconScannerPath
if ($LASTEXITCODE -ne 0) {
    throw "Icon-Scan fehlgeschlagen: php $iconScannerPath"
}

$scanOutputJson = $scanOutputLines -join "`n"
$requestedIcons = ConvertFrom-Json -InputObject $scanOutputJson

if ($requestedIcons -isnot [System.Array]) {
    $requestedIcons = @($requestedIcons)
}

foreach ($destinationDirectory in @($outlineDestinationDirectory, $filledDestinationDirectory)) {
    if (-not (Test-Path -LiteralPath $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    }

    # Vorher aufraeumen, damit nicht mehr benoetigte Icons nicht als Leichen liegen bleiben.
    Get-ChildItem -LiteralPath $destinationDirectory -Filter "*.svg" -File -ErrorAction SilentlyContinue |
        Remove-Item -Force
}

if ($requestedIcons.Count -eq 0) {
    Write-Output "Keine Tabler-Icon-Referenzen (Code oder tabler-icons.json) gefunden. Nichts zu synchronisieren."
    return
}

$missingIcons = New-Object System.Collections.Generic.List[string]
$syncedCount = 0

foreach ($icon in $requestedIcons) {
    $name = [string] $icon.name
    $variant = [string] $icon.variant

    if ([string]::IsNullOrWhiteSpace($name) -or [string]::IsNullOrWhiteSpace($variant)) {
        continue
    }

    $sourcePath = Join-Path $sourceRootDirectory "$variant\$name.svg"

    if (-not (Test-Path -LiteralPath $sourcePath)) {
        $missingIcons.Add("$name ($variant)")
        continue
    }

    $destinationDirectory = if ($variant -eq "filled") { $filledDestinationDirectory } else { $outlineDestinationDirectory }
    Copy-Item -LiteralPath $sourcePath -Destination (Join-Path $destinationDirectory "$name.svg") -Force
    Write-Output "Synced $name ($variant)"
    $syncedCount++
}

if ($missingIcons.Count -gt 0) {
    throw "Unbekannte Tabler-Icon-Referenzen im Theme-Code oder in tabler-icons.json: $($missingIcons -join ', ')"
}

Write-Output "Fertig: $syncedCount Icon(s) nach $outlineDestinationDirectory / $filledDestinationDirectory synchronisiert."
