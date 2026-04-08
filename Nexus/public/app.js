const API_BASE_URL = "http://localhost";

async function buscarFuncionarioPorId(funcionarioId = 1) {
    const endpoint = `${API_BASE_URL}/api/funcionarios/${funcionarioId}`;
    const apiStatus = document.getElementById("api-status");
    const employeeName = document.getElementById("employee-name");

    try {
        apiStatus.textContent = `Consultando ${endpoint}...`;

        const response = await fetch(endpoint, {
            method: "GET",
            headers: {
                "Content-Type": "application/json"
            }
        });

        const payload = await response.json();
        console.log("Resposta da API Nexus RH:", payload);

        if (!response.ok || !payload.success) {
            apiStatus.textContent = `Falha na consulta (${response.status}).`;
            return;
        }

        const nome = payload.data?.Nome || "Funcionario sem nome";
        employeeName.textContent = nome;
        apiStatus.textContent = `Consulta concluida com sucesso (HTTP ${response.status}).`;
    } catch (error) {
        console.error("Erro ao consultar API:", error);
        apiStatus.textContent = "Erro de comunicacao com a API.";
    }
}

function renderizarTabelaFuncionarios(funcionarios = []) {
    const tableBody = document.getElementById("employees-table-body");

    if (!tableBody) {
        return;
    }

    if (!Array.isArray(funcionarios) || funcionarios.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6">Nenhum funcionario encontrado.</td></tr>';
        return;
    }

    tableBody.innerHTML = funcionarios.map((funcionario) => `
        <tr>
            <td>${funcionario.FuncionarioID ?? "-"}</td>
            <td>${funcionario.Nome ?? "-"}</td>
            <td>${funcionario.CPF ?? "-"}</td>
            <td>${funcionario.CargoNome ?? "-"}</td>
            <td>${funcionario.CentroCustoNome ?? "-"}</td>
            <td>${funcionario.Status ?? "-"}</td>
        </tr>
    `).join("");
}

async function listarFuncionarios() {
    const endpoint = `${API_BASE_URL}/api/funcionarios`;
    const tableStatus = document.getElementById("table-status");

    try {
        if (tableStatus) {
            tableStatus.textContent = "Carregando funcionarios...";
        }

        const response = await fetch(endpoint, {
            method: "GET",
            headers: {
                "Content-Type": "application/json"
            }
        });

        const payload = await response.json();
        console.log("Listagem geral de funcionarios:", payload);

        if (!response.ok || !payload.success) {
            renderizarTabelaFuncionarios([]);
            if (tableStatus) {
                tableStatus.textContent = `Falha na listagem (HTTP ${response.status}).`;
            }
            return;
        }

        renderizarTabelaFuncionarios(payload.data ?? []);
        if (tableStatus) {
            tableStatus.textContent = `${(payload.data ?? []).length} funcionario(s) carregado(s).`;
        }
    } catch (error) {
        console.error("Erro ao listar funcionarios:", error);
        renderizarTabelaFuncionarios([]);
        if (tableStatus) {
            tableStatus.textContent = "Erro de comunicacao com a API.";
        }
    }
}

document.getElementById("btn-carregar")?.addEventListener("click", () => {
    buscarFuncionarioPorId(1);
    listarFuncionarios();
});

buscarFuncionarioPorId(1);
listarFuncionarios();
