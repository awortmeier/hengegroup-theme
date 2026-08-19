param(
    [switch] $SkipBuild
)

$ErrorActionPreference = "Stop"

function Start-UploadJob {
    param(
        [Parameter(Mandatory = $true)]
        [string] $RelativePath,
        [Parameter(Mandatory = $true)]
        [string] $FilePath,
        [Parameter(Mandatory = $true)]
        [string] $RemoteUrl,
        [Parameter(Mandatory = $true)]
        [string[]] $CurlArgsBase,
        [Parameter(Mandatory = $true)]
        [bool] $UseThreadJobs
    )

    $jobScript = {
        param(
            [string] $InnerRelativePath,
            [string] $InnerFilePath,
            [string] $InnerRemoteUrl,
            [string[]] $InnerCurlArgsBase
        )

        & curl.exe @InnerCurlArgsBase --upload-file $InnerFilePath $InnerRemoteUrl
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { [int] $LASTEXITCODE }

        if ($exitCode -ne 0) {
            return [pscustomobject]@{
                RelativePath = $InnerRelativePath
                Success = $false
                ExitCode = $exitCode
                ErrorMessage = "FTP upload failed for $InnerRelativePath"
            }
        }

        return [pscustomobject]@{
            RelativePath = $InnerRelativePath
            Success = $true
            ExitCode = 0
            ErrorMessage = ""
        }
    }

    $jobArguments = @($RelativePath, $FilePath, $RemoteUrl, $CurlArgsBase)
    if ($UseThreadJobs) {
        return Start-ThreadJob -Name $RelativePath -ScriptBlock $jobScript -ArgumentList $jobArguments
    }

    return Start-Job -Name $RelativePath -ScriptBlock $jobScript -ArgumentList $jobArguments
}

function Receive-UploadJobResult {
    param(
        [Parameter(Mandatory = $true)]
        [System.Management.Automation.Job] $Job
    )

    try {
        $result = Receive-Job -Job $Job -Wait -ErrorAction Stop
    } catch {
        $message = $_.Exception.Message
        if ([string]::IsNullOrWhiteSpace($message)) {
            $message = "FTP upload failed for $($Job.Name)"
        }

        return [pscustomobject]@{
            RelativePath = $Job.Name
            Success = $false
            ExitCode = 1
            ErrorMessage = $message
        }
    } finally {
        Remove-Job -Job $Job -Force -ErrorAction SilentlyContinue
    }

    if ($null -eq $result) {
        return [pscustomobject]@{
            RelativePath = $Job.Name
            Success = $false
            ExitCode = 1
            ErrorMessage = "FTP upload produced no result for $($Job.Name)"
        }
    }

    return $result
}

function Import-DotEnvFile {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    $envLines = Get-Content -LiteralPath $Path
    foreach ($envLine in $envLines) {
        $trimmedLine = $envLine.Trim()
        if ($trimmedLine -eq "" -or $trimmedLine.StartsWith("#")) {
            continue
        }

        $separatorIndex = $trimmedLine.IndexOf("=")
        if ($separatorIndex -lt 1) {
            continue
        }

        $key = $trimmedLine.Substring(0, $separatorIndex).Trim()
        $value = $trimmedLine.Substring($separatorIndex + 1).Trim()

        if (
            ($value.StartsWith('"') -and $value.EndsWith('"')) -or
            ($value.StartsWith("'") -and $value.EndsWith("'"))
        ) {
            $value = $value.Substring(1, $value.Length - 2)
        }

        $existingValue = [string] (Get-Item -Path "Env:$key" -ErrorAction SilentlyContinue).Value
        if ([string]::IsNullOrWhiteSpace($existingValue)) {
            Set-Item -Path "Env:$key" -Value $value
        }
    }
}

function Test-RequiredEnvVars {
    param(
        [Parameter(Mandatory = $true)]
        [string[]] $Names
    )

    $missingEnvVars = @()
    foreach ($envVar in $Names) {
        $value = [string] (Get-Item -Path "Env:$envVar" -ErrorAction SilentlyContinue).Value
        if ([string]::IsNullOrWhiteSpace($value)) {
            $missingEnvVars += $envVar
        }
    }

    if ($missingEnvVars.Count -gt 0) {
        throw "Missing required environment variables: $($missingEnvVars -join ', ')"
    }
}

