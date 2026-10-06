param([ValidateRange(1024, 65535)][int]$Port = 8088)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$envPath = Join-Path $projectRoot '.env'
if (Test-Path -LiteralPath $envPath) {
    throw '.env already exists. Keep the existing credentials and configuration.'
}

function New-LocalSecret {
    $bytes = New-Object byte[] 24
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $generator.GetBytes($bytes)
    $generator.Dispose()
    return [System.BitConverter]::ToString($bytes).Replace('-', '').ToLowerInvariant()
}

$text = [System.IO.File]::ReadAllText((Join-Path $projectRoot '.env.example'))
$values = [ordered]@{
    APP_URL = "http://localhost:$Port"
    APP_PORT = "$Port"
    APP_TIMEZONE = 'Asia/Kuala_Lumpur'
    APP_CURRENCY = 'MYR'
    APP_ALLOWED_LOCALES = 'en'
    APP_ALLOWED_CURRENCIES = 'MYR'
    DB_HOST = 'database'
    DB_DATABASE = 'bagisto_local'
    DB_USERNAME = 'bagisto_local'
    DB_PASSWORD = (New-LocalSecret)
    MARIADB_ROOT_PASSWORD = (New-LocalSecret)
    SESSION_ENCRYPT = 'true'
    REDIS_CLIENT = 'predis'
    MAIL_MAILER = 'log'
    MAIL_FROM_ADDRESS = 'shop@example.test'
    CONTACT_MAIL_ADDRESS = 'support@example.test'
    ADMIN_MAIL_ADDRESS = 'admin@bagisto.test'
}
foreach ($key in $values.Keys) {
    $pattern = '(?m)^' + [regex]::Escape($key) + '=.*$'
    $line = $key + '=' + $values[$key]
    if ([regex]::IsMatch($text, $pattern)) {
        $text = [regex]::Replace($text, $pattern, $line)
    } else {
        $text += "`n$line`n"
    }
}

$encoding = [System.Text.UTF8Encoding]::new($false)
[System.IO.File]::WriteAllText($envPath, $text, $encoding)
$localFolder = Join-Path $projectRoot '.local'
New-Item -ItemType Directory -Path $localFolder -Force | Out-Null
$credentials = [ordered]@{
    name = 'Local Administrator'
    email = 'admin@bagisto.test'
    password = (New-LocalSecret)
}
[System.IO.File]::WriteAllText((Join-Path $localFolder 'admin-credentials.json'), ($credentials | ConvertTo-Json), $encoding)
Write-Output 'Created .env and .local/admin-credentials.json with random local credentials.'
