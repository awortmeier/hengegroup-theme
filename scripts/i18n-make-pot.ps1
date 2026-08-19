param()

$ErrorActionPreference = "Stop"

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$languagesPath = Join-Path $repoRoot "languages"
$potFilePath = Join-Path $languagesPath "hengegroup-theme.pot"

if (-not (Test-Path -LiteralPath $languagesPath)) {
    New-Item -ItemType Directory -Path $languagesPath -Force | Out-Null
}

$wpCommand = Get-Command -Name "wp" -ErrorAction SilentlyContinue
if ($null -eq $wpCommand) {
    throw "WP-CLI wurde nicht gefunden. Installiere 'wp' und fuehre dann 'pnpm i18n' erneut aus."
}

Push-Location $repoRoot
try {
    & $wpCommand.Source i18n make-pot . $potFilePath --domain=hengegroup-theme --exclude=node_modules,dist,.git,.deploy-state,languages
    if ($LASTEXITCODE -ne 0) {
        throw "WP-CLI konnte die POT-Datei nicht erzeugen."
    }
} finally {
    Pop-Location
}

Write-Host "Generated $potFilePath"
