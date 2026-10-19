const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/frontend.js'), 'utf8');
function run(start, final, reduced = false, supportsObserver = true) {
    const frames = [];
    const number = {textContent: String(final)};
    const value = {dataset: {start: String(start), final: String(final), duration: '3000'}, isConnected: true, querySelector: () => number};
    let intersection;
    const window = {matchMedia: () => ({matches: reduced}), requestAnimationFrame: fn => frames.push(fn)};
    if (supportsObserver) window.IntersectionObserver = true;
    const document = {nodeType: 9, readyState: 'complete', body: {}, querySelectorAll: () => [value]};
    vm.runInNewContext(source, {window, document, IntersectionObserver: class {constructor(fn) {intersection = fn;} observe() {} unobserve() {}}, MutationObserver: class {observe() {}}});
    assert.equal(number.textContent, String(supportsObserver || reduced ? final : start));
    if (supportsObserver) intersection([{isIntersecting: true, target: value}]);
    if (!reduced && start !== final) {
        frames.shift()(0);
        assert.equal(number.textContent, String(start));
        frames.shift()(1500);
        const midpoint = Number(number.textContent);
        assert.ok(midpoint >= Math.min(start, final) && midpoint <= Math.max(start, final));
        frames.shift()(3000);
    }
    assert.equal(number.textContent, String(final));
    if (supportsObserver) intersection([{isIntersecting: true, target: value}]);
    assert.equal(frames.length, 0);
}
run(0, 150); run(100, -5); run(0, 12.5); run(10, 10); run(0, 150, true);
run(0, 150, false, false);
console.log('PASS: visibility trigger, ascending/descending counts, exact final values, reduced motion, single run');
