(function () {
    'use strict';
    document.querySelectorAll('.wpcunter-editor').forEach(function (editor) {
        var tabs = Array.from(editor.querySelectorAll('[role=tab]'));
        function activate(tab) {
            tabs.forEach(function (item) { item.setAttribute('aria-selected', String(item === tab)); });
            editor.querySelectorAll('[role=tabpanel]').forEach(function (panel) { panel.hidden = panel.id !== tab.getAttribute('aria-controls'); });
        }
        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activate(tab); });
            tab.addEventListener('keydown', function (event) {
                var next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next !== undefined) { event.preventDefault(); activate(tabs[next]); tabs[next].focus(); }
            });
        });
        function dependencies() {
            editor.querySelector('[data-field=color]').hidden = editor.querySelector('#wpcunter-color_scheme').value !== 'custom';
            var position = editor.querySelector('input[name="wpcunter_settings[animation_position]"]:checked');
            editor.querySelector('[data-layout=vertical_align]').hidden = position && position.value === 'top';
        }
        editor.querySelectorAll('.wpcunter-responsive').forEach(function (field) {
            var label = field.querySelector('label');
            var inputs = Array.from(field.querySelectorAll('[data-device-input]'));
            function sync() {
                var inherited = inputs[0].value;
                inputs.forEach(function (input, index) {
                    if (index > 0 && input.tagName === 'INPUT') input.placeholder = inherited || 'Inherit';
                    if (input.value !== '') inherited = input.value;
                });
                field.querySelectorAll('.wpcunter-devices button').forEach(function (button) {
                    var input = inputs.find(function (item) { return item.dataset.deviceInput === button.dataset.device; });
                    button.classList.toggle('has-value', button.dataset.device !== 'desktop' && input.value !== '');
                });
            }
            field.querySelectorAll('.wpcunter-devices button').forEach(function (button) {
                button.addEventListener('click', function () {
                    field.querySelectorAll('.wpcunter-devices button').forEach(function (item) { item.setAttribute('aria-pressed', String(item === button)); });
                    inputs.forEach(function (input) { input.hidden = input.dataset.deviceInput !== button.dataset.device; if (!input.hidden) label.htmlFor = input.id; });
                });
            });
            field.addEventListener('input', sync);
            field.addEventListener('change', sync);
            sync();
        });
        editor.addEventListener('change', dependencies);
        dependencies();
    });
}());
