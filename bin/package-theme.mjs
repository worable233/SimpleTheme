#!/usr/bin/env node
/**
 * Package Simple Theme as ZIP (cross-platform)
 * Usage: node bin/package-theme.mjs
 * Output: Simple-Theme-v<version>.zip (in theme root)
 *
 * Uses archiver (npm) to create a ZIP with forward-slash paths,
 * avoiding the backslash path issue that breaks on Linux ZipArchive.
 */
import { createWriteStream, existsSync, readFileSync, readdirSync, statSync, unlinkSync } from 'node:fs';
import { join, resolve, dirname, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { ZipArchive } from 'archiver';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = resolve(__dirname, '..');

// Read theme name + version from style.css
const styleCss = readFileSync(join(root, 'style.css'), 'utf-8');

const versionMatch = styleCss.match(/Version:\s*([\d.]+)/);
const version = versionMatch ? versionMatch[1] : '1.0.0';

const themeNameMatch = styleCss.match(/Theme Name:\s*(.+)/);
const themeName = themeNameMatch ? themeNameMatch[1].trim().replace(/\s+/g, '-') : 'simple-theme';

const zipName = `${themeName}-v${version}.zip`;
const zipPath = join(root, zipName);

console.log(`--- Simple Theme Packager ---`);
console.log(`Theme: ${themeName}  v${version}`);
console.log(`Output: ${zipName}`);
console.log('');

// Remove old zip
if (existsSync(zipPath)) {
  unlinkSync(zipPath);
  console.log(`  Removed old: ${zipName}`);
}

// Files/directories to exclude (basename match)
const excludedNames = new Set([
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
  '.vscode', '.idea', '.deepseek', '.learnings', '.atomcode', 'includes', '.atomcode.md',
  '_check.php', '_fix_admin.py', '_fix_loggedin.py', '_count_large.py',
  '_check-zip.mjs', '_check-zip-contents.ps1',
  'bin/check-zip-contents.ps1',
  'check_captcha.php', 'debug-lines.cjs', 'debug-resolve.php',
  'dist.zip', 'original.css', 'response.txt',
  'test_queue.cjs', 'test_queue_behavior.php',
  'dist/emojis',  'original_LinksView.vue', 'original_prose.css',
  'Simple-Theme-v1.0.0.zip', 'Simple-Theme-v2.0.0.zip',
  'deploy-temp.zip',
  'vendor', 'uixpress',
  'build-errors.txt', 'errors.txt', 'tmp_homeview_diff.txt',
  '_zipcheck',
]);

const alwaysExcludeDirs = new Set([
  'node_modules', '.git', '.claude', '.vscode', '.idea',
  '.deepseek', '.learnings', '.atomcode', 'includes',
  'vendor', 'uixpress',
]);

let fileCount = 0;

function shouldExclude(name, relPath) {
  if (excludedNames.has(name)) return true;
  if (excludedNames.has(relPath)) return true;
  return false;
}

const output = createWriteStream(zipPath);
const archive = new ZipArchive({ zlib: { level: 9 } });

output.on('close', () => {
  const bytes = archive.pointer();
  const sizeKB = (bytes / 1024).toFixed(1);
  const sizeMB = (bytes / 1024 / 1024).toFixed(2);
  console.log('');
  console.log(`DONE: ${zipName} (${sizeKB > 1024 ? sizeMB + ' MB' : sizeKB + ' KB'})`);
  console.log(`Files packaged: ${fileCount}`);
});

archive.on('warning', (err) => {
  if (err.code !== 'ENOENT') console.warn('Warning:', err);
});

archive.on('error', (err) => {
  console.error('Error:', err.message);
  process.exit(1);
});

archive.pipe(output);

function walkDir(dir) {
  let entries;
  try {
    entries = readdirSync(dir);
  } catch {
    return;
  }

  for (const entry of entries) {
    const fullPath = join(dir, entry);
    const relPath = relative(root, fullPath).replace(/\\/g, '/'); // force forward slashes
    const stat = statSync(fullPath);

    if (stat.isDirectory()) {
      if (alwaysExcludeDirs.has(entry)) continue;
      if (shouldExclude(entry, relPath)) continue;

      // Skip non-ASCII directory names (Chinese-named ref dirs etc.)
      let hasNonAscii = false;
      for (const c of entry) {
        if (c.codePointAt(0) > 127) {
          hasNonAscii = true;
          break;
        }
      }
      if (hasNonAscii) continue;

      walkDir(fullPath);
    } else if (stat.isFile()) {
      // File extension filter
      const ext = entry.substring(entry.lastIndexOf('.')).toLowerCase();
      if (ext === '.ps1' || ext === '.mjs' || ext === '.py' || ext === '.md' || ext === '.log' || ext === '.zip' || ext === '.txt') continue;
      if (entry.startsWith('composer.')) continue;

      if (shouldExclude(entry, relPath)) continue;

      archive.file(fullPath, { name: relPath });
      fileCount++;
    }
  }
}

walkDir(root);

if (fileCount === 0) {
  console.error('ERROR: no files to package');
  process.exit(1);
}

console.log(`Compressing ${fileCount} files...`);
archive.finalize();
