const API_BASE_URL = window.location.origin;

let funcionariosDataTable = null;
let configuracoesDataTable = null;
let logsOperacaoDataTable = null;
let logsApiDataTable = null;
let cargosChart = null;
let centrosChart = null;
let catalogosCarregados = false;

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

        if (cargosSelect) {
            cargosSelect.innerHTML = '<option value="">Selecione</option>' + (catalogos.cargos ?? []).map((item) => `<option value="${escapeHtml(item.CargoID)}">${escapeHtml(item.Nome ?? '-')}</option>`).join('');
        }

        if (centrosSelect) {
            centrosSelect.innerHTML = '<option value="">Selecione</option>' + (catalogos.centrosCusto ?? []).map((item) => `<option value="${escapeHtml(item.CentroCustoID)}">${escapeHtml(item.Nome ?? '-')}</option>`).join('');
        }

        catalogosCarregados = true;
    }

    const { response, payload } = await requestJson('/api/funcionarios');
    if (!response?.ok || payload?.success === false) {
        showPageMessage(resolveApiMessage(payload, 'Falha ao carregar funcionários.'));
        return;
    }

    const funcionarios = payload.data ?? [];

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
}

async function handleFuncionarioFormSubmit(event) {
    const form = event.target;
    if (!form || form.id !== 'form-funcionario') {
        return;
    }

    event.preventDefault();

    const payload = {
        nome: form.nome.value,
        cpf: form.cpf.value,
        email: form.email.value,
        senha: form.senha.value,
        perfilAcesso: form.perfilAcesso.value,
        cargoId: Number(form.cargoId.value),
        centroCustoId: Number(form.centroCustoId.value),
        supervisorId: form.supervisorId.value ? Number(form.supervisorId.value) : null,
        salarioAtual: form.salarioAtual.value,
        dataAdmissao: form.dataAdmissao.value,
        status: form.status.value
    };

    const { response, payload: result } = await requestJson('/api/funcionarios', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    const feedback = document.getElementById('funcionario-feedback');
    if (feedback) {
        feedback.textContent = resolveApiMessage(result, response?.ok ? 'Funcionário criado com sucesso.' : 'Falha ao criar funcionário.');
        feedback.className = `alert ${response?.ok ? 'alert-success' : 'alert-danger'}`;
        feedback.classList.remove('d-none');
    }

    if (response?.ok) {
        form.reset();
        await loadFuncionarios();
        hidePageMessage();
    } else {
        showPageMessage(resolveApiMessage(result, 'Falha ao criar funcionário.'));
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

async function initializePage() {
    await loadCurrentUser();

    document.getElementById('form-funcionario')?.addEventListener('submit', handleFuncionarioFormSubmit);
    document.getElementById('form-configuracao')?.addEventListener('submit', handleConfiguracaoFormSubmit);

    await Promise.all([
        loadDashboard(),
        loadFuncionarios(),
        loadConfiguracoes(),
        loadLogs(),
        loadMapa()
    ]);
}

document.addEventListener('DOMContentLoaded', initializePage);
