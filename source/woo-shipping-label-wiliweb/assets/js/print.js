(function () {
    'use strict';

    var postalSections = '.wsl-postal__recipient, .wsl-postal__sender, .wsl-postal__footer';

    function overflows(element) {
        return element.scrollHeight > element.clientHeight + 1 || element.scrollWidth > element.clientWidth + 1;
    }

    function labelOverflows(label) {
        return Array.prototype.some.call(label.querySelectorAll(postalSections), overflows);
    }

    function fitPostalLabel(label) {
        label.classList.remove('wsl-postal--compact', 'wsl-postal--ultra-compact', 'wsl-postal--overflow');
        label.style.removeProperty('font-size');
        label.removeAttribute('data-wsl-fitted');

        var preferredSize = parseFloat(window.getComputedStyle(label).fontSize) || 13;
        var size = preferredSize;

        if (labelOverflows(label)) {
            label.classList.add('wsl-postal--compact');
        }

        while (labelOverflows(label) && size > 10) {
            size = Math.max(10, size - 0.5);
            label.style.fontSize = size + 'px';
        }

        if (labelOverflows(label)) {
            label.classList.add('wsl-postal--ultra-compact');
        }

        while (labelOverflows(label) && size > 8) {
            size = Math.max(8, size - 0.5);
            label.style.fontSize = size + 'px';
        }

        if (labelOverflows(label)) {
            label.classList.add('wsl-postal--overflow');
        }

        label.setAttribute('data-wsl-fitted', 'true');
    }

    function fitPostalLabels() {
        Array.prototype.forEach.call(document.querySelectorAll('.wsl-label--postal'), fitPostalLabel);
    }

    function fitAfterFonts() {
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(fitPostalLabels);
        } else {
            fitPostalLabels();
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-wsl-print]');
        if (button) {
            fitPostalLabels();
            window.print();
        }
    });
    window.addEventListener('beforeprint', fitPostalLabels);
    fitAfterFonts();
}());
