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

    function draw() {
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

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-wsl-layout], [data-wsl-orientation]')) {
            var layout = document.querySelector('[data-wsl-layout]');
            applyPreset(layout ? (parseInt(layout.value, 10) || 4) : 4);
            draw();
        }
    });

    var selectLogo = document.querySelector('[data-wsl-logo-select]');
    var removeLogo = document.querySelector('[data-wsl-logo-remove]');
    var logoId = document.querySelector('[data-wsl-logo-id]');
    var logoPreview = document.querySelector('[data-wsl-logo-preview]');
    var mediaFrame;

    function showLogo(id, url) {
        if (!logoId || !logoPreview) return;
        var image = logoPreview.querySelector('img');
        var empty = logoPreview.querySelector('span');
        logoId.value = id || '';
        image.src = url || '';
        image.hidden = !url;
        empty.hidden = !!url;
        logoPreview.classList.toggle('has-image', !!url);
        if (removeLogo) removeLogo.hidden = !url;
        if (selectLogo) selectLogo.textContent = url ? 'تغییر لوگو' : 'انتخاب لوگو';
    }

    if (selectLogo) selectLogo.addEventListener('click', function () {
        if (!window.wp || !wp.media) return;
        if (!mediaFrame) {
            mediaFrame = wp.media({ title: 'انتخاب لوگوی لیبل', button: { text: 'استفاده از این تصویر' }, library: { type: 'image' }, multiple: false });
            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                showLogo(attachment.id, url);
            });
        }
        mediaFrame.open();
    });

    if (removeLogo) removeLogo.addEventListener('click', function () { showLogo('', ''); });
    draw();
})();
