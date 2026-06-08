(function () {
    var modal = document.getElementById('mapping-modal');
    var slot = document.getElementById('mapping-modal-slot');

    if (!modal || !slot) {
        return;
    }

    var indexUrl = modal.getAttribute('data-index-url') || window.location.pathname;
    var loading = false;

    function isPeekPath(path) {
        return path.indexOf('/mappings/c/database/') !== -1
            || path.indexOf('/mappings/c/canonical/') !== -1;
    }

    function isOpen() {
        return !modal.classList.contains('hidden');
    }

    function bindPanelEvents() {
        var closeBtn = slot.querySelector('[data-console-peek-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                closeModal(true);
            });
        }
    }

    function openModal(html, peekUrl, panelUrl, pushState) {
        slot.innerHTML = html;
        bindPanelEvents();
        if (typeof window.initMappingComboboxs === 'function') {
            window.initMappingComboboxs(slot);
        }
        modal.classList.remove('hidden');
        document.body.classList.add('canonical-modal-open');

        if (pushState !== false && peekUrl) {
            history.pushState({
                mappingPeek: true,
                peekUrl: peekUrl,
                panelUrl: panelUrl,
            }, '', peekUrl);
        }
    }

    function closeModal(pushState) {
        modal.classList.add('hidden');
        slot.innerHTML = '';
        document.body.classList.remove('canonical-modal-open');

        if (pushState !== false && isPeekPath(window.location.pathname)) {
            history.replaceState({ mappingPeek: false }, '', indexUrl);
        }
    }

    function loadPanel(panelUrl, peekUrl, pushState) {
        if (loading) {
            return;
        }

        loading = true;
        slot.innerHTML = '<div class="canonical-peek canonical-peek-loading"><p class="console-muted">Loading…</p></div>';
        modal.classList.remove('hidden');
        document.body.classList.add('canonical-modal-open');

        fetch(panelUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Failed to load panel');
                }
                return response.text();
            })
            .then(function (html) {
                openModal(html, peekUrl, panelUrl, pushState);
            })
            .catch(function () {
                slot.innerHTML = '<div class="canonical-peek"><p class="console-muted">Could not load details.</p></div>';
            })
            .finally(function () {
                loading = false;
            });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.canonical-info-btn');
        if (btn && !modal.contains(btn)) {
            e.preventDefault();
            loadPanel(btn.getAttribute('data-panel-url'), btn.getAttribute('data-peek-url'), true);
            return;
        }

        if (e.target.classList.contains('canonical-modal-backdrop')) {
            closeModal(true);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            closeModal(true);
        }
    });

    window.addEventListener('popstate', function (e) {
        var path = window.location.pathname;
        if (isPeekPath(path)) {
            var state = e.state || {};
            var panelUrl = state.panelUrl || modal.getAttribute('data-open-panel-url');
            var peekUrl = state.peekUrl || path;

            if (panelUrl && !isOpen()) {
                loadPanel(panelUrl, peekUrl, false);
            }
            return;
        }

        if (isOpen()) {
            closeModal(false);
        }
    });

    var autoPanelUrl = modal.getAttribute('data-open-panel-url');
    var autoPeekUrl = modal.getAttribute('data-open-peek-url');

    if (autoPanelUrl) {
        loadPanel(autoPanelUrl, autoPeekUrl, false);
    }
})();
