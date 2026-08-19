<#
.SYNOPSIS
    Pulls later changes from the base-theme template repo into a project theme that was started
    from it (README "Neues Projekt aus dieser Vorlage starten"), on top of that project's own
    slug/prefix rename (`rename-theme.ps1`).

.DESCRIPTION
    A forked/renamed project theme no longer textually matches the base repo (rename-theme.ps1
    replaced every "base-theme"/"base_theme_" occurrence with the project's own slug/prefix), so a
    plain `git merge`/`git subtree pull` against the base repo would conflict on almost every line
    that rename touched, even when the actual base content is unchanged. This script works around
    that by doing the merge on a temporary branch where the naming matches again:

      1. Add/fetch the base repo as a git remote.
      2. Create a temporary branch off the current branch.
      3. Reverse-rename that branch back to base-theme/base_theme_ naming (rename-theme.ps1 with
         -OldSlug/-OldPrefix swapped), commit.
      4. Merge the base repo's branch in — now a real, content-only diff/merge.
      5. Re-apply the project's own slug/prefix naming (rename-theme.ps1 forward again), commit.

    Nothing is merged back into your actual branch automatically — review the temporary branch's
    diff yourself, then merge/rebase it into your branch (instructions are printed at the end).

    Requires a clean working tree to start (uncommitted changes are never touched, but the script
    needs to create/switch branches safely).

.PARAMETER CurrentSlug
    This project's current kebab-case slug (what rename-theme.ps1's -NewSlug was set to when the
    project was started). Defaults to package.json's "name" field.

.PARAMETER CurrentPrefix
    This project's current PHP function prefix. Defaults to $CurrentSlug with "-" -> "_" plus a
    trailing "_" — same derivation rename-theme.ps1 uses when -NewPrefix isn't given explicitly.
    Override if this project used a custom -NewPrefix.

.PARAMETER RemoteUrl
    Git URL of the base-theme template repo. Defaults to the base-theme project's own GitHub repo.

.PARAMETER RemoteBranch
    Branch on the base repo to pull from. Defaults to "main".

.PARAMETER RemoteName
    Local name for the base repo's git remote. Defaults to "base-theme-upstream" (added if missing,
    left alone/updated if it already exists).

.EXAMPLE
    powershell -File scripts/pull-base-updates.ps1

.EXAMPLE
    powershell -File scripts/pull-base-updates.ps1 -CurrentSlug "acme-shop" -CurrentPrefix "acme_shop_"
#>

[CmdletBinding()]
param(
    [string] $CurrentSlug,
    [string] $CurrentPrefix,

    [string] $RemoteUrl = "git@github.com:awortmeier/wordpress-base-theme.git",
    [string] $RemoteBranch = "main",
    [string] $RemoteName = "base-theme-upstream",

    [string] $BaseSlug = "base-theme",
    [string] $BasePrefix = "base_theme_"
)

$ErrorActionPreference = "Stop"

function Invoke-Git {
    # No "2>&1" here: Windows PowerShell 5.1 wraps a native command's stderr lines into
    # NativeCommandError records when redirected this way, which $ErrorActionPreference = "Stop"
    # then treats as a terminating error even for a successful git call (e.g. fetch's progress
    # output). Letting stderr flow straight to the console avoids that and is more useful anyway
    # for a script meant to be run interactively.
    param([string[]] $Arguments)
    $output = & git @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "git $($Arguments -join ' ') fehlgeschlagen (Exit-Code $LASTEXITCODE), siehe Ausgabe oben."
    }
    return $output
}

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
Set-Location $repoRoot

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    throw "git wurde nicht gefunden. Ohne git kann kein Basis-Update gezogen werden."
}

if (-not (Test-Path -LiteralPath (Join-Path $repoRoot ".git"))) {
    throw "Kein Git-Repository in $repoRoot. `git init` (siehe README) ist Voraussetzung fuer diesen Workflow."
}

if (-not $CurrentSlug) {
    $packageJsonPath = Join-Path $repoRoot "package.json"
    if (-not (Test-Path -LiteralPath $packageJsonPath)) {
        throw "Kein -CurrentSlug angegeben und package.json fehlt, um es abzuleiten."
    }
    $CurrentSlug = (Get-Content -LiteralPath $packageJsonPath -Raw -Encoding UTF8 | ConvertFrom-Json).name
}

if (-not $CurrentSlug) {
    throw "Konnte CurrentSlug nicht ermitteln (package.json 'name' ist leer). Bitte -CurrentSlug explizit angeben."
}

if (-not $CurrentPrefix) {
    $CurrentPrefix = ($CurrentSlug -replace '-', '_') + '_'
}

if ($CurrentSlug -eq $BaseSlug) {
    throw "CurrentSlug ('$CurrentSlug') entspricht BaseSlug ('$BaseSlug') - dieses Projekt scheint noch nicht umbenannt zu sein (siehe README 'Neues Projekt aus dieser Vorlage starten', Schritt 2: scripts/rename-theme.ps1). Ohne abweichenden Slug/Praefix gibt es hier nichts zurueckzu-/wieder-umzubenennen."
}

