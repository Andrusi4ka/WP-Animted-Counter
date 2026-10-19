const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const assert = require('node:assert/strict');
const {JSDOM} = require('jsdom');

const root = path.join(__dirname, '..');
const php = process.env.PHP_BIN || 'php';
const html = execFileSync(php, [path.join(__dirname, 'render.php'), '--html'], {encoding: 'utf8'});
const css = fs.readFileSync(path.join(root, 'assets/admin.css'), 'utf8');
const dom = new JSDOM(`<style>${css}</style><form>${html}</form>`, {runScripts: 'outside-only'});
const {document, Event, KeyboardEvent, FormData} = dom.window;
dom.window.eval(fs.readFileSync(path.join(root, 'assets/admin.js'), 'utf8'));

const input = document.querySelector('#wpcunter-font_size');
const mobile = document.querySelector('#wpcunter-font_size_mobile');
const field = input.closest('.wpcunter-responsive');
const mobileButton = field.querySelector('[data-device=mobile]');
mobileButton.click();
assert.equal(input.hidden, true);
assert.equal(mobile.hidden, false);
assert.equal(field.querySelector('label').htmlFor, mobile.id);
assert.equal(mobileButton.getAttribute('aria-pressed'), 'true');
mobile.value = '22.5';
mobile.dispatchEvent(new Event('input', {bubbles: true}));
assert.equal(mobileButton.classList.contains('has-value'), true);
assert.equal(new FormData(document.querySelector('form')).get('wpcunter_settings[font_size_mobile]'), '22.5');
field.querySelector('[data-device=desktop]').click();
assert.equal(mobile.value, '22.5', 'Switching device preserves values');
assert.equal(mobile.hidden, true);
assert.equal(dom.window.getComputedStyle(input).height, '42px');
assert.equal(dom.window.getComputedStyle(document.querySelector('#wpcunter-font_weight')).height, '42px');

const scheme = document.querySelector('#wpcunter-color_scheme');
assert.equal(document.querySelector('[data-field=color]').hidden, true);
scheme.value = 'custom';
scheme.dispatchEvent(new Event('change', {bubbles: true}));
assert.equal(document.querySelector('[data-field=color]').hidden, false);
document.querySelector('input[name="wpcunter_settings[animation_position]"][value=left]').click();
assert.equal(document.querySelector('[data-layout=vertical_align]').hidden, false);
document.querySelector('input[name="wpcunter_settings[animation_position]"][value=top]').click();
assert.equal(document.querySelector('[data-layout=vertical_align]').hidden, true);

const tabs = document.querySelectorAll('[role=tab]');
tabs[1].click();
assert.equal(document.querySelector('#wpcunter-panel-design').hidden, false);
assert.equal(document.querySelector('#wpcunter-panel-general').hidden, true);
tabs[1].dispatchEvent(new KeyboardEvent('keydown', {key: 'ArrowRight', bubbles: true}));
assert.equal(tabs[2].getAttribute('aria-selected'), 'true');
assert.equal(document.activeElement, tabs[2]);

assert.equal(document.querySelectorAll('input[name="wpcunter_settings[label]"]').length, 1);
assert.equal(document.querySelector('#wpcunter-label').closest('.wpcunter-column-left') !== null, true);
assert.equal(document.querySelector('#wpcunter-color_scheme').closest('.wpcunter-column-right') !== null, true);
assert.equal(document.querySelector('#wpcunter-size'), null);
assert.equal(document.querySelector('#wpcunter-label_size'), null);
assert.equal(document.querySelectorAll('.wpcunter-layout img').length, 9);
dom.window.close();
console.log('PASS: admin DOM, device switching, value persistence, dependencies, keyboard tabs, equal heights and columns');
