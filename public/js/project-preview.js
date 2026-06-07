(function () {
    var preview = document.getElementById('atlas-preview');
    if (!preview) return;

    var iframe = preview.querySelector('.preview-iframe');
    var openLink = preview.querySelector('.preview-open-link');
    var tabs = preview.querySelectorAll('.preview-tab');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            iframe.src = tab.getAttribute('data-embed-url');
            if (openLink) {
                openLink.href = tab.getAttribute('data-atlas-url');
            }
        });
    });
})();