function Get-StateMap {
    param(
        [Parameter(Mandatory = $true)]
        [string] $StateFilePath,
        [Parameter(Mandatory = $true)]
        [string] $RemoteBasePath
    )

    if (-not (Test-Path -LiteralPath $StateFilePath)) {
        return @{}
    }

    try {
        $state = Get-Content -LiteralPath $StateFilePath -Raw | ConvertFrom-Json -ErrorAction Stop
    } catch {
        Write-Warning "Ignoring invalid deploy state file: $StateFilePath"
        return @{}
    }

    if ($null -eq $state -or $state.remoteBasePath -ne $RemoteBasePath -or $null -eq $state.files) {
        return @{}
    }

    $map = @{}
    foreach ($entry in $state.files) {
        if ($null -eq $entry.relativePath -or $null -eq $entry.hash) {
            continue
        }

        $map[[string] $entry.relativePath] = [string] $entry.hash
    }

    return $map
}

function Save-StateMap {
    param(
        [Parameter(Mandatory = $true)]
        [string] $StateFilePath,
        [Parameter(Mandatory = $true)]
        [string] $RemoteBasePath,
        [Parameter(Mandatory = $true)]
        [hashtable] $StateMap
    )

    $stateDir = Split-Path -Parent $StateFilePath
    if (-not (Test-Path -LiteralPath $stateDir)) {
        New-Item -ItemType Directory -Path $stateDir -Force | Out-Null
    }

    $files = foreach ($relativePath in ($StateMap.Keys | Sort-Object)) {
        [pscustomobject]@{
            relativePath = $relativePath
            hash = $StateMap[$relativePath]
        }
    }

    $state = [pscustomobject]@{
        remoteBasePath = $RemoteBasePath
        updatedAt = (Get-Date).ToString("o")
        files = $files
    }

    $state | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $StateFilePath -Encoding UTF8
}

$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot ".."))
$envFilePath = Join-Path $repoRoot ".env"
$distPath = Join-Path $repoRoot "dist"
$manifestPath = Join-Path $distPath "assets\.vite\manifest.json"
$buildScript = Join-Path $PSScriptRoot "build.ps1"
$stateFilePath = Join-Path $repoRoot ".deploy-state\ftp-theme-state.json"

Import-DotEnvFile -Path $envFilePath
Test-RequiredEnvVars -Names @(
    "FTP_HOST",
    "FTP_USER",
    "FTP_PASSWORD",
    "FTP_REMOTE_PATH"
)

if ($null -eq (Get-Command -Name "curl.exe" -ErrorAction SilentlyContinue)) {
    throw "curl.exe is required for deploys but was not found in PATH."
}

if (-not $SkipBuild) {
    Push-Location $repoRoot
    try {
        & $buildScript
        if ($LASTEXITCODE -ne 0) {
            throw "Theme build failed."
        }
    } finally {
        Pop-Location
    }
}

if (-not (Test-Path -LiteralPath $distPath)) {
    throw "Theme package directory not found: $distPath. Run 'pnpm run build' first."
}

if (-not (Test-Path -LiteralPath $manifestPath)) {
    throw "Vite manifest not found: $manifestPath. Run 'pnpm run build' first."
}

try {
    $manifest = Get-Content -LiteralPath $manifestPath -Raw | ConvertFrom-Json -ErrorAction Stop
} catch {
    throw "Invalid Vite manifest JSON: $manifestPath"
}

if (-not $manifest.PSObject.Properties.Name.Contains("assets/js/app.js")) {
    throw "Vite manifest is missing required app entry: assets/js/app.js"
}

$ftpScheme = if ([string]::IsNullOrWhiteSpace($env:FTP_SCHEME)) { "ftp" } else { $env:FTP_SCHEME.Trim().ToLowerInvariant() }
$ftpHost = $env:FTP_HOST.Trim()
$ftpUser = $env:FTP_USER
$ftpPassword = $env:FTP_PASSWORD
$remoteBasePath = $env:FTP_REMOTE_PATH.Trim().Trim("/")

