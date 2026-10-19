const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const postcss = require('postcss');
const root = path.join(__dirname, '..');
function files(dir) {
    return fs.readdirSync(dir, {withFileTypes: true}).flatMap(entry => {
        const name = path.join(dir, entry.name);
        if (['node_modules', '.git', 'dist'].includes(entry.name)) return [];
        return entry.isDirectory() ? files(name) : [name];
    });
}
const all = files(root);
const production = all.filter(name => /\.(php|js|css)$/.test(name) && !name.includes(`${path.sep}tests${path.sep}`));
const source = production.map(name => fs.readFileSync(name, 'utf8')).join('\n');
const logic = production.filter(name => !name.endsWith('.css')).map(name => fs.readFileSync(name, 'utf8')).join('\n');
for (const file of production.filter(name => name.endsWith('.css'))) {
    const ast = postcss.parse(fs.readFileSync(file, 'utf8'), {from: file});
    const seen = new Set();
    ast.walkRules(rule => {
        for (const [, name] of rule.selector.matchAll(/\.(wpcunter-[a-z0-9-]+)/g)) {
            assert.ok(logic.includes(name), `Unreferenced CSS class: ${name}`);
        }
        const ancestors = [];
        for (let parent = rule.parent; parent.type !== 'root'; parent = parent.parent) ancestors.unshift(parent.params || parent.type);
        const key = `${ancestors.join('/')}|${rule.selector}`;
        assert.ok(!seen.has(key), `Duplicate selector: ${key}`);
        seen.add(key);
        const properties = new Set();
        rule.walkDecls(decl => {
            assert.ok(!properties.has(decl.prop), `Duplicate property: ${decl.prop}`);
            properties.add(decl.prop);
        });
    });
}
const names = [...source.matchAll(/function\s+(wpcunter_\w+)\s*\(/g)].map(match => match[1]);
assert.equal(new Set(names).size, names.length, 'No duplicate PHP function definitions');
for (const name of names) {
    assert.ok(source.match(new RegExp(`\\b${name}\\b`, 'g')).length > 1, `Possibly unused function: ${name}`);
}
assert.ok(!source.includes('woodmart_shortcode_animated_counter'), 'No theme renderer dependency');
assert.ok(!source.includes('woodmart-counter'), 'No competing theme animation selector');
assert.ok(!source.includes('--wpcunter-label-custom-color'), 'No obsolete per-label color override');
assert.ok(!source.includes('.counter-small'), 'No obsolete size preset CSS');
const header = fs.readFileSync(path.join(root, 'wp-cunter.php'), 'utf8');
const version = header.match(/Version:\s*(\S+)/)[1];
assert.equal(version, require('../package.json').version);
assert.ok(header.includes(`define('WPCUNTER_VERSION', '${version}')`), 'One asset version constant');
const hashes = new Map();
const crypto = require('node:crypto');
for (const file of all.filter(name => /\.(jpg|png|svg)$/.test(name))) {
    const hash = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
    assert.ok(!hashes.has(hash), `Duplicate asset: ${file}`);
    hashes.set(hash, file);
    const relative = path.relative(root, file).split(path.sep).join('/');
    assert.ok(logic.includes(relative), `Unreferenced image asset: ${relative}`);
}
console.log(`PASS: ${production.length} source files, CSS selectors/properties, PHP references, asset duplicates and version consistency`);
