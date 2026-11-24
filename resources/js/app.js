import './bootstrap';

const api = window.axios;
const views = {
    clientes: {
        title: 'Clientes',
        description: 'Información de las personas y sus vehículos.',
        endpoint: '/api/clientes',
        columns: ['Cédula', 'Dirección', 'Teléfono', 'Vehículos'],
    },
    coches: {
        title: 'Vehículos',
        description: 'Inventario de coches y sus clientes.',
        endpoint: '/api/coches',
        columns: ['Vehículo', 'Color', 'Precio', 'Cliente', 'Revisiones'],
    },
    revisiones: {
        title: 'Revisiones',
        description: 'Historial técnico y servicios realizados.',
        endpoint: '/api/revisiones',
        columns: ['Código', 'Vehículo', 'Aceite', 'Filtro', 'Frenos'],
    },
};

const state = { view: 'clientes', clientes: [], coches: [], revisiones: [], filters: {} };
const $ = (selector) => document.querySelector(selector);
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
}[character]));

function showNotice(message, isError = false) {
    const notice = $('#notice');
    notice.textContent = message;
    notice.classList.toggle('error', isError);
    notice.hidden = false;
    window.clearTimeout(showNotice.timeout);
    showNotice.timeout = window.setTimeout(() => { notice.hidden = true; }, 4500);
}

function errorMessage(error) {
    const errors = error.response?.data?.errors;
    if (errors) return Object.values(errors).flat().join(' ');
    return error.response?.data?.message || 'No se pudo completar la solicitud. Inténtalo de nuevo.';
}

function field(label, name, type = 'text', value = '', extra = '') {
    return `<label class="field">${label}<input name="${name}" type="${type}" value="${escapeHtml(value)}" ${extra}></label>`;
}

function selectField(label, name, items, selected, textForItem) {
    const options = items.map((item) => `<option value="${item.id}" ${String(item.id) === String(selected) ? 'selected' : ''}>${escapeHtml(textForItem(item))}</option>`).join('');
    return `<label class="field">${label}<select name="${name}" required><option value="">Selecciona una opción</option>${options}</select></label>`;
}

function renderFilters() {
    const filters = $('#filters');
    const view = state.view;
    if (view === 'clientes') {
        filters.hidden = true;
        filters.innerHTML = '';
        return;
    }

    filters.hidden = false;
    if (view === 'coches') {
        filters.innerHTML = `${selectField('Filtrar por cliente', 'cliente_id', state.clientes, state.filters.cliente_id, (cliente) => cliente.cedula)}<label class="filter-check"><input type="checkbox" name="aceite" ${state.filters.aceite ? 'checked' : ''}> Con aceite</label><label class="filter-check"><input type="checkbox" name="filtro" ${state.filters.filtro ? 'checked' : ''}> Con filtro</label><label class="filter-check"><input type="checkbox" name="frenos" ${state.filters.frenos ? 'checked' : ''}> Con frenos</label>`;
    } else {
        filters.innerHTML = selectField('Filtrar por vehículo', 'coche_id', state.coches, state.filters.coche_id, (coche) => `${coche.marca} ${coche.modelo}`);
    }
}

function filteredEndpoint() {
    const params = new URLSearchParams();
    Object.entries(state.filters).forEach(([key, value]) => {
        if (value !== '' && value !== false && value != null) params.set(key, value);
    });
    const query = params.toString();
    return `${views[state.view].endpoint}${query ? `?${query}` : ''}`;
}

function renderRows() {
    const data = state[state.view];
    const rows = state.view === 'clientes'
        ? data.map((item) => [item.cedula, item.direccion, item.telefono].map(escapeHtml).concat(item.coches_count))
        : state.view === 'coches'
            ? data.map((item) => [escapeHtml(`${item.marca} ${item.modelo}`), escapeHtml(item.color), `$${Number(item.precio).toLocaleString('es-CO')}`, escapeHtml(item.cliente?.cedula ?? '—'), item.revisiones?.length ?? 0])
            : data.map((item) => [`<strong>${escapeHtml(item.codigo)}</strong>`, `${escapeHtml(item.coche?.marca)} ${escapeHtml(item.coche?.modelo)} · ${escapeHtml(item.coche?.cliente?.cedula)}`, badge(item.aceite), badge(item.filtro), badge(item.frenos)]);

    $('#table-head').innerHTML = `<tr>${views[state.view].columns.map((column) => `<th>${column}</th>`).join('')}<th></th></tr>`;
    $('#table-body').innerHTML = data.map((item, index) => {
        const cells = rows[index].map((value) => `<td>${typeof value === 'number' ? value : value}</td>`).join('');
        return `<tr>${cells}<td><div class="table-actions"><button class="action-button" type="button" data-edit="${item.id}">Editar</button><button class="action-button danger" type="button" data-delete="${item.id}">Eliminar</button></div></td></tr>`;
    }).join('');
    $('#empty-state').hidden = data.length > 0;
    $('#record-count').textContent = `${data.length} ${data.length === 1 ? 'registro' : 'registros'}`;
}

function badge(value) {
    return `<span class="badge ${value ? '' : 'inactive'}">${value ? 'Realizado' : 'Pendiente'}</span>`;
}

function render() {
    const view = views[state.view];
    $('#section-title').textContent = view.title;
    $('#section-description').textContent = view.description;
    $('#create-record').innerHTML = `<span aria-hidden="true">＋</span> Nuevo ${state.view === 'clientes' ? 'cliente' : state.view === 'coches' ? 'vehículo' : 'revisión'}`;
    document.querySelectorAll('.tab').forEach((tab) => tab.classList.toggle('is-active', tab.dataset.view === state.view));
    renderFilters();
    renderRows();
    $('#count-clientes').textContent = state.clientes.length;
    $('#count-coches').textContent = state.coches.length;
    $('#count-revisiones').textContent = state.revisiones.length;
}

