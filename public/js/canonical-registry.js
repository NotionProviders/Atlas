(function () {
    var modal = document.getElementById('canonical-modal');
    var slot = document.getElementById('canonical-modal-slot');

    if (!modal || !slot) return;

    var indexUrl = modal.getAttribute('data-index-url') || '/console/canonical-databases';
    var loading = false;

    function isOpen() {
        return !modal.classList.contains('hidden');
    }

    function bindPanelEvents() {
        var closeBtn = slot.querySelector('[data-canonical-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
    }

    function openModal(html, peekUrl, panelUrl, pushState) {
        slot.innerHTML = html;
        bindPanelEvents();
        modal.classList.remove('hidden');
        document.body.classList.add('canonical-modal-open');

        if (pushState !== false && peekUrl) {
            history.pushState({
                canonicalPeek: true,
                peekUrl: peekUrl,
                panelUrl: panelUrl,
            }, '', peekUrl);
        }
    }

    function closeModal(pushState) {
        modal.classList.add('hidden');
        slot.innerHTML = '';
        document.body.classList.remove('canonical-modal-open');

        if (pushState !== false && window.location.pathname.indexOf('/canonical-databases/c/') !== -1) {
            history.replaceState({ canonicalPeek: false }, '', indexUrl);
        }
    }

    function loadPanel(panelUrl, peekUrl, pushState) {
        if (loading) return;
        loading = true;
        slot.innerHTML = '<div class="canonical-peek canonical-peek-loading"><p class="console-muted">Loading…</p></div>';
        modal.classList.remove('hidden');
        document.body.classList.add('canonical-modal-open');

        fetch(panelUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Failed to load panel');
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
        if (btn) {
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
        if (window.location.pathname.indexOf('/canonical-databases/c/') !== -1) {
            var state = e.state || {};
            var panelUrl = state.panelUrl || modal.getAttribute('data-open-panel-url');
            var peekUrl = state.peekUrl || window.location.pathname;

            if (panelUrl && !isOpen()) {
                loadPanel(panelUrl, peekUrl, false);
            }
            return;
        }

        if (isOpen()) {
            closeModal(false);
        }
    });

    var autoSlug = modal.getAttribute('data-open-slug');
    var autoPanelUrl = modal.getAttribute('data-open-panel-url');
    var autoPeekUrl = modal.getAttribute('data-open-peek-url');

    if (autoSlug && autoPanelUrl) {
        loadPanel(autoPanelUrl, autoPeekUrl, false);
    }
})();
