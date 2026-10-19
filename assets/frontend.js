(function () {
    'use strict';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    function animate(value) {
        if (value.dataset.counterState === 'done') return;
        value.dataset.counterState = 'done';
        var number = value.querySelector('.wpcunter-number');
        var start = Number(value.dataset.start);
        var final = Number(value.dataset.final);
        var duration = Number(value.dataset.duration) || 3000;
        if (!number || reducedMotion.matches || start === final) return;
        var began;
        number.textContent = String(start);
        function tick(now) {
            if (!value.isConnected) return;
            if (began === undefined) began = now;
            var progress = Math.min(1, (now - began) / duration);
            if (reducedMotion.matches) progress = 1;
            var eased = (1 - Math.cos(progress * Math.PI)) / 2;
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
            if (observer) observer.observe(value); else animate(value);
        });
    }
    function init() {
        scan(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) { mutation.addedNodes.forEach(scan); });
        }).observe(document.body, {childList: true, subtree: true});
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
