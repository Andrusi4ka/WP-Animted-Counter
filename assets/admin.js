(function () {
    'use strict';
    document.querySelectorAll('.wpcunter-editor').forEach(function (editor) {
        function dependencies() {
            editor.querySelector('[data-field=color]').hidden = editor.querySelector('#wpcunter-color_scheme').value !== 'custom';
            var effect = editor.querySelector('#wpcunter-counter_animation').value;
            var descriptions = {
                none: 'Animates the number without a visual effect.',
                ring: 'A full circle fills with the count. The number and label stay inside.',
                ring_marker: 'An open arc fills with the count. A circular marker follows its tip.',
                ring_band: 'An open gradient arc fades from fully transparent to solid color at the moving marker. No outline.'
            };
            editor.querySelector('[data-effect-description]').textContent = descriptions[effect] || descriptions.none;
            editor.querySelector('[data-field=ring_thickness] label').textContent = effect === 'ring_band' ? 'Gradient thickness, px' : 'Ring thickness, px';
            editor.querySelector('[data-field=ring_color] label').textContent = effect === 'ring_band' ? 'Gradient color' : (effect === 'ring_marker' ? 'Arc color' : 'Ring color');
            var ring = ['ring', 'ring_marker', 'ring_band'].includes(effect);
            editor.querySelector('.wpcunter-marker-fields').hidden = !['ring_marker', 'ring_band'].includes(effect);

            editor.querySelector('.wpcunter-ring-fields').hidden = !ring;
            ['ring_color', 'ring_thickness', 'ring_size'].forEach(function (key) {
                editor.querySelector('[data-field=' + key + ']').hidden = !ring;
            });
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
