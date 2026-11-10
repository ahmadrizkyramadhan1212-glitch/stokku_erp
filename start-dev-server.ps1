# Development server launcher
Write-Host "Starting Laravel development server..."
$phpPath = (Get-Command php).Source
$serverScript = Join-Path $PSScriptRoot "server.php"

# Start PHP server on port 8000
& $phpPath -S 127.0.0.1:8000 $serverScript
