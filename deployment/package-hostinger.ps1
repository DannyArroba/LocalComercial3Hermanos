$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$outputRoot = Join-Path $projectRoot 'entrega-hostinger'
$stageRoot = Join-Path $outputRoot ('site-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force $stageRoot | Out-Null
Copy-Item (Join-Path $projectRoot 'react-frontend/dist/*') $stageRoot -Recurse
New-Item -ItemType Directory -Force "$stageRoot/backend", "$stageRoot/frontend", "$stageRoot/_database" | Out-Null
Copy-Item (Join-Path $projectRoot 'backend/api') "$stageRoot/backend" -Recurse
Copy-Item (Join-Path $projectRoot 'backend/api-bootstrap.php') "$stageRoot/backend"
Copy-Item (Join-Path $PSScriptRoot 'db-production.php') "$stageRoot/backend/db.php"
Copy-Item (Join-Path $projectRoot 'backend/config.example.php') "$stageRoot/backend/config.php"
Copy-Item (Join-Path $projectRoot 'frontend/uploads') "$stageRoot/frontend" -Recurse
Copy-Item (Join-Path $PSScriptRoot 'htaccess.txt') "$stageRoot/.htaccess"
$utf8 = New-Object System.Text.UTF8Encoding($false)
[IO.File]::WriteAllText("$stageRoot/backend/.htaccess", "Options -Indexes`nRewriteEngine On`nRewriteRule ^(?!api/).+ - [F,L]`n", $utf8)
[IO.File]::WriteAllText("$stageRoot/frontend/uploads/.htaccess", "Options -Indexes`n<FilesMatch `"(?i)\.(php[0-9]?|phtml|phar|cgi|pl|html?)$`">`nRequire all denied`n</FilesMatch>`n", $utf8)
[IO.File]::WriteAllText("$stageRoot/_database/.htaccess", "Require all denied`n", $utf8)
$sqlPath = Join-Path $outputRoot 'lc3brothers-database.sql'
& 'C:/xampp/mysql/bin/mysqldump.exe' -h 127.0.0.1 -u root --single-transaction --skip-lock-tables --no-tablespaces --default-character-set=utf8mb4 "--result-file=$sqlPath" tienda
if ($LASTEXITCODE -ne 0) { throw 'No se pudo exportar la base.' }
Copy-Item $sqlPath "$stageRoot/_database/lc3brothers-database.sql"
Copy-Item (Join-Path $PSScriptRoot 'LEEME-HOSTINGER.txt') $outputRoot
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipPath = Join-Path $outputRoot 'lc3brothers-hostinger.zip'
if (Test-Path $zipPath) { throw 'El ZIP ya existe. Muevelo antes de volver a empaquetar.' }
$archive = [IO.Compression.ZipFile]::Open($zipPath, [IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem $stageRoot -Recurse -File -Force | ForEach-Object {
        $entryName = $_.FullName.Substring($stageRoot.Length + 1).Replace('\', '/')
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entryName, [IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally { $archive.Dispose() }
Get-Item $zipPath, $sqlPath | Select-Object FullName, Length
