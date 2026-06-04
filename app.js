const API_BASE_URL = window.location.origin;

let funcionariosDataTable = null;
let configuracoesDataTable = null;
let logsOperacaoDataTable = null;
let logsApiDataTable = null;
let cargosChart = null;
let centrosChart = null;
let catalogosCarregados = false;
let funcionariosCache = new Map();

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

async function requestJson(path, options = {}) {
    try {
        const response = await fetch(`${API_BASE_URL}${path}`, {
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                ...(options.headers ?? {})
            },
            ...options
        });

        const payload = await response.json().catch(() => ({}));
        return { response, payload };
    } catch (error) {
        return {
            response: null,
            payload: {
                success: false,
                message: 'Falha de rede ao conectar na API.'
            }
        };
    }
}

function ensurePageMessageBox() {
    let box = document.getElementById('page-message');
    if (box) {
        return box;
    }

    const container = document.querySelector('.main-shell') || document.body;
    box = document.createElement('div');
    box.id = 'page-message';
    box.className = 'alert d-none';
    container.prepend(box);
    return box;
}

function showPageMessage(text, kind = 'danger') {
    const box = ensurePageMessageBox();
    box.textContent = text;
    box.className = `alert alert-${kind}`;
    box.classList.remove('d-none');
}

function hidePageMessage() {
    const box = document.getElementById('page-message');
    if (!box) return;
    box.classList.add('d-none');
}

function resolveApiMessage(payload, fallback) {
    const message = payload?.message ?? '';
    return message.trim() !== '' ? message : fallback;
}

function ensureLogoutShortcut() {
    const existing = document.querySelector('[data-action="logout"]');
    if (existing) {
        return;
    }

    const main = document.querySelector('.main-shell');
    if (!main) {
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-secondary btn-sm ms-auto mb-3';
    button.dataset.action = 'logout';
    button.innerHTML = '<i class="bi bi-box-arrow-right me-2"></i>Sair';

    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex justify-content-end';
    wrapper.appendChild(button);
    main.prepend(wrapper);
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('pt-BR');
}

function formatCurrency(value) {
    const amount = Number(value ?? 0);
    if (Number.isNaN(amount)) {
        return String(value ?? '-');
    }

    return amount.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

async function loadCurrentUser() {
    const userName = document.getElementById('current-user-name');
    const userProfile = document.getElementById('current-user-profile');
    const logoutButtons = document.querySelectorAll('[data-action="logout"]');
    const requiresAuth = document.body.dataset.requiresAuth === '1';

    if (!userName && !userProfile && !logoutButtons.length) {
        return null;
    }

    const { response, payload } = await requestJson('/api/auth/me');
    const user = payload?.data ?? null;

    if (requiresAuth && (!response?.ok || !user)) {
        window.location.href = './login.html';
        return null;
    }

    ensureLogoutShortcut();

    if (userName) {
        userName.textContent = user?.Nome ? `Olá, ${user.Nome}` : 'Visitante';
    }

    if (userProfile) {
        userProfile.textContent = user?.PerfilAcesso ?? 'Sem perfil';
    }

    logoutButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            await requestJson('/api/auth/logout', { method: 'POST' });
            window.location.href = './login.html';
        });
    });

    return user;
}

