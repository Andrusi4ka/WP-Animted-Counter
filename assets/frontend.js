(function () {
    'use strict';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    // Measure the widest possible digit string before counting changes the layout.
    function reserveNumberWidth(value) {
        var number = value.querySelector('.wpcunter-number');
        if (!number) return;
        var start = Number(value.dataset.start);
        var final = Number(value.dataset.final);
        var samples = [start, final, Math.ceil(start), Math.ceil(final)].map(String);
        var digits = Math.max.apply(null, samples.map(function (text) { return text.replace(/\D/g, '').length; }));
        var prefix = Math.min(start, final) < 0 ? '-' : '';
        var decimal = samples.some(function (text) { return text.includes('.'); }) ? '.' : '';
        for (var digit = 0; digit < 10; digit++) samples.push(prefix + String(digit).repeat(digits) + decimal);
        var original = number.textContent;
        number.style.minWidth = '0px';
        var width = 0;
        samples.forEach(function (sample) {
            number.textContent = sample;
            width = Math.max(width, number.getBoundingClientRect().width);
        });
        number.textContent = original;
        number.style.minWidth = Math.ceil(width) + 'px';
    }
    function ringArc(value) {
        var frame = value.closest('.wpcunter-counter');
        return frame ? frame.querySelector('.wpcunter-ring-arc') : null;
    }
    function updateRing(arc, progress) {
        if (!arc) return;
        var sweep = Number(arc.dataset.sweep) || 100;
        var svg = arc.closest ? arc.closest('svg') : arc.parentNode;
        arc.setAttribute('stroke-dashoffset', String(sweep * (1 - progress)));
        var band = svg && svg.querySelector('.wpcunter-ring-band');
        if (band) {
            band.setAttribute('stroke-dashoffset', String(sweep * (1 - progress)));
            if (svg.style) svg.style.setProperty('--wpcunter-band-fill', Math.max(.001, sweep * 3.6 * progress) + 'deg');
        }
        var marker = svg && svg.querySelector('.wpcunter-marker-orbit');
        if (marker) {
            var angle = Number(arc.dataset.startAngle) + sweep * 3.6 * progress;
            marker.setAttribute('transform', 'rotate(' + angle + ' 50 50)');
        }
    }
    function animate(value) {
        if (value.dataset.counterState === 'done') return;
        reserveNumberWidth(value);
        value.dataset.counterState = 'done';
        var number = value.querySelector('.wpcunter-number');
        var start = Number(value.dataset.start);
        var final = Number(value.dataset.final);
        var duration = Number(value.dataset.duration) || 2000;
        var arc = ringArc(value);
        updateRing(arc, 1);
        if (!number || reducedMotion.matches || start === final) return;
        var began;
        number.textContent = String(start);
        updateRing(arc, 0);
        function tick(now) {
            if (!value.isConnected) return;
            if (began === undefined) began = now;
            var progress = Math.min(1, (now - began) / duration);
            if (reducedMotion.matches) progress = 1;
            var eased = value.dataset.slowDown === '1' ? 1 - Math.pow(1 - progress, 4) : progress;
            updateRing(arc, eased);
            number.textContent = progress === 1 ? String(final) : String(Math.ceil(start + (final - start) * eased));
            if (progress < 1) window.requestAnimationFrame(tick);
        }
        window.requestAnimationFrame(tick);
    }
    var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) { observer.unobserve(entry.target); animate(entry.target); }
        });
    }) : null;
    function scan(root) {
        if (root.nodeType !== 1 && root.nodeType !== 9) return;
        var values = Array.from(root.querySelectorAll('.wpcunter-counter .counter-value'));
        if (root.matches && root.matches('.wpcunter-counter .counter-value')) values.unshift(root);
        values.forEach(function (value) {
            if (value.dataset.counterState) return;
            value.dataset.counterState = 'waiting';
            reserveNumberWidth(value);
            var arc = ringArc(value);
            if (arc && !reducedMotion.matches && Number(value.dataset.start) !== Number(value.dataset.final)) {
                updateRing(arc, 0);
            }
            if (observer) observer.observe(value); else animate(value);
        });
    }
    function init() {
        scan(document);
        function refreshWidths() {
            document.querySelectorAll('.wpcunter-counter .counter-value').forEach(reserveNumberWidth);
        }
        var resizePending = false;
        window.addEventListener('resize', function () {
            if (resizePending) return;
            resizePending = true;
            window.requestAnimationFrame(function () { resizePending = false; refreshWidths(); });
        });
        if (document.fonts) document.fonts.ready.then(refreshWidths);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) { mutation.addedNodes.forEach(scan); });
        }).observe(document.body, {childList: true, subtree: true});
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
