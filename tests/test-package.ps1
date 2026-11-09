param(
    [string]$Package = (Join-Path (Split-Path $PSScriptRoot -Parent) 'dist\woo-shipping-label-wiliweb-1.1.1-beta.1.zip')
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem

$archive = [System.IO.Compression.ZipFile]::OpenRead((Resolve-Path -LiteralPath $Package))
try {
    $entries = @($archive.Entries | ForEach-Object { $_.FullName })
    $invalidSeparators = @($entries | Where-Object { $_.Contains('\') })
    if ($invalidSeparators.Count -gt 0) {
        throw "ZIP entries must use forward slashes. Invalid entry: $($invalidSeparators[0])"
    }

    $roots = @($entries | Where-Object { $_ } | ForEach-Object { ($_ -split '/')[0] } | Sort-Object -Unique)
    if ($roots.Count -ne 1 -or $roots[0] -ne 'woo-shipping-label-wiliweb') {
        throw "ZIP must contain one woo-shipping-label-wiliweb root. Found: $($roots -join ', ')"
    }

    $mainFile = 'woo-shipping-label-wiliweb/woo-shipping-label-wiliweb.php'
    if ($entries -notcontains $mainFile) {
        throw "Plugin main file is missing at $mainFile"
    }

    Write-Output 'PASS WordPress-compatible package paths'
}
finally {
    $archive.Dispose()
}
