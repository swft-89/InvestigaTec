$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$backupDirectory = Join-Path $projectRoot 'backups\database'
$connectionFile = Join-Path $backupDirectory '.database-url.dpapi'
$backupScript = Join-Path $PSScriptRoot 'backup-database.ps1'
$taskName = 'InvestigaTec - Respaldo PostgreSQL semanal'

$pgDump = Get-Command pg_dump -ErrorAction SilentlyContinue
$pgRestore = Get-Command pg_restore -ErrorAction SilentlyContinue
if ($null -eq $pgDump -or $null -eq $pgRestore) {
    throw 'Instala las herramientas cliente de PostgreSQL y agrega pg_dump y pg_restore a PATH antes de continuar.'
}

$secureUrl = Read-Host 'DATABASE_URL externa de PostgreSQL (entrada oculta)' -AsSecureString
if ($secureUrl.Length -eq 0) {
    throw 'La URL de PostgreSQL no puede estar vacia.'
}

New-Item -ItemType Directory -Path $backupDirectory -Force | Out-Null
ConvertFrom-SecureString -SecureString $secureUrl |
    Set-Content -LiteralPath $connectionFile -Encoding ASCII

Write-Output 'Probando la conexion y creando el primer respaldo...'
& $backupScript

$powerShellPath = Join-Path $PSHOME 'powershell.exe'
$arguments = "-NoProfile -File `"$backupScript`""
$action = New-ScheduledTaskAction `
    -Execute $powerShellPath `
    -Argument $arguments `
    -WorkingDirectory $projectRoot
$trigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Sunday -At '3:00AM'
$principal = New-ScheduledTaskPrincipal `
    -UserId ([Security.Principal.WindowsIdentity]::GetCurrent().Name) `
    -LogonType Interactive `
    -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 1) `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $taskName `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings `
    -Description 'Crea y valida un respaldo PostgreSQL semanal y conserva los cuatro mas recientes.' `
    -Force | Out-Null

Write-Output "Tarea semanal registrada: $taskName (domingos a las 3:00 AM, hora local)."
Write-Output "Los respaldos estan en: $backupDirectory"
