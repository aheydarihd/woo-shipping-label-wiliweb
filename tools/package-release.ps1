param([string]$Version = '1.0.0')
$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$sourceRoot = (Resolve-Path (Join-Path $projectRoot 'source\woo-shipping-label-wiliweb')).Path
$distRoot = Join-Path $projectRoot 'dist'
$stageRoot = Join-Path $projectRoot 'tmp\package-stage'
$pluginStage = Join-Path $stageRoot 'woo-shipping-label-wiliweb'
$zipPath = Join-Path $distRoot ("woo-shipping-label-wiliweb-{0}.zip" -f $Version)

if (-not $sourceRoot.StartsWith($projectRoot, [System.StringComparison]::OrdinalIgnoreCase)) { throw 'Invalid source path' }
if (Test-Path -LiteralPath $stageRoot) { Remove-Item -LiteralPath $stageRoot -Recurse -Force }
New-Item -ItemType Directory -Force -Path $pluginStage, $distRoot | Out-Null
Get-ChildItem -LiteralPath $sourceRoot -Force | Copy-Item -Destination $pluginStage -Recurse -Force
if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath -Force }
Compress-Archive -Path $pluginStage -DestinationPath $zipPath -CompressionLevel Optimal
$hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $zipPath).Hash
"$hash  $(Split-Path $zipPath -Leaf)" | Set-Content -Encoding ascii -LiteralPath ($zipPath + '.sha256')
Write-Output "ZIP=$zipPath"
Write-Output "SHA256=$hash"
