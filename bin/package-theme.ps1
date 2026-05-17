# Package Simple Theme as ZIP (cross-platform)
# Uses .NET ZipArchive with forward-slash paths for Linux compatibility.
# Output: [Theme-Name]-v[Version].zip

$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.IO.Compression
$src = Split-Path -Parent (Split-Path -Parent $PSCommandPath)

# Read theme name + version from style.css
$styleCss = Join-Path $src "style.css"
$cssContent = Get-Content $styleCss -Raw

$themeName = if ($cssContent -match 'Theme Name:\s*(.+)') { $matches[1].Trim() } else { 'simple-theme' }
$version = if ($cssContent -match 'Version:\s*([\d.]+)') { $matches[1].Trim() } else { '1.0.0' }

# "Simple Theme" -> "Simple-Theme"
$slug = $themeName -replace '\s+', '-'
$zipName = "$slug-v$version.zip"
$zipPath = Join-Path $src $zipName

Write-Host "--- Simple Theme Packager ---"
Write-Host "Theme: $themeName  v$version"
Write-Host "Output: $zipName"
Write-Host ""

# Remove old zip
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

# Excluded file/directory names (exact match or wildcard)
$excludedNames = @(
    'node_modules', 'src', '.git', '.gitattributes', '.gitignore', '.claude',
    '.env', '.env.docker', '.env.production',
    '.editorconfig', '.eslintcache', '.oxlintrc.json', '.prettierrc.json',
    'tsconfig.json', 'tsconfig.app.json', 'tsconfig.node.json',
    'vite.config.ts', 'eslint.config.ts', 'env.d.ts',
    'package.json', 'package-lock.json', 'composer.json', 'composer.lock',
    '_build.bat', '_check.bat', '_run-dev.bat', '_pack.bat',
    'bin', 'public', 'skills',
    'docker-compose.yml', 'Dockerfile', '.dockerignore',
    'index.html',
    'vite-server.log', 'dev-server.log', 'vite-srv.log',
    'copy-svgs.ps1',
    'CLAUDE.md', 'AGENTS.md', 'README.md', 'LICENSE',
    'UI-DESIGN-ANALYSIS.md', 'MATERIAL_DESIGN_GUIDE.md',
    '参考主题', '参考iEmo', '参考Sakurairo', 'iEmo-master.zip',
    'vendor',
    '.vscode', '.idea', '.deepseek', '.learnings', '.atomcode', '.atomcode.md',
    '_check.php', '_check-zip.mjs', '_check-zip-contents.ps1',
    'bin/check-zip-contents.ps1',
    'Simple-Theme-v1.0.0.zip', 'Simple-Theme-v2.0.0.zip'
)

$alwaysExcludeDirs = @(
    'node_modules', '.git', '.claude', '.vscode', '.idea',
    '.deepseek', '.learnings', '.atomcode'
)

$excludeExtensions = @('.ps1', '.mjs', '.md', '.log')

function Should-Exclude([string]$name, [string]$relPath) {
    if ($excludedNames -contains $name) { return $true }
    if ($excludedNames -contains $relPath) { return $true }
    foreach ($ext in $excludeExtensions) {
        if ($name -like "*$ext") { return $true }
    }
    if ($name -like 'composer.*') { return $true }
    # Non-ASCII directory names (Chinese ref dirs)
    foreach ($c in $name.ToCharArray()) {
        if ($c -gt 127) { return $true }
    }
    return $false
}

$fileCount = 0

# Create ZIP using .NET ZipArchive with forward-slash paths
$zipStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::CreateNew)
$zipArchive = [System.IO.Compression.ZipArchive]::new($zipStream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    # Recursive directory walker
    function Add-DirectoryToZip([string]$dir) {
        $entries = Get-ChildItem -Path $dir
        foreach ($entry in $entries) {
            $fullPath = $entry.FullName
            $relPath = $fullPath.Substring($script:src.Length + 1) -replace '\\', '/'  # force forward slashes

            if ($entry.PSIsContainer) {
                if ($script:alwaysExcludeDirs -contains $entry.Name) { continue }
                if (Should-Exclude $entry.Name $relPath) { continue }
                Add-DirectoryToZip $fullPath
            } else {
                if (Should-Exclude $entry.Name $relPath) { continue }
                # Add file to zip
                $zipEntry = $script:zipArchive.CreateEntry($relPath, [System.IO.Compression.CompressionLevel]::Optimal)
                $entryStream = $zipEntry.Open()
                $fileBytes = [System.IO.File]::ReadAllBytes($fullPath)
                $entryStream.Write($fileBytes, 0, $fileBytes.Length)
                $entryStream.Close()
                $script:fileCount++
            }
        }
    }

    Add-DirectoryToZip $src

    if ($fileCount -eq 0) {
        Write-Host "ERROR: no files to package"
        exit 1
    }

    Write-Host "Compressing $fileCount files..."
} finally {
    $zipArchive.Dispose()
    $zipStream.Dispose()
}

if (Test-Path $zipPath) {
    $sizeKB = [math]::Round((Get-Item $zipPath).Length / 1KB, 1)
    $sizeMB = [math]::Round((Get-Item $zipPath).Length / 1MB, 2)
    if ($sizeKB -gt 1024) {
        Write-Host "DONE: $zipName (${sizeMB} MB)"
    } else {
        Write-Host "DONE: $zipName (${sizeKB} KB)"
    }
    Write-Host ""
    Write-Host "Contents (top-level):"
    Get-ChildItem -Path $src -Exclude $excludedNames | Where-Object {
        $name = $_.Name
        $skip = $false
        foreach ($ext in $excludeExtensions) { if ($name -like "*$ext") { $skip = $true; break } }
        if ($name -like 'composer.*') { $skip = $true }
        foreach ($c in $name.ToCharArray()) { if ($c -gt 127) { $skip = $true; break } }
        -not $skip
    } | ForEach-Object { Write-Host "  $($_.Name)" }
} else {
    Write-Host "ERROR: zip not created"
    exit 1
}
