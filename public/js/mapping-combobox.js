(function () {
    function initCombobox(root) {
        if (root.getAttribute('data-combobox-init') === 'true') {
            return;
        }

        var input = root.querySelector('.canonical-combobox-input');
        var list = root.querySelector('.canonical-combobox-list');
        var mapForm = root.querySelector('.canonical-combobox-map-form');
        var createForm = root.querySelector('.canonical-combobox-create-form');
        var optionsEl = root.querySelector('.canonical-combobox-options');

        if (!input || !list || !mapForm || !createForm || !optionsEl) {
            return;
        }

        root.setAttribute('data-combobox-init', 'true');

        var options = [];
        try {
            options = JSON.parse(optionsEl.textContent || '[]');
        } catch (e) {
            options = [];
        }

        var mapIdInput = mapForm.querySelector('[name="canonical_database_id"]');
        var createNameInput = createForm.querySelector('[name="name"]');

        function normalized(value) {
            return (value || '').trim().toLowerCase();
        }

        function closeList() {
            list.classList.add('hidden');
        }

        function openList() {
            list.classList.remove('hidden');
        }

        function renderList(query) {
            var q = normalized(query);
            list.innerHTML = '';

            var matches = options.filter(function (opt) {
                return !q || normalized(opt.name).indexOf(q) !== -1;
            });

            matches.forEach(function (opt) {
                var li = document.createElement('li');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'canonical-combobox-option';
                btn.setAttribute('role', 'option');
                btn.textContent = opt.name + (opt.is_custom ? ' (not in template)' : '');
                btn.addEventListener('click', function () {
                    selectExisting(opt);
                });
                li.appendChild(btn);
                list.appendChild(li);
            });

            var trimmedQuery = (query || '').trim();
            var exact = trimmedQuery && options.some(function (opt) {
                return normalized(opt.name) === q;
            });

            if (trimmedQuery && !exact) {
                var createLi = document.createElement('li');
                createLi.className = 'canonical-combobox-create-item';
                var createOption = document.createElement('button');
                createOption.type = 'button';
                createOption.className = 'canonical-combobox-option canonical-combobox-option-create';
                createOption.setAttribute('role', 'option');
                createOption.textContent = 'Create "' + trimmedQuery + '" and map';
                createOption.addEventListener('click', function () {
                    createAndMap(trimmedQuery);
                });
                createLi.appendChild(createOption);
                list.appendChild(createLi);
            }

            if (list.children.length === 0) {
                closeList();
                return;
            }

            openList();
        }

        function selectExisting(opt) {
            input.value = opt.name;
            input.setAttribute('data-selected-id', String(opt.id));
            mapIdInput.value = String(opt.id);
            closeList();
            mapForm.submit();
        }

        function createAndMap(name) {
            createNameInput.value = name;
            closeList();
            createForm.submit();
        }

        function openFullList() {
            renderList('');
        }

        input.addEventListener('focus', function () {
            openFullList();
        });

        input.addEventListener('click', function () {
            if (list.classList.contains('hidden')) {
                openFullList();
            }
        });

        input.addEventListener('input', function () {
            input.removeAttribute('data-selected-id');
            renderList(input.value);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var q = input.value.trim();
                if (!q) {
                    return;
                }

                var exact = options.find(function (opt) {
                    return normalized(opt.name) === normalized(q);
                });

                if (exact) {
                    selectExisting(exact);
                } else {
                    createAndMap(q);
                }
            }

            if (e.key === 'Escape') {
                closeList();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closeList();
            }
        });
    }

    window.initMappingComboboxs = function (container) {
        var scope = container || document;
        scope.querySelectorAll('.canonical-combobox').forEach(initCombobox);
    };

    window.initMappingComboboxs();
})();