async function loadData() {
    const [clientes, coches, revisiones] = await Promise.all([
        api.get('/api/clientes'), api.get('/api/coches'), api.get('/api/revisiones'),
    ]);
    state.clientes = clientes.data;
    state.coches = coches.data;
    state.revisiones = revisiones.data;
    const current = await api.get(filteredEndpoint());
    state[state.view] = current.data;
    render();
}

function formFields(record = {}) {
    if (state.view === 'clientes') {
        return field('Cédula', 'cedula', 'text', record.cedula ?? '', 'maxlength="20" required')
            + field('Dirección', 'direccion', 'text', record.direccion ?? '', 'maxlength="150" required')
            + field('Teléfono', 'telefono', 'tel', record.telefono ?? '', 'maxlength="20" required');
    }
    if (state.view === 'coches') {
        return field('Marca', 'marca', 'text', record.marca ?? '', 'maxlength="50" required')
            + field('Modelo', 'modelo', 'text', record.modelo ?? '', 'maxlength="50" required')
            + field('Color', 'color', 'text', record.color ?? '', 'maxlength="50" required')
            + field('Precio', 'precio', 'number', record.precio ?? '', 'min="0" step="0.01" required')
            + '<label class="field">Foto (JPG o PNG, máximo 5 MB)<input name="foto" type="file" accept="image/jpeg,image/png"></label>'
            + (record.foto ? '<span class="field-hint">Elige una imagen solo si deseas reemplazar la actual.</span>' : '')
            + selectField('Cliente', 'cliente_id', state.clientes, record.cliente_id, (cliente) => cliente.cedula);
    }

    return field('Código de revisión', 'codigo', 'text', record.codigo ?? '', 'maxlength="20" required')
        + selectField('Vehículo', 'coche_id', state.coches, record.coche_id, (coche) => `${coche.marca} ${coche.modelo}`)
        + ['aceite', 'filtro', 'frenos'].map((name) => `<label class="field check-field"><input type="checkbox" name="${name}" ${record[name] ? 'checked' : ''}> ${name[0].toUpperCase()}${name.slice(1)} realizado</label>`).join('');
}

function openDialog(record = null) {
    const form = $('#record-form');
    form.dataset.recordId = record?.id ?? '';
    $('#dialog-eyebrow').textContent = record ? `EDITAR ${state.view.toUpperCase()}` : `NUEVO ${state.view.toUpperCase()}`;
    $('#dialog-title').textContent = record ? `Editar ${state.view === 'clientes' ? 'cliente' : state.view === 'coches' ? 'vehículo' : 'revisión'}` : `Nuevo ${state.view === 'clientes' ? 'cliente' : state.view === 'coches' ? 'vehículo' : 'revisión'}`;
    $('#form-fields').innerHTML = formFields(record ?? {});
    $('#form-error').hidden = true;
    $('#record-dialog').showModal();
}

function formPayload(form) {
    const data = new FormData(form);
    if (state.view === 'revisiones') {
        return {
            codigo: data.get('codigo'),
            coche_id: Number(data.get('coche_id')),
            aceite: form.elements.aceite.checked,
            filtro: form.elements.filtro.checked,
            frenos: form.elements.frenos.checked,
        };
    }
    if (state.view === 'coches') {
        const result = new FormData(form);
        if (!result.get('foto')?.size) result.delete('foto');
        return result;
    }
    return Object.fromEntries(data.entries());
}

document.querySelectorAll('.tab').forEach((tab) => tab.addEventListener('click', async () => {
    state.view = tab.dataset.view;
    state.filters = {};
    try { await loadData(); } catch (error) { showNotice(errorMessage(error), true); }
}));

$('#filters').addEventListener('change', async (event) => {
    if (!event.target.name) return;
    state.filters[event.target.name] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
    try {
        const response = await api.get(filteredEndpoint());
        state[state.view] = response.data;
        render();
    } catch (error) { showNotice(errorMessage(error), true); }
});

$('#create-record').addEventListener('click', () => openDialog());
$('#close-dialog').addEventListener('click', () => $('#record-dialog').close());
$('#cancel-dialog').addEventListener('click', () => $('#record-dialog').close());

$('#record-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = $('#save-record');
    button.disabled = true;
    try {
        const id = form.dataset.recordId;
        const endpoint = `${views[state.view].endpoint}${id ? `/${id}` : ''}`;
        const payload = formPayload(form);
        if (state.view === 'coches') {
            if (id) payload.append('_method', 'PUT');
            await api.post(endpoint, payload);
        } else if (id) await api.put(endpoint, payload);
        else await api.post(endpoint, payload);
        $('#record-dialog').close();
        await loadData();
        showNotice(id ? 'Registro actualizado correctamente.' : 'Registro creado correctamente.');
    } catch (error) {
        const message = errorMessage(error);
        $('#form-error').textContent = message;
        $('#form-error').hidden = false;
    } finally { button.disabled = false; }
});

$('#table-body').addEventListener('click', async (event) => {
    const edit = event.target.closest('[data-edit]');
    const remove = event.target.closest('[data-delete]');
    if (edit) {
        const record = state[state.view].find((item) => String(item.id) === edit.dataset.edit);
        if (record) openDialog(record);
    }
    if (remove) {
        if (!window.confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
        try {
            await api.delete(`${views[state.view].endpoint}/${remove.dataset.delete}`);
            await loadData();
            showNotice('Registro eliminado correctamente.');
        } catch (error) { showNotice(errorMessage(error), true); }
    }
});

loadData().catch((error) => showNotice(errorMessage(error), true));