$gitStatus = Invoke-Git -Arguments @("status", "--porcelain")
if ($gitStatus) {
    throw "Working Tree ist nicht sauber (uncommitted changes). Bitte erst committen/stashen, dann erneut ausfuehren."
}

$originalBranch = (Invoke-Git -Arguments @("rev-parse", "--abbrev-ref", "HEAD")) | Select-Object -First 1

Write-Output "Aktueller Branch: $originalBranch"
Write-Output "Projekt-Naming:   $CurrentSlug / $CurrentPrefix"
Write-Output "Basis-Remote:     $RemoteName -> $RemoteUrl ($RemoteBranch)"
Write-Output ""

$existingRemotes = Invoke-Git -Arguments @("remote")
if ($existingRemotes -contains $RemoteName) {
    Invoke-Git -Arguments @("remote", "set-url", $RemoteName, $RemoteUrl) | Out-Null
} else {
    Invoke-Git -Arguments @("remote", "add", $RemoteName, $RemoteUrl) | Out-Null
}

Write-Output "Hole $RemoteName/$RemoteBranch ..."
Invoke-Git -Arguments @("fetch", $RemoteName, $RemoteBranch) | Out-Null

& git merge-base --is-ancestor "$RemoteName/$RemoteBranch" HEAD 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Output "Basis ist bereits aktuell - $RemoteName/$RemoteBranch ist bereits in $originalBranch enthalten. Nichts zu tun."
    exit 0
}

$tempBranch = "base-update/$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Write-Output "Erzeuge temporaeren Branch '$tempBranch' ..."
Invoke-Git -Arguments @("checkout", "-b", $tempBranch) | Out-Null

Write-Output ""
Write-Output "--- Schritt 1/2: temporaer zurueck auf $BaseSlug/$BasePrefix umbenennen ---"
& "$PSScriptRoot\rename-theme.ps1" -NewSlug $BaseSlug -NewPrefix $BasePrefix -OldSlug $CurrentSlug -OldPrefix $CurrentPrefix

$statusAfterReverse = Invoke-Git -Arguments @("status", "--porcelain")
if ($statusAfterReverse) {
    Invoke-Git -Arguments @("add", "-A") | Out-Null
    Invoke-Git -Arguments @("commit", "-m", "chore: temporarily revert to $BaseSlug naming for upstream merge", "--quiet") | Out-Null
} else {
    Write-Output "(keine Aenderungen - Projekt-Naming entsprach bereits $BaseSlug/$BasePrefix)"
}

Write-Output ""
Write-Output "--- Merge $RemoteName/$RemoteBranch ---"
& git merge "$RemoteName/$RemoteBranch" --allow-unrelated-histories --no-edit
$mergeExitCode = $LASTEXITCODE

if ($mergeExitCode -ne 0) {
    Write-Output ""
    Write-Output "Merge-Konflikte auf Branch '$tempBranch'. Manuell weitermachen:"
    Write-Output "  1. Konflikte in den betroffenen Dateien aufloesen, dann:"
    Write-Output "     git add <geloeste Dateien>"
    Write-Output "     git commit --no-edit"
    Write-Output "  2. Projekt-Naming wiederherstellen:"
    Write-Output "     powershell -File scripts/rename-theme.ps1 -NewSlug $CurrentSlug -NewPrefix $CurrentPrefix -OldSlug $BaseSlug -OldPrefix $BasePrefix"
    Write-Output "     git add -A; git commit -m `"chore: reapply $CurrentSlug naming after base update`""
    Write-Output "  3. Diff pruefen und in '$originalBranch' mergen:"
    Write-Output "     git diff $originalBranch..$tempBranch"
    Write-Output "     git checkout $originalBranch; git merge $tempBranch"
    exit 1
}

Write-Output ""
Write-Output "--- Schritt 2/2: $CurrentSlug/$CurrentPrefix wiederherstellen ---"
& "$PSScriptRoot\rename-theme.ps1" -NewSlug $CurrentSlug -NewPrefix $CurrentPrefix -OldSlug $BaseSlug -OldPrefix $BasePrefix

$statusAfterForward = Invoke-Git -Arguments @("status", "--porcelain")
if ($statusAfterForward) {
    Invoke-Git -Arguments @("add", "-A") | Out-Null
    Invoke-Git -Arguments @("commit", "-m", "chore: reapply $CurrentSlug naming after base update", "--quiet") | Out-Null
} else {
    Write-Output "(keine Aenderungen)"
}

Write-Output ""
Write-Output "Fertig. Basis-Update steht committet auf Branch '$tempBranch', dein Branch '$originalBranch' ist"
Write-Output "unveraendert. Vor dem Zusammenfuehren pruefen:"
Write-Output ""
Write-Output "  git diff $originalBranch..$tempBranch"
Write-Output ""
Write-Output "Danach manuell mergen:"
Write-Output ""
Write-Output "  git checkout $originalBranch"
Write-Output "  git merge $tempBranch"
