<script>
(() => {
    const setupId = {{ $setup->id }};
    const base = @json(url('/almanac/setups/' . $setup->id));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());

    const getModal = id => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
    const manageModalElement = document.getElementById('manageWeekBlocksModal');
    const editWeekModalElement = document.getElementById('editWeekBlockModal');

    let currentWeekBlockId = null;
    let reopenManagerAfterEdit = false;

    document.getElementById('openWeekBlockManagerBtn')?.addEventListener('click', async () => {
        getModal('manageWeekBlocksModal').show();
        await loadWeekBlocks();
    });

    document.getElementById('existingWeekBlocksTab')?.addEventListener('shown.bs.tab', loadWeekBlocks);
    document.getElementById('refreshWeekBlocksBtn')?.addEventListener('click', loadWeekBlocks);

    document.querySelectorAll('.js-add-event').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.getElementById('eventForm');
            form.reset();
            form.action = `${base}/events`;
            removeMethod(form);

            document.getElementById('eventModalTitle').textContent = 'Add Almanac Event';
            form.elements.start_date.value = button.dataset.date;
            form.elements.end_date.value = '';
            form.elements.event_column.value = button.dataset.column || 'academic';
            form.elements.applies_to_all.checked = true;

            getModal('eventModal').show();
        });
    });

    document.querySelectorAll('.js-edit-event').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const data = await fetchJson(`${base}/events/${button.dataset.id}`);
                const form = document.getElementById('eventForm');

                form.action = `${base}/events/${data.id}`;
                ensureMethod(form, 'PUT');
                document.getElementById('eventModalTitle').textContent = 'Edit Almanac Event';
                fillForm(form, data);
                setMultiSelect(form.querySelector('[name="program_group_ids[]"]'), data.program_group_ids || []);

                getModal('eventModal').show();
            } catch (error) {
                showError(error.message);
            }
        });
    });

    document.querySelectorAll('.js-edit-group').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                const data = await fetchJson(`${base}/groups/${button.dataset.id}`);
                const form = document.getElementById('editGroupForm');

                form.action = `${base}/groups/${data.id}`;
                fillForm(form, data);
                setMultiSelect(form.querySelector('[name="program_ids[]"]'), data.program_ids || []);

                getModal('editGroupModal').show();
            } catch (error) {
                showError(error.message);
            }
        });
    });

    // Clicking a populated cell opens the same edit modal used by the manager.
    document.querySelectorAll('.js-edit-week-block').forEach(cell => {
        cell.addEventListener('click', () => openWeekBlockEditor(cell.dataset.id, false));
    });

    editWeekModalElement?.addEventListener('hidden.bs.modal', () => {
        if (reopenManagerAfterEdit) {
            reopenManagerAfterEdit = false;
            getModal('manageWeekBlocksModal').show();
            loadWeekBlocks();
        }
    });

    document.getElementById('deleteWeekBlockBtn')?.addEventListener('click', async () => {
        if (!currentWeekBlockId) return;

        const accepted = window.confirm('Delete this week block? This action cannot be undone.');
        if (!accepted) return;

        try {
            await sendRequest(`${base}/week-blocks/${currentWeekBlockId}`, 'DELETE');
            getModal('editWeekBlockModal').hide();
            window.location.reload();
        } catch (error) {
            showError(error.message);
        }
    });

    async function loadWeekBlocks() {
        const tbody = document.getElementById('weekBlocksTableBody');
        const count = document.getElementById('weekBlocksCount');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Loading week blocks...</td></tr>';

        try {
            const response = await fetchJson(`${base}/week-blocks`);
            const blocks = response.data || [];
            count.textContent = `${response.count ?? blocks.length} block(s)`;

            if (!blocks.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No week blocks have been created.</td></tr>';
                return;
            }

            tbody.innerHTML = blocks.map(block => `
                <tr>
                    <td>${escapeHtml(block.program_group_name || 'Unknown group')}</td>
                    <td>
                        <span class="badge border" style="background:${safeColor(block.background_color, '#ffffff')};color:${safeColor(block.text_color, '#000000')}">
                            ${escapeHtml(block.full_label || '')}
                        </span>
                    </td>
                    <td>${escapeHtml(block.start_date || '')}</td>
                    <td>${escapeHtml(block.end_date || '')}</td>
                    <td>${escapeHtml(capitalize(block.block_type || ''))}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary js-manager-edit-block" data-id="${block.id}">
                            Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger js-manager-delete-block" data-id="${block.id}">
                            Delete
                        </button>
                    </td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.js-manager-edit-block').forEach(button => {
                button.addEventListener('click', () => openWeekBlockEditor(button.dataset.id, true));
            });

            tbody.querySelectorAll('.js-manager-delete-block').forEach(button => {
                button.addEventListener('click', async () => {
                    if (!window.confirm('Delete this week block?')) return;

                    try {
                        await sendRequest(`${base}/week-blocks/${button.dataset.id}`, 'DELETE');
                        await loadWeekBlocks();
                    } catch (error) {
                        showError(error.message);
                    }
                });
            });
        } catch (error) {
            count.textContent = 'Unable to load blocks';
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">${escapeHtml(error.message)}</td></tr>`;
        }
    }

    async function openWeekBlockEditor(id, openedFromManager) {
        if (!id) return;

        try {
            const data = await fetchJson(`${base}/week-blocks/${id}`);
            const form = document.getElementById('editWeekBlockForm');

            currentWeekBlockId = data.id;
            form.action = `${base}/week-blocks/${data.id}`;
            fillForm(form, data);

            reopenManagerAfterEdit = openedFromManager;

            if (openedFromManager) {
                const manager = getModal('manageWeekBlocksModal');
                const onHidden = () => {
                    manageModalElement.removeEventListener('hidden.bs.modal', onHidden);
                    getModal('editWeekBlockModal').show();
                };
                manageModalElement.addEventListener('hidden.bs.modal', onHidden);
                manager.hide();
            } else {
                getModal('editWeekBlockModal').show();
            }
        } catch (error) {
            showError(error.message);
        }
    }

    function ensureMethod(form, method) {
        let input = form.querySelector('input[name="_method"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_method';
            form.appendChild(input);
        }
        input.value = method;
    }

    function removeMethod(form) {
        form.querySelector('input[name="_method"]')?.remove();
    }

    function fillForm(form, data) {
        Object.entries(data).forEach(([key, value]) => {
            if (key.endsWith('_ids')) return;
            const element = form.elements[key];
            if (!element) return;

            if (element.type === 'checkbox') {
                element.checked = Boolean(value);
            } else {
                element.value = value ?? '';
            }
        });
    }

    function setMultiSelect(select, values) {
        if (!select) return;
        const wanted = values.map(String);
        [...select.options].forEach(option => {
            option.selected = wanted.includes(String(option.value));
        });
    }

    async function fetchJson(url) {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(extractError(payload, `Request failed with status ${response.status}.`));
        }

        return payload;
    }

    async function sendRequest(url, method) {
        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            throw new Error(extractError(payload, `Request failed with status ${response.status}.`));
        }

        return true;
    }

    function extractError(payload, fallback) {
        if (payload.message) return payload.message;
        if (payload.errors) return Object.values(payload.errors).flat().join(' ');
        return fallback;
    }

    function showError(message) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Unable to continue', text: message });
            return;
        }
        window.alert(message);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function safeColor(value, fallback) {
        return /^#[0-9a-fA-F]{6}$/.test(value || '') ? value : fallback;
    }

    function capitalize(value) {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
    }

    document.querySelectorAll('.js-delete-event-form').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm('Delete this event? This is allowed only while the Almanac is in draft status.')) {
                event.preventDefault();
            }
        });
    });
})();
</script>