$files = Get-ChildItem -LiteralPath $distPath -Recurse -File | Sort-Object FullName
if ($files.Count -eq 0) {
    throw "Theme package directory is empty: $distPath"
}

$previousStateMap = Get-StateMap -StateFilePath $stateFilePath -RemoteBasePath $remoteBasePath
$currentStateMap = @{}
$filesToUpload = New-Object System.Collections.Generic.List[object]

foreach ($file in $files) {
    $relativePath = $file.FullName.Substring($distPath.Length).TrimStart('\').Replace('\', '/')
    $hash = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
    $currentStateMap[$relativePath] = $hash

    if (-not $previousStateMap.ContainsKey($relativePath) -or $previousStateMap[$relativePath] -ne $hash) {
        $filesToUpload.Add([pscustomobject]@{
            File = $file
            RelativePath = $relativePath
            Hash = $hash
        })
    }
}

if ($filesToUpload.Count -eq 0) {
    Write-Host "No changed files detected in dist. Nothing to upload."
    exit 0
}

$curlArgsBase = @(
    "--ftp-create-dirs",
    "--user", "$ftpUser`:$ftpPassword",
    "--silent",
    "--show-error",
    "--fail"
)

$totalFiles = $filesToUpload.Count
$maxParallelUploads = 3
$useThreadJobs = $null -ne (Get-Command -Name "Start-ThreadJob" -ErrorAction SilentlyContinue)
$activeJobs = New-Object System.Collections.ArrayList
$completedUploads = 0
$failedUploads = New-Object System.Collections.Generic.List[string]
$nextIndex = 0
$stopQueueing = $false

while ($nextIndex -lt $totalFiles -or $activeJobs.Count -gt 0) {
    while (-not $stopQueueing -and $nextIndex -lt $totalFiles -and $activeJobs.Count -lt $maxParallelUploads) {
        $fileToUpload = $filesToUpload[$nextIndex]
        $remoteUrl = "${ftpScheme}://${ftpHost}/${remoteBasePath}/$($fileToUpload.RelativePath)"

        Write-Progress `
            -Activity "Uploading changed theme files via FTP" `
            -Status "Starting upload $($nextIndex + 1) of ${totalFiles}: $($fileToUpload.RelativePath)" `
            -PercentComplete ([int](($completedUploads / $totalFiles) * 100))

        $job = Start-UploadJob `
            -RelativePath $fileToUpload.RelativePath `
            -FilePath $fileToUpload.File.FullName `
            -RemoteUrl $remoteUrl `
            -CurlArgsBase $curlArgsBase `
            -UseThreadJobs $useThreadJobs

        [void] $activeJobs.Add($job)
        $nextIndex++
    }

    if ($activeJobs.Count -eq 0) {
        break
    }

    $finishedJob = Wait-Job -Job $activeJobs.ToArray() -Any
    $jobResult = Receive-UploadJobResult -Job $finishedJob
    [void] $activeJobs.Remove($finishedJob)
    $completedUploads++

    if (-not $jobResult.Success) {
        $failedUploads.Add([string] $jobResult.RelativePath)
        $stopQueueing = $true
    }

    $status = if ($failedUploads.Count -gt 0) {
        "Completed $completedUploads of ${totalFiles} with $($failedUploads.Count) failure(s)"
    } else {
        "Completed $completedUploads of ${totalFiles}: $($jobResult.RelativePath)"
    }

    Write-Progress `
        -Activity "Uploading changed theme files via FTP" `
        -Status $status `
        -PercentComplete ([int](($completedUploads / $totalFiles) * 100))
}

Write-Progress -Activity "Uploading changed theme files via FTP" -Completed

if ($failedUploads.Count -gt 0) {
    throw "FTP upload failed for $($failedUploads -join ', ')"
}

Save-StateMap -StateFilePath $stateFilePath -RemoteBasePath $remoteBasePath -StateMap $currentStateMap

Write-Host "Uploaded $totalFiles changed file(s) from dist to $ftpHost/$remoteBasePath."
