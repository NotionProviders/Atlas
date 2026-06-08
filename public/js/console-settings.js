(function () {
    var menu = document.getElementById('console-gear-menu');
    var toggle = document.getElementById('console-gear-toggle');
    var panel = document.getElementById('console-gear-panel');

    if (!menu || !toggle || !panel) return;

    function close() {
        panel.classList.add('hidden');
        toggle.setAttribute('aria-expanded', 'false');
    }

    function open() {
        panel.classList.remove('hidden');
        toggle.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.classList.contains('hidden')) {
            open();
        } else {
            close();
        }
    });

    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target)) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            close();
        }
    });
})();
