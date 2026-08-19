[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$iconScannerPath = Join-Path $PSScriptRoot "find-lucide-icons.php"
$sourceDirectory = Join-Path $repoRoot "node_modules\lucide-static\icons"
$destinationDirectory = Join-Path $repoRoot "assets\images\icons\lucide"

if (-not (Test-Path -LiteralPath $sourceDirectory)) {
    throw "lucide-static wurde nicht gefunden: $sourceDirectory. Fuehre 'pnpm install' aus."
}

if (-not (Test-Path -LiteralPath $iconScannerPath)) {
    throw "Icon-Scanner nicht gefunden: $iconScannerPath"
}

# Ermittelt die benoetigten Icon-Namen automatisch aus den Templates (siehe
# find-lucide-icons.php), ergaenzt um alles, was zusaetzlich in scripts/lucide-icons.json
# gelistet ist (fuer Icons, die noch nicht im Code referenziert werden).
$scanOutputLines = & php $iconScannerPath
if ($LASTEXITCODE -ne 0) {
    throw "Icon-Scan fehlgeschlagen: php $iconScannerPath"
}

$scanOutputJson = $scanOutputLines -join "`n"
$iconNames = ConvertFrom-Json -InputObject $scanOutputJson

if ($iconNames -isnot [System.Array]) {
    $iconNames = @($iconNames)
}

if (-not (Test-Path -LiteralPath $destinationDirectory)) {
    New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
}

# Vorher aufraeumen, damit im Code nicht mehr referenzierte Icons nicht als Leichen liegen bleiben.
Get-ChildItem -LiteralPath $destinationDirectory -Filter "*.svg" -File -ErrorAction SilentlyContinue |
    Remove-Item -Force

if ($iconNames.Count -eq 0) {
    Write-Output "Keine Lucide-Icon-Referenzen im Theme-Code gefunden. Nichts zu synchronisieren."
    return
}

$missingIcons = New-Object System.Collections.Generic.List[string]

foreach ($iconName in $iconNames) {
    $iconName = [string] $iconName
    if ([string]::IsNullOrWhiteSpace($iconName)) {
        continue
    }

    $sourcePath = Join-Path $sourceDirectory "$iconName.svg"

    if (-not (Test-Path -LiteralPath $sourcePath)) {
        $missingIcons.Add($iconName)
        continue
    }

    Copy-Item -LiteralPath $sourcePath -Destination (Join-Path $destinationDirectory "$iconName.svg") -Force
    Write-Output "Synced $iconName"
}

if ($missingIcons.Count -gt 0) {
    throw "Unbekannte Lucide-Icon-Namen im Theme-Code referenziert: $($missingIcons -join ', ')"
}

Write-Output "Fertig: $($iconNames.Count) Icon(s) nach $destinationDirectory synchronisiert."
