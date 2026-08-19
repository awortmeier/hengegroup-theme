[CmdletBinding()]
param()

$ErrorActionPreference = "Stop"

$distPath = Join-Path $PSScriptRoot "..\\dist"
$distPath = [System.IO.Path]::GetFullPath($distPath)
$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))

if (-not $distPath.StartsWith($repoRoot, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to clean a dist path outside the repository: $distPath"
}

if (-not (Test-Path -LiteralPath $distPath)) {
    Write-Output "Nothing to clean. Dist folder not found: $distPath"
    exit 0
}

$targets = Get-ChildItem -LiteralPath $distPath -Force -ErrorAction SilentlyContinue

if (-not $targets) {
    Write-Output "Dist folder is already empty: $distPath"
    exit 0
}

foreach ($target in $targets) {
    Remove-Item -LiteralPath $target.FullName -Recurse -Force
    Write-Output "Removed $($target.FullName)"
}