async function loadDashboard() {
    const summaryNodes = {
        total: document.getElementById('dashboard-total-funcionarios'),
        ativos: document.getElementById('dashboard-funcionarios-ativos'),
        pontos: document.getElementById('dashboard-pontos-hoje'),
        ferias: document.getElementById('dashboard-ferias-pendentes'),
        operacoes: document.getElementById('dashboard-operacoes-semana'),
        api: document.getElementById('dashboard-api-semana')
    };

    if (!summaryNodes.total && !document.getElementById('dashboard-chart-cargos')) {
        return;
    }

    const { response, payload } = await requestJson('/api/dashboard');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar o dashboard.'));
        return;
    }

    const data = payload?.data ?? {};
    const resumo = data ?? {};
    const configuracoes = resumo.Configuracoes ?? {};

    if (summaryNodes.total) summaryNodes.total.textContent = resumo.TotalFuncionarios ?? '0';
    if (summaryNodes.ativos) summaryNodes.ativos.textContent = resumo.FuncionariosAtivos ?? '0';
    if (summaryNodes.pontos) summaryNodes.pontos.textContent = resumo.PontosHoje ?? '0';
    if (summaryNodes.ferias) summaryNodes.ferias.textContent = resumo.FeriasPendentes ?? '0';
    if (summaryNodes.operacoes) summaryNodes.operacoes.textContent = resumo.OperacoesUltimos7Dias ?? '0';
    if (summaryNodes.api) summaryNodes.api.textContent = resumo.ChamadasApiUltimos7Dias ?? '0';

    const companyTitle = document.getElementById('dashboard-company-title');
    const companySubtitle = document.getElementById('dashboard-company-subtitle');

    if (companyTitle) companyTitle.textContent = configuracoes.NomeEmpresa ?? 'Nexus RH';
    if (companySubtitle) companySubtitle.textContent = configuracoes.Slogan ?? 'Gestão inteligente de RH';

    const cargosCanvas = document.getElementById('dashboard-chart-cargos');
    if (cargosCanvas && window.Chart) {
        const labels = (data.PorCargo ?? []).map((item) => item.Cargo ?? '-');
        const values = (data.PorCargo ?? []).map((item) => Number(item.Total ?? 0));

        if (cargosChart) {
            cargosChart.destroy();
        }

        cargosChart = new Chart(cargosCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Funcionários por cargo',
                    data: values,
                    backgroundColor: ['#0057a5', '#0d6efd', '#0dcaf0', '#20c997', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    const centrosCanvas = document.getElementById('dashboard-chart-centros');
    if (centrosCanvas && window.Chart) {
        const labels = (data.PorCentroCusto ?? []).map((item) => item.CentroCusto ?? '-');
        const values = (data.PorCentroCusto ?? []).map((item) => Number(item.Total ?? 0));

        if (centrosChart) {
            centrosChart.destroy();
        }

        centrosChart = new Chart(centrosCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#003f7d', '#0057a5', '#0dcaf0', '#20c997', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    const lastLogs = document.getElementById('dashboard-last-logs');
    if (lastLogs) {
        const logs = (data.operacoes ?? data.UltimosLogs ?? []).slice?.(0, 6) ?? [];
        lastLogs.innerHTML = logs.length
            ? logs.map((item) => `
                <div class="list-group-item bg-transparent border-0 px-0 py-2">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">${escapeHtml(item.TipoOperacao ?? item.Modulo ?? 'Operação')}</div>
                            <small class="text-muted">${escapeHtml(item.Mensagem ?? item.Rota ?? '-')}</small>
                        </div>
                        <small class="text-muted text-end">${formatDate(item.DataOperacao ?? item.CriadoEm ?? item.DataConsumo)}</small>
                    </div>
                </div>
            `).join('')
            : '<div class="text-muted small">Nenhum log recente encontrado.</div>';
    }
}

async function loadFuncionarios() {
    const tableBody = document.getElementById('funcionarios-table-body');
    if (!tableBody) {
        return;
    }

    if (!catalogosCarregados) {
        const catalogosResponse = await requestJson('/api/funcionarios?catalogos=1');
        if (!catalogosResponse.response?.ok || catalogosResponse.payload?.success === false) {
            showPageMessage(resolveApiMessage(catalogosResponse.payload, 'Falha ao carregar catálogos.'));
            return;
        }

        const catalogos = catalogosResponse.payload?.data ?? {};
        const cargosSelect = document.getElementById('cargo-select');
        const centrosSelect = document.getElementById('centro-custo-select');
        const cargosEditSelect = document.getElementById('cargo-edit-select');
        const centrosEditSelect = document.getElementById('centro-custo-edit-select');

        const cargosOptions = '<option value="">Selecione</option>' + (catalogos.cargos ?? []).map((item) => `<option value="${escapeHtml(item.CargoID)}">${escapeHtml(item.Nome ?? '-')}</option>`).join('');
        const centrosOptions = '<option value="">Selecione</option>' + (catalogos.centrosCusto ?? []).map((item) => `<option value="${escapeHtml(item.CentroCustoID)}">${escapeHtml(item.Nome ?? '-')}</option>`).join('');

        if (cargosSelect) {
            cargosSelect.innerHTML = cargosOptions;
        }

        if (centrosSelect) {
            centrosSelect.innerHTML = centrosOptions;
        }

        if (cargosEditSelect) {
            cargosEditSelect.innerHTML = cargosOptions;
        }

        if (centrosEditSelect) {
            centrosEditSelect.innerHTML = centrosOptions;
        }

        catalogosCarregados = true;
    }

    const { response, payload } = await requestJson('/api/funcionarios');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar funcionários.'));
        return;
    }

    const funcionarios = payload.data ?? [];
    funcionariosCache = new Map((funcionarios ?? []).map((item) => [String(item.FuncionarioID), item]));

    tableBody.innerHTML = funcionarios.length
        ? funcionarios.map((item) => `
            <tr>
                <td>${escapeHtml(item.Nome ?? '-')}</td>
                <td>${escapeHtml(item.CargoNome ?? '-')}</td>
                <td>${escapeHtml(item.CentroCustoNome ?? '-')}</td>
                <td>${escapeHtml(item.CPF ?? '-')}</td>
                <td>${escapeHtml(item.Email ?? '-')}</td>
                <td><span class="badge text-bg-${(item.Status ?? '') === 'Ativo' ? 'success' : 'secondary'}">${escapeHtml(item.Status ?? '-')}</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary me-2" data-edit-funcionario="${item.FuncionarioID}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-delete-funcionario="${item.FuncionarioID}"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        `).join('')
        : '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum funcionário cadastrado.</td></tr>';

    if (window.jQuery && window.jQuery.fn?.DataTable) {
        if (funcionariosDataTable) {
            funcionariosDataTable.destroy();
        }

        funcionariosDataTable = window.jQuery('#funcionarios-data-table').DataTable({
            pageLength: 10,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
            }
        });
    }

    document.querySelectorAll('[data-delete-funcionario]').forEach((button) => {
        button.addEventListener('click', async () => {
            const funcionarioId = button.getAttribute('data-delete-funcionario');
            if (!funcionarioId) return;

            if (!window.confirm('Desativar este funcionário?')) {
                return;
            }

            const { response, payload } = await requestJson(`/api/funcionarios/${funcionarioId}`, { method: 'DELETE' });
            if (response?.ok) {
                await loadFuncionarios();
                hidePageMessage();
                return;
            }

            showPageMessage(resolveApiMessage(payload, 'Falha ao desativar funcionário.'));
        });
    });

    document.querySelectorAll('[data-edit-funcionario]').forEach((button) => {
        button.addEventListener('click', () => {
            const funcionarioId = button.getAttribute('data-edit-funcionario');
            if (!funcionarioId) return;
            const funcionario = funcionariosCache.get(String(funcionarioId));
            if (!funcionario) return;
            preencherFormularioFuncionarioModal(funcionario);
        });
    });
}

function setFormularioFuncionarioModo(edicao) {
    const form = document.getElementById('form-funcionario');
    if (!form) return;

    const title = document.getElementById('funcionario-form-title');
    const submitText = document.getElementById('funcionario-submit-text');
    const cancelButton = document.getElementById('funcionario-cancelar');

    if (title) title.textContent = edicao ? 'Editar funcionario' : 'Novo funcionario';
    if (submitText) submitText.textContent = edicao ? 'Salvar alteracoes' : 'Salvar funcionario';
    if (cancelButton) cancelButton.classList.toggle('d-none', !edicao);

    if (form.senha) {
        form.senha.required = !edicao;
        if (edicao) {
            form.senha.value = '';
        }
    }
}

function obterModalFuncionarioEdicao() {
    const modalElement = document.getElementById('funcionario-edicao-modal');
    if (!modalElement || !window.bootstrap?.Modal) {
        return null;
    }

    return window.bootstrap.Modal.getOrCreateInstance(modalElement);
}

function resetFormularioFuncionarioEdicao() {
    const form = document.getElementById('form-funcionario-edicao');
    if (!form) return;
    form.reset();

    const funcionarioIdField = form.querySelector('[name="funcionarioId"]');
    if (funcionarioIdField) funcionarioIdField.value = '';

    const feedback = document.getElementById('funcionario-edicao-feedback');
    if (feedback) {
        feedback.classList.add('d-none');
        feedback.textContent = '';
    }
}

function preencherFormularioFuncionarioModal(funcionario) {
    const form = document.getElementById('form-funcionario-edicao');
    if (!form) return;

    const funcionarioIdField = form.querySelector('[name="funcionarioId"]');
    if (funcionarioIdField) funcionarioIdField.value = funcionario.FuncionarioID ?? '';

    form.nome.value = funcionario.Nome ?? '';
    form.cpf.value = funcionario.CPF ?? '';
    form.email.value = funcionario.Email ?? '';
    form.senha.value = '';
    form.perfilAcesso.value = funcionario.PerfilAcesso ?? 'Usuario';
    form.cargoId.value = funcionario.CargoID ?? '';
    form.centroCustoId.value = funcionario.CentroCustoID ?? '';
    form.supervisorId.value = funcionario.SupervisorID ?? '';
    form.salarioAtual.value = funcionario.SalarioAtual ?? '';
    form.dataAdmissao.value = funcionario.DataAdmissao ?? '';
    form.status.value = funcionario.Status ?? 'Ativo';

    const feedback = document.getElementById('funcionario-edicao-feedback');
    if (feedback) {
        feedback.classList.add('d-none');
        feedback.textContent = '';
    }

    const modal = obterModalFuncionarioEdicao();
    if (modal) {
        modal.show();
    }
}

function preencherFormularioFuncionario(funcionario) {
    const form = document.getElementById('form-funcionario');
    if (!form) return;

    const funcionarioIdField = document.getElementById('funcionario-id');
    if (funcionarioIdField) funcionarioIdField.value = funcionario.FuncionarioID ?? '';

    form.nome.value = funcionario.Nome ?? '';
    form.cpf.value = funcionario.CPF ?? '';
    form.email.value = funcionario.Email ?? '';
    form.perfilAcesso.value = funcionario.PerfilAcesso ?? 'Usuario';
    form.cargoId.value = funcionario.CargoID ?? '';
    form.centroCustoId.value = funcionario.CentroCustoID ?? '';
    form.supervisorId.value = funcionario.SupervisorID ?? '';
    form.salarioAtual.value = funcionario.SalarioAtual ?? '';
    form.dataAdmissao.value = funcionario.DataAdmissao ?? '';
    form.status.value = funcionario.Status ?? 'Ativo';

    setFormularioFuncionarioModo(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetFormularioFuncionario() {
    const form = document.getElementById('form-funcionario');
    if (!form) return;

    form.reset();
    const funcionarioIdField = document.getElementById('funcionario-id');
    if (funcionarioIdField) funcionarioIdField.value = '';
    setFormularioFuncionarioModo(false);
}

async function handleFuncionarioFormSubmit(event) {
    const form = event.target;
    if (!form || (form.id !== 'form-funcionario' && form.id !== 'form-funcionario-edicao')) {
        return;
    }

    event.preventDefault();

    const funcionarioId = form.querySelector('[name="funcionarioId"]')?.value;
    const editando = funcionarioId && String(funcionarioId).trim() !== '';
    const senhaValue = form.senha.value;

    const payload = {
        ...(editando ? { funcionarioId: Number(funcionarioId) } : {}),
        nome: form.nome.value,
        cpf: form.cpf.value,
        email: form.email.value,
        ...(senhaValue ? { senha: senhaValue } : {}),
        perfilAcesso: form.perfilAcesso.value,
        cargoId: Number(form.cargoId.value),
        centroCustoId: Number(form.centroCustoId.value),
        supervisorId: form.supervisorId.value ? Number(form.supervisorId.value) : null,
        salarioAtual: form.salarioAtual.value,
        dataAdmissao: form.dataAdmissao.value,
        status: form.status.value
    };

    const { response, payload: result } = await requestJson(editando ? `/api/funcionarios/${encodeURIComponent(funcionarioId)}` : '/api/funcionarios', {
        method: editando ? 'PUT' : 'POST',
        body: JSON.stringify(payload)
    });

    const feedback = form.id === 'form-funcionario-edicao'
        ? document.getElementById('funcionario-edicao-feedback')
        : document.getElementById('funcionario-feedback');
    if (feedback) {
        feedback.textContent = resolveApiMessage(
            result,
            response?.ok
                ? (editando ? 'Funcionario atualizado com sucesso.' : 'Funcionario criado com sucesso.')
                : (editando ? 'Falha ao atualizar funcionario.' : 'Falha ao criar funcionario.')
        );
        feedback.className = `alert ${response?.ok ? 'alert-success' : 'alert-danger'}`;
        feedback.classList.remove('d-none');
    }

    if (response?.ok) {
        if (form.id === 'form-funcionario-edicao') {
            const modal = obterModalFuncionarioEdicao();
            if (modal) {
                modal.hide();
            }
            resetFormularioFuncionarioEdicao();
        } else {
            resetFormularioFuncionario();
        }
        await loadFuncionarios();
        hidePageMessage();
    } else {
        showPageMessage(resolveApiMessage(result, editando ? 'Falha ao atualizar funcionario.' : 'Falha ao criar funcionario.'));
    }
}

async function loadConfiguracoes() {
    const tableBody = document.getElementById('configuracoes-table-body');
    if (!tableBody) {
        return;
    }

    const { response, payload } = await requestJson('/api/configuracoes');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar configurações.'));
        return;
    }

    const configuracoes = payload.data ?? [];

    tableBody.innerHTML = configuracoes.length
        ? configuracoes.map((item) => `
            <tr>
                <td>${escapeHtml(item.Categoria ?? '-')}</td>
                <td><code>${escapeHtml(item.Chave ?? '-')}</code></td>
                <td>${escapeHtml(item.Valor ?? '-')}</td>
                <td>${escapeHtml(item.TipoCampo ?? '-')}</td>
                <td>${item.Editavel ? 'Sim' : 'Não'}</td>
                <td>${item.Ativo ? 'Sim' : 'Não'}</td>
            </tr>
        `).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-4">Nenhuma configuração encontrada.</td></tr>';

    if (window.jQuery && window.jQuery.fn?.DataTable) {
        if (configuracoesDataTable) {
            configuracoesDataTable.destroy();
        }

        configuracoesDataTable = window.jQuery('#configuracoes-data-table').DataTable({
            pageLength: 10,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
        });
    }
}

async function handleConfiguracaoFormSubmit(event) {
    const form = event.target;
    if (!form || form.id !== 'form-configuracao') {
        return;
    }

    event.preventDefault();

    const payload = {
        chave: form.chave.value,
        valor: form.valor.value,
        categoria: form.categoria.value,
        descricao: form.descricao.value,
        tipoCampo: form.tipoCampo.value,
        editavel: form.editavel.checked,
        ativo: form.ativo.checked
    };

    const { response, payload: result } = await requestJson('/api/configuracoes', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    const feedback = document.getElementById('configuracao-feedback');
    if (feedback) {
        feedback.textContent = resolveApiMessage(result, response?.ok ? 'Configuração salva com sucesso.' : 'Falha ao salvar configuração.');
        feedback.className = `alert ${response?.ok ? 'alert-success' : 'alert-danger'}`;
        feedback.classList.remove('d-none');
    }

    if (response?.ok) {
        form.reset();
        await loadConfiguracoes();
        hidePageMessage();
    } else {
        showPageMessage(resolveApiMessage(result, 'Falha ao salvar configuração.'));
    }
}

async function loadLogs() {
    const operacaoBody = document.getElementById('logs-operacao-table-body');
    const apiBody = document.getElementById('logs-api-table-body');

    if (!operacaoBody && !apiBody) {
        return;
    }

    const { response, payload } = await requestJson('/api/logs');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar logs.'));
        return;
    }

    const data = payload.data ?? {};

    const operacoes = data.operacoes ?? [];
    const apiLogs = data.api ?? [];

    if (operacaoBody) {
        operacaoBody.innerHTML = operacoes.length
            ? operacoes.map((item) => `
                <tr>
                    <td>${escapeHtml(item.TipoOperacao ?? '-')}</td>
                    <td>${escapeHtml(item.Entidade ?? '-')}</td>
                    <td>${escapeHtml(item.Mensagem ?? '-')}</td>
                    <td>${escapeHtml(item.FuncionarioNome ?? '-')}</td>
                    <td>${item.Sucesso ? '<span class="badge text-bg-success">Sim</span>' : '<span class="badge text-bg-danger">Não</span>'}</td>
                    <td>${formatDate(item.DataOperacao)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum log de operação encontrado.</td></tr>';
    }

    if (apiBody) {
        apiBody.innerHTML = apiLogs.length
            ? apiLogs.map((item) => `
                <tr>
                    <td>${escapeHtml(item.Endpoint ?? '-')}</td>
                    <td>${escapeHtml(item.Metodo ?? '-')}</td>
                    <td>${escapeHtml(String(item.StatusHttp ?? '-'))}</td>
                    <td>${escapeHtml(item.FuncionarioNome ?? '-')}</td>
                    <td>${escapeHtml(String(item.TempoRespostaMs ?? '-'))}</td>
                    <td>${formatDate(item.DataConsumo)}</td>
                </tr>
            `).join('')
            : '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum consumo de API encontrado.</td></tr>';
    }

    if (window.jQuery && window.jQuery.fn?.DataTable) {
        if (logsOperacaoDataTable) logsOperacaoDataTable.destroy();
        if (logsApiDataTable) logsApiDataTable.destroy();

        logsOperacaoDataTable = window.jQuery('#logs-operacao-data-table').DataTable({
            pageLength: 10,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
        });

        logsApiDataTable = window.jQuery('#logs-api-data-table').DataTable({
            pageLength: 10,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json' }
        });
    }
}

async function loadMapa() {
    const iframe = document.getElementById('mapa-iframe');
    const title = document.getElementById('mapa-titulo');
    const info = document.getElementById('mapa-info');

    if (!iframe && !info && !title) {
        return;
    }

    const { response, payload } = await requestJson('/api/mapa');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar dados do mapa.'));
        return;
    }

    const mapa = payload?.data ?? {};

    if (title) {
        title.textContent = mapa.titulo ?? 'Nexus RH';
    }

    if (info) {
        info.textContent = `Lat ${mapa.latitude ?? '-'} | Lng ${mapa.longitude ?? '-'} | Zoom ${mapa.zoom ?? '-'}`;
    }

    if (iframe) {
        const latitude = Number(mapa.latitude ?? -23.5629);
        const longitude = Number(mapa.longitude ?? -46.6560);
        const zoom = Number(mapa.zoom ?? 15);
        const delta = 0.0085 / Math.max(zoom, 1);
        const bbox = `${longitude - delta},${latitude - delta},${longitude + delta},${latitude + delta}`;
        iframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${latitude}%2C${longitude}`;
    }

    const mapCanvas = document.getElementById('leaflet-map');
    if (mapCanvas && window.L) {
        const latitude = Number(mapa.latitude ?? -23.5629);
        const longitude = Number(mapa.longitude ?? -46.6560);
        const zoom = Number(mapa.zoom ?? 15);

        const leafletMap = L.map('leaflet-map').setView([latitude, longitude], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(leafletMap);

        L.marker([latitude, longitude]).addTo(leafletMap).bindPopup(mapa.titulo ?? 'Nexus RH').openPopup();
    }
}

async function handleRegistrarPonto() {
    const feedback = document.getElementById('ponto-feedback');
    const { response, payload } = await requestJson('/api/ponto/registrar', { method: 'POST' });

    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao registrar ponto.'));
        return;
    }

    const data = payload?.data ?? {};
    if (feedback) {
        const tipo = data.TipoBatida ?? 'Batida';
        const horario = data.DataHoraRegistro ?? '-';
        feedback.textContent = `${tipo} registrada as ${horario}.`;
    }

    hidePageMessage();
}

function formatDateInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function resolveEspelhoPeriodo() {
    const inicioInput = document.getElementById('espelho-data-inicio');
    const fimInput = document.getElementById('espelho-data-fim');
    if (!inicioInput || !fimInput) {
        return null;
    }

    let inicio = inicioInput.value;
    let fim = fimInput.value;

    if (!inicio || !fim) {
        const hoje = new Date();
        const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

        if (!inicio) {
            inicio = formatDateInput(primeiroDia);
            inicioInput.value = inicio;
        }

        if (!fim) {
            fim = formatDateInput(hoje);
            fimInput.value = fim;
        }
    }

    return { inicio, fim };
}

async function loadEspelhoPonto() {
    const container = document.getElementById('espelho-resultado');
    if (!container) {
        return;
    }

    const periodo = resolveEspelhoPeriodo();
    if (!periodo) {
        return;
    }

    const { response, payload } = await requestJson(`/api/ponto/espelho?inicio=${encodeURIComponent(periodo.inicio)}&fim=${encodeURIComponent(periodo.fim)}`);
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar espelho de ponto.'));
        return;
    }

    const registros = payload?.data ?? [];
    if (!registros.length) {
        container.innerHTML = '<div class="text-muted">Nenhum registro encontrado no periodo informado.</div>';
        return;
    }

    container.innerHTML = `
        <table class="table table-sm table-striped align-middle">
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Tipo</th>
                    <th>Origem</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${registros.map((item) => `
                    <tr>
                        <td>${escapeHtml(formatDate(item.DataHoraRegistro))}</td>
                        <td>${escapeHtml(item.TipoBatida ?? '-')}</td>
                        <td>${escapeHtml(item.Origem ?? '-')}</td>
                        <td>${escapeHtml(item.StatusAprovacao ?? '-')}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

function handleEspelhoDownload() {
    const periodo = resolveEspelhoPeriodo();
    if (!periodo) {
        return;
    }

    window.location.href = `/api/ponto/espelho/pdf?inicio=${encodeURIComponent(periodo.inicio)}&fim=${encodeURIComponent(periodo.fim)}`;
}

async function carregarFuncionariosHolerite() {
    const select = document.getElementById('holerite-funcionario');
    if (!select) {
        return;
    }

    const { response, payload } = await requestJson('/api/funcionarios');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar funcionarios.'));
        return;
    }

    const funcionarios = payload?.data ?? [];
    select.innerHTML = '<option value="">Selecione</option>' + funcionarios.map((item) => (
        `<option value="${escapeHtml(item.FuncionarioID)}">${escapeHtml(item.Nome ?? '-')} (${escapeHtml(item.CPF ?? '-')})</option>`
    )).join('');
}

async function setupHoleriteAdmin(user) {
    const card = document.getElementById('holerite-admin-card');
    if (!card) {
        return;
    }

    if ((user?.PerfilAcesso ?? '') !== 'Administrador') {
        card.classList.add('d-none');
        return;
    }

    card.classList.remove('d-none');
    await carregarFuncionariosHolerite();
}

async function handleHoleriteAdminSubmit(event) {
    const form = event.target;
    if (!form || form.id !== 'holerite-admin-form') {
        return;
    }

    event.preventDefault();

    const payload = {
        funcionarioId: Number(document.getElementById('holerite-funcionario')?.value || 0),
        mes: Number(document.getElementById('holerite-mes')?.value || 0),
        ano: Number(document.getElementById('holerite-ano')?.value || 0),
        diasTrabalhados: Number(document.getElementById('holerite-dias')?.value || 0)
    };

    const { response, payload: result } = await requestJson('/api/holerites', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    const feedback = document.getElementById('holerite-admin-feedback');
    if (feedback) {
        feedback.textContent = resolveApiMessage(result, response?.ok ? 'Holerite gerado com sucesso.' : 'Falha ao gerar holerite.');
        feedback.className = `alert ${response?.ok ? 'alert-success' : 'alert-danger'}`;
        feedback.classList.remove('d-none');
    }

    if (response?.ok) {
        form.reset();
        await loadHolerites();
        hidePageMessage();
    } else {
        showPageMessage(resolveApiMessage(result, 'Falha ao gerar holerite.'));
    }
}

async function loadHolerites() {
    const list = document.getElementById('holerites-list');
    if (!list) {
        return;
    }

    const { response, payload } = await requestJson('/api/holerites');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar holerites.'));
        return;
    }

    const holerites = payload?.data ?? [];
    list.innerHTML = holerites.length
        ? holerites.map((item) => {
            const mes = String(item.MesReferencia ?? '').padStart(2, '0');
            const ano = item.AnoReferencia ?? '';
            return `
                <div class="col">
                    <div class="card panel-card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5">${escapeHtml(mes)}/${escapeHtml(ano)}</h2>
                            <p class="text-muted">Referência: ${escapeHtml(mes)}/${escapeHtml(ano)}</p>
                            <p class="mb-4">Valor líquido: <strong>${escapeHtml(formatCurrency(item.ValorLiquido))}</strong></p>
                            <a href="/api/holerites/${encodeURIComponent(item.FolhaID)}/pdf" class="btn btn-primary mt-auto"><i class="bi bi-file-earmark-pdf me-2"></i>Baixar PDF</a>
                        </div>
                    </div>
                </div>
            `;
        }).join('')
        : '<div class="col"><div class="text-muted">Nenhum holerite encontrado.</div></div>';
}

async function initializePage() {
    const currentUser = await loadCurrentUser();

    document.getElementById('form-funcionario')?.addEventListener('submit', handleFuncionarioFormSubmit);
    document.getElementById('form-funcionario-edicao')?.addEventListener('submit', handleFuncionarioFormSubmit);
    document.getElementById('funcionario-cancelar')?.addEventListener('click', resetFormularioFuncionario);
    document.getElementById('form-configuracao')?.addEventListener('submit', handleConfiguracaoFormSubmit);
    document.getElementById('registrar-ponto')?.addEventListener('click', handleRegistrarPonto);
    document.getElementById('holerite-admin-form')?.addEventListener('submit', handleHoleriteAdminSubmit);
    document.getElementById('espelho-download')?.addEventListener('click', handleEspelhoDownload);
    document.getElementById('espelho-data-inicio')?.addEventListener('change', loadEspelhoPonto);
    document.getElementById('espelho-data-fim')?.addEventListener('change', loadEspelhoPonto);

    if (currentUser) {
        await setupHoleriteAdmin(currentUser);
    }

    if (document.getElementById('form-funcionario')) {
        setFormularioFuncionarioModo(false);
    }

    const funcionarioModalElement = document.getElementById('funcionario-edicao-modal');
    if (funcionarioModalElement) {
        funcionarioModalElement.addEventListener('hidden.bs.modal', resetFormularioFuncionarioEdicao);
    }

    await Promise.all([
        loadDashboard(),
        loadFuncionarios(),
        loadConfiguracoes(),
        loadLogs(),
        loadMapa(),
        loadHolerites(),
        loadEspelhoPonto()
    ]);
}

document.addEventListener('DOMContentLoaded', initializePage);
