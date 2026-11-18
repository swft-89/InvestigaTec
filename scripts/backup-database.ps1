$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$backupDirectory = Join-Path $projectRoot 'backups\database'
$connectionFile = Join-Path $backupDirectory '.database-url.dpapi'
$retentionCount = 4

function Get-ConnectionSettings([string]$connectionUrl) {
    try {
        $uri = [Uri]::new($connectionUrl)
    }
    catch {
        throw 'La URL de PostgreSQL no tiene un formato valido.'
    }

    if ($uri.Scheme -notin @('postgres', 'postgresql') -or
        [string]::IsNullOrWhiteSpace($uri.Host) -or
        [string]::IsNullOrWhiteSpace($uri.AbsolutePath)) {
        throw 'La URL debe ser una URL PostgreSQL completa.'
    }

    $userInfo = $uri.UserInfo
    $separator = $userInfo.IndexOf(':')
    if ($separator -lt 1 -or $separator -eq ($userInfo.Length - 1)) {
        throw 'La URL de PostgreSQL debe incluir usuario y contrasena.'
    }

    $username = [Uri]::UnescapeDataString($userInfo.Substring(0, $separator))
    $password = [Uri]::UnescapeDataString($userInfo.Substring($separator + 1))
    $databaseName = [Uri]::UnescapeDataString($uri.AbsolutePath.TrimStart('/'))
    $sslMode = 'require'

    foreach ($parameter in $uri.Query.TrimStart('?').Split('&')) {
        if ([string]::IsNullOrWhiteSpace($parameter)) {
            continue
        }
        $parts = $parameter.Split('=', 2)
        if ([Uri]::UnescapeDataString($parts[0]) -eq 'sslmode' -and $parts.Length -eq 2) {
            $sslMode = [Uri]::UnescapeDataString($parts[1])
        }
    }

    if ([string]::IsNullOrWhiteSpace($username) -or
        [string]::IsNullOrWhiteSpace($password) -or
        [string]::IsNullOrWhiteSpace($databaseName)) {
        throw 'La URL de PostgreSQL esta incompleta.'
    }

    if ($sslMode -notin @('disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full')) {
        throw 'El sslmode de la URL de PostgreSQL no es valido.'
    }

    $port = if ($uri.IsDefaultPort -or $uri.Port -lt 1) { 5432 } else { $uri.Port }

    return [PSCustomObject]@{
        Host = $uri.DnsSafeHost
        Port = $port
        Database = $databaseName
        Username = $username
        Password = $password
        SslMode = $sslMode
    }
}

if (-not (Test-Path -LiteralPath $connectionFile -PathType Leaf)) {
    throw "Falta la configuracion local cifrada. Ejecuta scripts\setup-database-backup.ps1."
}

$pgDump = Get-Command pg_dump -ErrorAction SilentlyContinue
$pgRestore = Get-Command pg_restore -ErrorAction SilentlyContinue
if ($null -eq $pgDump -or $null -eq $pgRestore) {
    throw 'No se encontraron pg_dump y pg_restore. Instala las herramientas cliente de PostgreSQL y agregalas a PATH.'
}

$encryptedUrl = (Get-Content -LiteralPath $connectionFile -Raw).Trim()
$secureUrl = ConvertTo-SecureString -String $encryptedUrl
$urlPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureUrl)
try {
    $connectionUrl = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($urlPointer)
}
finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($urlPointer)
}

$connection = Get-ConnectionSettings $connectionUrl
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmssfff'
$fileName = "investigatec-$timestamp.dump"
$finalPath = Join-Path $backupDirectory $fileName
$partialPath = Join-Path $backupDirectory ".$fileName.partial"

New-Item -ItemType Directory -Path $backupDirectory -Force | Out-Null
if (Test-Path -LiteralPath $finalPath) {
    throw "Ya existe un respaldo con el nombre $fileName."
}

$previousPassword = $env:PGPASSWORD
$previousSslMode = $env:PGSSLMODE
try {
    $env:PGPASSWORD = $connection.Password
    $env:PGSSLMODE = $connection.SslMode

    & $pgDump.Source `
        --format=custom `
        --no-owner `
        --no-acl `
        --no-password `
        --host=$connection.Host `
        --port=$connection.Port `
        --username=$connection.Username `
        --dbname=$connection.Database `
        --file=$partialPath
    if ($LASTEXITCODE -ne 0) {
        throw "pg_dump fallo con codigo $LASTEXITCODE."
    }

    & $pgRestore.Source --list $partialPath | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "pg_restore no pudo validar el respaldo (codigo $LASTEXITCODE)."
    }

    Move-Item -LiteralPath $partialPath -Destination $finalPath
}
finally {
    if (Test-Path -LiteralPath $partialPath) {
        Remove-Item -LiteralPath $partialPath -Force
    }
    if ($null -eq $previousPassword) {
        Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
    }
    else {
        $env:PGPASSWORD = $previousPassword
    }
    if ($null -eq $previousSslMode) {
        Remove-Item Env:\PGSSLMODE -ErrorAction SilentlyContinue
    }
    else {
        $env:PGSSLMODE = $previousSslMode
    }
}

$oldBackups = Get-ChildItem -LiteralPath $backupDirectory -Filter 'investigatec-*.dump' -File |
    Sort-Object Name -Descending |
    Select-Object -Skip $retentionCount
foreach ($oldBackup in $oldBackups) {
    Remove-Item -LiteralPath $oldBackup.FullName -Force
}

Write-Output "Respaldo verificado: $finalPath"
