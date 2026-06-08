(function () {
    function normalized(value) {
        return (value || '').trim().toLowerCase();
    }

    function bindComboboxEvents(root, input, list, renderList, openFullList) {
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

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                list.classList.add('hidden');
            }
        });
    }

    function initCanonicalCombobox(root) {
        if (root.getAttribute('data-combobox-init') === 'true') {
            return;
        }

        var input = root.querySelector('.canonical-combobox-input');
        var list = root.querySelector('.canonical-combobox-list');
        var mapForm = root.querySelector('.canonical-combobox-map-form');
        var createForm = root.querySelector('.canonical-combobox-create-form');
        var unmapForm = root.querySelector('.canonical-combobox-unmap-form');
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

        function closeList() {
            list.classList.add('hidden');
        }

        function openList() {
            list.classList.remove('hidden');
        }

        function unmap() {
            closeList();
            if (unmapForm) {
                unmapForm.submit();
            }
        }

        function renderList(query) {
            var q = normalized(query);
            var selectedId = input.getAttribute('data-selected-id');
            list.innerHTML = '';

            if (selectedId) {
                var selectedOpt = options.find(function (opt) {
                    return String(opt.id) === selectedId;
                });
                var selectedName = selectedOpt ? selectedOpt.name : (input.value || '').trim();

                if (selectedName) {
                    var selectedLi = document.createElement('li');
                    selectedLi.className = 'canonical-combobox-selected-item';
                    var selectedRow = document.createElement('div');
                    selectedRow.className = 'canonical-combobox-selected-row';

                    var selectedLabel = document.createElement('span');
                    selectedLabel.className = 'canonical-combobox-selected-label';
                    selectedLabel.textContent = selectedName + (selectedOpt && selectedOpt.is_custom ? ' (not in template)' : '');

                    var clearBtn = document.createElement('button');
                    clearBtn.type = 'button';
                    clearBtn.className = 'canonical-combobox-selected-clear';
                    clearBtn.setAttribute('aria-label', 'Remove mapping for ' + selectedName);
                    clearBtn.innerHTML = '&times;';
                    clearBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        unmap();
                    });

                    selectedRow.appendChild(selectedLabel);
                    selectedRow.appendChild(clearBtn);
                    selectedLi.appendChild(selectedRow);
                    list.appendChild(selectedLi);
                }
            }

            var matches = options.filter(function (opt) {
                if (selectedId && String(opt.id) === selectedId) {
                    return false;
                }

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

        bindComboboxEvents(root, input, list, renderList, openFullList);
    }

    function initTeamspaceCombobox(root) {
        if (root.getAttribute('data-combobox-init') === 'true') {
            return;
        }

        var input = root.querySelector('.teamspace-combobox-input');
        var list = root.querySelector('.teamspace-combobox-list');
        var assignForm = root.querySelector('.teamspace-combobox-assign-form');
        var createForm = root.querySelector('.teamspace-combobox-create-form');
        var optionsEl = root.querySelector('.teamspace-combobox-options');

        if (!input || !list || !assignForm || !createForm || !optionsEl) {
            return;
        }

        root.setAttribute('data-combobox-init', 'true');

        var options = [];
        try {
            options = JSON.parse(optionsEl.textContent || '[]');
        } catch (e) {
            options = [];
        }

        var assignIdInput = assignForm.querySelector('[name="project_teamspace_id"]');
        var createNameInput = createForm.querySelector('[name="name"]');

        function closeList() {
            list.classList.add('hidden');
        }

        function openList() {
            list.classList.remove('hidden');
        }

        function unassign() {
            input.value = '';
            input.removeAttribute('data-selected-id');
            assignIdInput.value = '';
            closeList();
            assignForm.submit();
        }

        function renderList(query) {
            var q = normalized(query);
            list.innerHTML = '';

            if (!q && input.getAttribute('data-selected-id')) {
                var unassignLi = document.createElement('li');
                var unassignBtn = document.createElement('button');
                unassignBtn.type = 'button';
                unassignBtn.className = 'canonical-combobox-option';
                unassignBtn.setAttribute('role', 'option');
                unassignBtn.textContent = 'Unassigned';
                unassignBtn.addEventListener('click', unassign);
                unassignLi.appendChild(unassignBtn);
                list.appendChild(unassignLi);
            }

            var matches = options.filter(function (opt) {
                return !q || normalized(opt.name).indexOf(q) !== -1;
            });

            matches.forEach(function (opt) {
                var li = document.createElement('li');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'canonical-combobox-option';
                btn.setAttribute('role', 'option');
                btn.textContent = opt.name;
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
                createOption.textContent = 'Create "' + trimmedQuery + '" and assign';
                createOption.addEventListener('click', function () {
                    createAndAssign(trimmedQuery);
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
            assignIdInput.value = String(opt.id);
            closeList();
            assignForm.submit();
        }

        function createAndAssign(name) {
            createNameInput.value = name;
            closeList();
            createForm.submit();
        }

        function openFullList() {
            renderList('');
        }

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
                    createAndAssign(q);
                }
            }

            if (e.key === 'Escape') {
                closeList();
            }
        });

        bindComboboxEvents(root, input, list, renderList, openFullList);
    }

    window.initMappingComboboxs = function (container) {
        var scope = container || document;
        scope.querySelectorAll('.canonical-combobox').forEach(initCanonicalCombobox);
        scope.querySelectorAll('.teamspace-combobox').forEach(initTeamspaceCombobox);
    };

    window.initMappingComboboxs();
})();
