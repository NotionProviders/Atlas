(function () {
    document.querySelectorAll('.workspace-expand').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var parentId = btn.getAttribute('data-parent-id');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            var rows = document.querySelectorAll('.workspace-under-' + parentId);
            rows.forEach(function (row) {
                row.style.display = expanded ? 'none' : '';
            });
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            btn.textContent = expanded ? '▸' : '▾';
        });
    });

    document.querySelectorAll('.workspace-copy').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var value = btn.getAttribute('data-copy');
            if (!value || !navigator.clipboard) return;
            navigator.clipboard.writeText(value).then(function () {
                var prev = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(function () { btn.textContent = prev; }, 1200);
            });
        });
    });

    var panel = document.getElementById('workspace-properties-panel');
    var panelTitle = document.getElementById('workspace-properties-title');
    var panelBody = document.getElementById('workspace-properties-body');
    var closeBtn = document.getElementById('workspace-properties-close');

    if (panel) {
        document.querySelectorAll('.workspace-db-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var label = btn.getAttribute('data-label') || 'Database';
                var props = [];
                try {
                    props = JSON.parse(btn.getAttribute('data-props') || '[]');
                } catch (e) {
                    props = [];
                }

                panelTitle.textContent = label + ' — Properties';
                panelBody.innerHTML = '';

                if (!props.length) {
                    panelBody.innerHTML = '<p class="console-muted">No properties recorded for this database.</p>';
                } else {
                    var table = document.createElement('table');
                    table.className = 'workspace-props-table';
                    table.innerHTML = '<thead><tr><th>Name</th><th>Type</th><th>Options</th></tr></thead>';
                    var tbody = document.createElement('tbody');
                    props.forEach(function (prop) {
                        var tr = document.createElement('tr');
                        var options = prop.options ? JSON.stringify(prop.options) : '—';
                        tr.innerHTML = '<td>' + escapeHtml(prop.name) + '</td><td>' + escapeHtml(prop.type) + '</td><td>' + escapeHtml(options) + '</td>';
                        tbody.appendChild(tr);
                    });
                    table.appendChild(tbody);
                    panelBody.appendChild(table);
                }

                panel.classList.remove('hidden');
            });
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                panel.classList.add('hidden');
            });
        }
    }

    var tableApp = document.getElementById('workspace-table-app');
    if (tableApp) {
        var storageKey = 'atlas-table-columns';
        var columns = [
            { id: 'kind', label: 'Kind', default: true },
            { id: 'notion_page_id', label: 'Notion page ID', default: true },
            { id: 'notion_data_source_id', label: 'Data source ID', default: true },
            { id: 'notes', label: 'Notes', default: true },
        ];

        var saved = {};
        try {
            saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch (e) {
            saved = {};
        }

        columns.forEach(function (col) {
            if (saved[col.id] === undefined) {
                saved[col.id] = col.default;
            }
        });

        function applyColumns() {
            columns.forEach(function (col) {
                var visible = saved[col.id];
                tableApp.querySelectorAll('[data-col="' + col.id + '"]').forEach(function (el) {
                    el.style.display = visible ? '' : 'none';
                });
            });
            localStorage.setItem(storageKey, JSON.stringify(saved));
        }

        var toggleWrap = document.getElementById('workspace-column-toggle');
        if (toggleWrap) {
            columns.forEach(function (col) {
                var label = document.createElement('label');
                label.className = 'workspace-col-toggle-item';
                var input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = saved[col.id];
                input.addEventListener('change', function () {
                    saved[col.id] = input.checked;
                    applyColumns();
                });
                label.appendChild(input);
                label.appendChild(document.createTextNode(' ' + col.label));
                toggleWrap.appendChild(label);
            });
        }

        applyColumns();
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
})();
