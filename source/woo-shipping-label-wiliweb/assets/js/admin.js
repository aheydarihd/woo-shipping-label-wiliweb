(function () {
    var presets = {
        portrait: { 1: [1, 1], 2: [2, 1], 4: [2, 2], 6: [3, 2], 8: [4, 2] },
        landscape: { 1: [1, 1], 2: [1, 2], 4: [1, 4], 6: [2, 3], 8: [2, 4] }
    };

    function orientation() {
        var selected = document.querySelector('[data-wsl-orientation]:checked');
        return selected && selected.value === 'landscape' ? 'landscape' : 'portrait';
    }

    function presetFor(count) {
        var shape = presets[orientation()][count] || presets.portrait[4];
        return { columns: shape[0], rows: shape[1] };
    }

    function setSize(key, value) {
        var input = document.querySelector('[data-wsl-size="' + key + '"]');
        if (input) input.value = Math.round(value * 10) / 10;
    }

    function applyPreset(count) {
        var preset = presetFor(count);
        var margin = 6;
        var gap = 4;
        setSize('margin_top', margin);
        setSize('margin_right', margin);
        setSize('margin_bottom', margin);
        setSize('margin_left', margin);
        setSize('gap_horizontal', preset.columns > 1 ? gap : 0);
        setSize('gap_vertical', preset.rows > 1 ? gap : 0);
        setSize('label_width', (210 - margin * 2 - (preset.columns - 1) * gap) / preset.columns);
        setSize('label_height', (294 - margin * 2 - (preset.rows - 1) * gap) / preset.rows);
    }

    function drawLabels() {
        var select = document.querySelector('[data-wsl-layout]');
        var preview = document.querySelector('[data-wsl-preview]');
        if (!select || !preview) return;
        var count = parseInt(select.value, 10) || 4;
        var preset = presetFor(count);
        preview.innerHTML = '';
        preview.style.gridTemplateColumns = 'repeat(' + preset.columns + ',1fr)';
        preview.style.gridTemplateRows = 'repeat(' + preset.rows + ',1fr)';
        for (var i = 0; i < count; i++) preview.appendChild(document.createElement('i'));
    }

    function drawInvoice() {
        var preview = document.querySelector('[data-wsl-invoice-preview]');
        var template = document.querySelector('[data-wsl-invoice-template]:checked');
        if (!preview) return;
        preview.classList.toggle('is-classic', !!template && template.value === 'classic');
    }

    function setupMediaPicker(root) {
        var select = root.querySelector('[data-wsl-logo-select]');
        var remove = root.querySelector('[data-wsl-logo-remove]');
        var logoId = root.querySelector('[data-wsl-logo-id]');
        var preview = root.querySelector('[data-wsl-logo-preview]');
        var mediaFrame;
        if (!select || !logoId || !preview) return;

        function showLogo(id, url) {
            var image = preview.querySelector('img');
            var empty = preview.querySelector('span');
            logoId.value = id || '';
            image.src = url || '';
            image.hidden = !url;
            empty.hidden = !!url;
            preview.classList.toggle('has-image', !!url);
            if (remove) remove.hidden = !url;
            select.textContent = url ? 'تغییر لوگو' : 'انتخاب لوگو';
        }

        select.addEventListener('click', function () {
            if (!window.wp || !window.wp.media) return;
            if (!mediaFrame) {
                mediaFrame = window.wp.media({ title: 'انتخاب لوگو', button: { text: 'استفاده از این تصویر' }, library: { type: 'image' }, multiple: false });
                mediaFrame.on('select', function () {
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    showLogo(attachment.id, url);
                });
            }
            mediaFrame.open();
        });
        if (remove) remove.addEventListener('click', function () { showLogo('', ''); });
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-wsl-label-template]') && event.target.value === 'postal') {
            var postalLayout = document.querySelector('[data-wsl-layout]');
            var postalLandscape = document.querySelector('[data-wsl-orientation][value="landscape"]');
            var postalPortrait = document.querySelector('[data-wsl-orientation][value="portrait"]');
            if (postalLayout) postalLayout.value = '2';
            if (postalLandscape) postalLandscape.checked = true;
            if (postalPortrait) postalPortrait.checked = false;
            applyPreset(2);
            drawLabels();
        }
        if (event.target.matches('[data-wsl-layout], [data-wsl-orientation]')) {
            var layout = document.querySelector('[data-wsl-layout]');
            applyPreset(layout ? (parseInt(layout.value, 10) || 4) : 4);
            drawLabels();
        }
        if (event.target.matches('[data-wsl-invoice-template]')) drawInvoice();
    });

    document.querySelectorAll('[data-wsl-media-picker]').forEach(setupMediaPicker);
    drawLabels();
    drawInvoice();
})();
