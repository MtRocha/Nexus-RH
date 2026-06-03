const API_BASE_URL = window.location.origin;

async function requestJson(path, options = {}) {
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
}

function setMessage(text, kind = 'info') {
    const box = document.getElementById('login-message');
    if (!box) return;

    box.textContent = text;
    box.className = `alert alert-${kind}`;
    box.classList.remove('d-none');
}

async function handleLogin(event) {
    event.preventDefault();

    const login = document.getElementById('login')?.value ?? '';
    const senha = document.getElementById('senha')?.value ?? '';

    const { response, payload } = await requestJson('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ login, senha })
    });

    if (!response.ok || !payload.success) {
        setMessage(payload.message ?? 'Falha no login.', 'danger');
        return;
    }

    if (payload.data?.mfaRequired) {
        document.getElementById('login-step-one')?.classList.add('d-none');
        document.getElementById('login-step-two')?.classList.remove('d-none');
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

async function handleMfa(event) {
    event.preventDefault();

    const codigo = document.getElementById('codigo-mfa')?.value ?? '';
    const challengeToken = document.getElementById('challengeToken')?.value ?? '';

    const { response, payload } = await requestJson('/api/auth/mfa/verify', {
        method: 'POST',
        body: JSON.stringify({ codigo, challengeToken })
    });

    if (!response.ok || !payload.success) {
        setMessage(payload.message ?? 'Falha na validacao MFA.', 'danger');
        return;
    }

    window.location.href = './index.html';
}

document.addEventListener('DOMContentLoaded', async () => {
    const { payload } = await requestJson('/api/auth/me');
    if (payload?.data) {
        window.location.href = './index.html';
        return;
    }

    document.getElementById('login-form')?.addEventListener('submit', handleLogin);
    document.getElementById('mfa-form')?.addEventListener('submit', handleMfa);
});
