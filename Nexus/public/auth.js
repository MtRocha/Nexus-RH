const API_BASE_URL = window.location.origin;

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

function setMessage(text, kind = 'info') {
    const box = document.getElementById('login-message');
    if (!box) return;

    box.textContent = text;
    box.className = `alert alert-${kind}`;
    box.classList.remove('d-none');
}

function showPanel(panelId) {
    const panels = ['login-step-one', 'login-step-two', 'login-reset'];
    panels.forEach((id) => {
        const panel = document.getElementById(id);
        if (!panel) return;
        panel.classList.toggle('d-none', id !== panelId);
    });
}

async function handleLogin(event) {
    event.preventDefault();

    const login = document.getElementById('login')?.value ?? '';
    const senha = document.getElementById('senha')?.value ?? '';

    const { response, payload } = await requestJson('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ login, senha })
    });

    if (!response?.ok || !payload.success) {
        setMessage(payload.message ?? 'Falha no login.', 'danger');
        return;
    }

    if (payload.data?.mfaRequired) {
        showPanel('login-step-two');
        document.getElementById('challengeToken').value = payload.data.challengeToken ?? '';

        const provisioning = document.getElementById('mfa-provisioning');
        if (provisioning && payload.data.totpUri) {
            provisioning.textContent = payload.data.totpUri;
        }

        setMessage('Login validado. Informe o código TOTP do seu aplicativo autenticador.', 'warning');
        return;
    }

    window.location.href = './index.html';
}

async function handlePasswordResetCpf(event) {
    event.preventDefault();

    const cpf = document.getElementById('reset-cpf')?.value ?? '';
    const senha = document.getElementById('reset-senha')?.value ?? '';

    const { response, payload } = await requestJson('/api/auth/password/reset/cpf', {
        method: 'POST',
        body: JSON.stringify({ cpf, senha })
    });

    if (!response?.ok || !payload.success) {
        setMessage(payload.message ?? 'Falha ao redefinir a senha.', 'danger');
        return;
    }

    setMessage(payload.message ?? 'Senha atualizada. Faça login novamente.', 'success');
    showPanel('login-step-one');
}

async function handleMfa(event) {
    event.preventDefault();

    const codigo = document.getElementById('codigo-mfa')?.value ?? '';
    const challengeToken = document.getElementById('challengeToken')?.value ?? '';

    const { response, payload } = await requestJson('/api/auth/mfa/verify', {
        method: 'POST',
        body: JSON.stringify({ codigo, challengeToken })
    });

    if (!response?.ok || !payload.success) {
        setMessage(payload.message ?? 'Falha na validacao MFA.', 'danger');
        return;
    }

    window.location.href = './index.html';
}

document.addEventListener('DOMContentLoaded', async () => {
    const { response, payload } = await requestJson('/api/auth/me');
    if (response?.ok && payload?.data) {
        window.location.href = './index.html';
        return;
    }

    document.getElementById('login-form')?.addEventListener('submit', handleLogin);
    document.getElementById('mfa-form')?.addEventListener('submit', handleMfa);
    document.getElementById('reset-cpf-form')?.addEventListener('submit', handlePasswordResetCpf);
    document.querySelectorAll('[data-action="show-reset"]').forEach((button) => {
        button.addEventListener('click', () => {
            showPanel('login-reset');
            setMessage('Informe seu CPF para redefinir a senha.', 'info');
        });
    });
    document.querySelectorAll('[data-action="show-login"]').forEach((button) => {
        button.addEventListener('click', () => {
            showPanel('login-step-one');
            setMessage('Faça login com seu usuário e senha.', 'info');
        });
    });
});
