param([ValidateSet('Status','Pull','Push')][string]$Action = 'Status')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Push-Location $projectRoot
try {
    if ($Action -eq 'Status') {
        git status --short --branch
    } elseif ($Action -eq 'Pull') {
        $pendingChanges = git status --porcelain
        if ($pendingChanges) { throw 'Commit or save your local changes before pulling GitHub updates.' }
        git pull --ff-only origin main
    } else {
        git push -u origin main
    }
    if ($LASTEXITCODE -ne 0) { throw 'Git synchronization failed; review the message above.' }
} finally {
    Pop-Location
}
