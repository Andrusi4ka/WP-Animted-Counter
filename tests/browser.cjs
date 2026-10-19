const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const {execFileSync} = require('node:child_process');
const assert = require('node:assert/strict');
const {chromium} = require('playwright-core');
const {PNG} = require('pngjs');
const root = path.join(__dirname, '..');
const php = process.env.PHP_BIN || 'php';
const fixtures = execFileSync(php, [path.join(__dirname, 'render.php'), '--browser'], {encoding: 'utf8'});
const html = `<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="/frontend.css"><style>body{margin:24px;font-family:Arial,sans-serif;background:white}.fixtures{display:flex;gap:32px;flex-wrap:wrap}section{width:240px}</style><div class="fixtures">${fixtures}</div><script src="/frontend.js"></script>`;
const server = http.createServer((request, response) => {
    const asset = {'/frontend.css': 'text/css', '/frontend.js': 'application/javascript'}[request.url];
    response.setHeader('Content-Type', asset || 'text/html; charset=utf-8');
    response.end(asset ? fs.readFileSync(path.join(root, 'assets', request.url.slice(1))) : html);
});

(async () => {
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const executablePath = process.env.BROWSER_BIN || (process.platform === 'win32' ? 'C:/Program Files/Google/Chrome/Application/chrome.exe' : '/usr/bin/google-chrome');
    let browser;
    try {
        browser = await chromium.launch({executablePath, headless: true});
        const page = await browser.newPage({viewport: {width: 1100, height: 700}, reducedMotion: 'reduce'});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const url = `http://127.0.0.1:${server.address().port}`;
        await page.goto(url);
        await page.evaluate(() => document.fonts.ready);
        const band = page.locator('[data-fixture=ring_band] .wpcunter-ring-frame');
        const image = PNG.sync.read(await band.screenshot());
        const geometry = await band.evaluate(node => {
            const counter = node.closest('.wpcunter-counter');
            return {radius: Number(getComputedStyle(counter).getPropertyValue('--wpcunter-ring-radius')), diameter: node.getBoundingClientRect().width};
        });
        const radius = geometry.radius * geometry.diameter / 100;
        function redAt(x, y) { return image.data[(Math.round(y) * image.width + Math.round(x)) * 4]; }
        const pale = redAt(image.width / 2 - radius, image.height / 2);
        const saturated = redAt(image.width / 2 + radius, image.height / 2);
        assert.ok(pale > saturated + 70, `Angular gradient must become more opaque: ${pale} vs ${saturated}`);
        assert.ok(saturated < 160, 'Gradient is painted rather than empty');
        assert.equal(await page.locator('[data-fixture=ring_band] .wpcunter-ring-track').count(), 0);
        assert.equal(await page.locator('[data-fixture=ring_band] .wpcunter-number').textContent(), '100');
        await page.setViewportSize({width: 600, height: 700});
        await page.locator('[data-fixture=ring_band]').scrollIntoViewIfNeeded();
        const mobileWidth = (await band.boundingBox()).width;
        assert.equal(mobileWidth, 160, 'Mobile diameter overrides desktop');
        const markerRadius = await page.locator('[data-fixture=ring_band] .wpcunter-ring-marker').evaluate(node => parseFloat(getComputedStyle(node).r));
        assert.ok(Math.abs(markerRadius - 5) < .01, 'Marker size responds with the mobile ring');

        const animated = await browser.newPage({viewport: {width: 1100, height: 700}});
        animated.on('pageerror', error => errors.push(error.message));
        await animated.clock.install();
        await animated.goto(url);
        await animated.waitForFunction(() => document.querySelector('[data-fixture=ring_band] .counter-value').dataset.counterState === 'done');
        await animated.clock.runFor(48);
        const startWidth = await animated.locator('[data-fixture=ring_band] .wpcunter-number').evaluate(node => node.getBoundingClientRect().width);
        await animated.clock.runFor(1000);
        const progress = await animated.locator('[data-fixture=ring_band] .wpcunter-ring-arc').evaluate(node => Number(node.getAttribute('stroke-dashoffset')));
        assert.ok(progress > 10 && progress < 70, `Gradient mask advances with the counter: ${progress}`);
        assert.equal(await animated.locator('[data-fixture=ring_band] .wpcunter-number').evaluate(node => node.getBoundingClientRect().width), startWidth, 'Counting keeps a stable width');
        await animated.clock.runFor(2200);
        assert.equal(await animated.locator('[data-fixture=ring_band] .wpcunter-number').textContent(), '100');
        const angle = await animated.locator('[data-fixture=ring_band] .wpcunter-marker-orbit').getAttribute('transform');
        assert.ok(Math.abs(parseFloat(angle.slice(7)) - 420) < .001, 'Marker finishes on the symmetric lower endpoint');
        assert.deepEqual(errors, [], 'No browser runtime errors');
        fs.mkdirSync(path.join(root, 'dist'), {recursive: true});
        await animated.screenshot({path: path.join(root, 'dist/browser-smoke.png'), fullPage: true});
        console.log('PASS: real Chrome gradient pixels, SVG mask, responsive geometry, reduced motion, synchronized animation and stable width');
    } finally {
        if (browser) await browser.close();
        await new Promise(resolve => server.close(resolve));
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
