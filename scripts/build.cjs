const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {execFileSync} = require('node:child_process');
const root = path.join(__dirname, '..');
const version = require('../package.json').version;
const production = ['wp-cunter.php', 'includes', 'assets', 'README.md', 'readme.txt', 'LICENSE', 'THIRD-PARTY-NOTICES.md', 'CHANGELOG.md'];
// Build exactly the reviewed commit, excluding tests, development tools and node_modules.
execFileSync('git', ['diff', '--exit-code', 'HEAD', '--', ...production], {cwd: root, stdio: 'pipe'});
const archive = execFileSync('git', ['archive', '--format=zip', '--prefix=wp-cunter/', 'HEAD', ...production], {cwd: root, maxBuffer: 20 * 1024 * 1024});
const dist = path.join(root, 'dist');
fs.mkdirSync(dist, {recursive: true});
const filename = `wp-animated-counter-${version}.zip`;
fs.writeFileSync(path.join(dist, filename), archive);
const hash = crypto.createHash('sha256').update(archive).digest('hex');
fs.writeFileSync(path.join(dist, 'SHA256SUMS.txt'), `${hash}  ${filename}\n`);
console.log(`Built ${filename} (${archive.length} bytes)\nSHA256 ${hash}`);
