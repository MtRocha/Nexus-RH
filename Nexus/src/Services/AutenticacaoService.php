<?php

declare(strict_types=1);

namespace NexusRH\Services;

use NexusRH\DAO\AutenticacaoDAO;
use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Support\SessionAuth;
use NexusRH\Support\Totp;

final class AutenticacaoService
{
    private AutenticacaoDAO $autenticacaoDAO;
    private SistemaService $sistemaService;

    public function __construct(
    ) {
        $this->autenticacaoDAO = new AutenticacaoDAO();
        $this->sistemaService = new SistemaService();
    }

    public function login(string $login, string $senha): array
    {
        $login = trim($login);

        if ($login === '' || trim($senha) === '') {
            throw new ValidationException('Login e senha sao obrigatorios.');
        }

        $funcionario = $this->autenticacaoDAO->buscarFuncionarioPorLogin($login);

        if ($funcionario === null) {
            $this->sistemaService->registrarOperacao('LOGIN', 'Tentativa com usuario inexistente.', false, ['login' => $login], 'Funcionario', null);
            throw new BusinessRuleException('Credenciais invalidas.');
        }

        if (($funcionario['Status'] ?? '') !== 'Ativo') {
            $this->sistemaService->registrarOperacao('LOGIN', 'Login bloqueado para funcionario inativo.', false, ['FuncionarioID' => $funcionario['FuncionarioID']], 'Funcionario', (string) $funcionario['FuncionarioID']);
            throw new BusinessRuleException('Funcionario inativo ou desligado.');
        }

        if (!password_verify($senha, (string) $funcionario['SenhaHash'])) {
            $this->sistemaService->registrarOperacao('LOGIN', 'Senha incorreta.', false, ['FuncionarioID' => $funcionario['FuncionarioID']], 'Funcionario', (string) $funcionario['FuncionarioID']);
            throw new BusinessRuleException('Credenciais invalidas.');
        }

        $mfaObrigatorio = (bool) (int) $this->sistemaService->obterConfiguracao('mfa.obrigatorio', '0');
        $mfaRegistro = $this->autenticacaoDAO->buscarMfaPorFuncionarioId((int) $funcionario['FuncionarioID']);

        if ($mfaRegistro === null) {
            $secret = $this->sistemaService->gerarSecretTotp();
            $this->autenticacaoDAO->salvarMfa((int) $funcionario['FuncionarioID'], $secret, $mfaObrigatorio);
            $mfaRegistro = $this->autenticacaoDAO->buscarMfaPorFuncionarioId((int) $funcionario['FuncionarioID']);
        }

        $usuarioSeguro = $this->sanitizarUsuario($funcionario);

        if ($mfaObrigatorio || (!empty($mfaRegistro) && (bool) ($mfaRegistro['Ativo'] ?? false))) {
            SessionAuth::setPendingUser($usuarioSeguro);

            $this->sistemaService->registrarOperacao('LOGIN', 'Login autenticado aguardando MFA.', true, ['FuncionarioID' => $funcionario['FuncionarioID']], 'Funcionario', (string) $funcionario['FuncionarioID']);

            return [
                'mfaRequired' => true,
                'funcionario' => $usuarioSeguro,
                'totpUri' => Totp::buildProvisioningUri(
                    (string) $this->sistemaService->obterConfiguracao('empresa.nome', 'Nexus RH'),
                    (string) ($funcionario['Email'] ?? $funcionario['CPF']),
                    (string) $mfaRegistro['SecretBase32']
                ),
            ];
        }

        SessionAuth::setCurrentUser($usuarioSeguro);
        $this->sistemaService->registrarOperacao('LOGIN', 'Login realizado com sucesso.', true, ['FuncionarioID' => $funcionario['FuncionarioID']], 'Funcionario', (string) $funcionario['FuncionarioID']);

        return [
            'mfaRequired' => false,
            'funcionario' => $usuarioSeguro,
        ];
    }

    public function verificarMfa(string $codigo): array
    {
        $pendingUser = SessionAuth::pendingUser();

        if ($pendingUser === null) {
            throw new ValidationException('Nao existe autenticacao pendente.');
        }

        $mfaRegistro = $this->autenticacaoDAO->buscarMfaPorFuncionarioId((int) $pendingUser['FuncionarioID']);

        if ($mfaRegistro === null || empty($mfaRegistro['SecretBase32'])) {
            throw new BusinessRuleException('MFA nao configurado para este usuario.');
        }

        if (!Totp::verify((string) $mfaRegistro['SecretBase32'], $codigo)) {
            $this->sistemaService->registrarOperacao('MFA', 'Codigo TOTP invalido.', false, ['FuncionarioID' => $pendingUser['FuncionarioID']], 'Funcionario', (string) $pendingUser['FuncionarioID']);
            throw new BusinessRuleException('Codigo MFA invalido.');
        }

        SessionAuth::setCurrentUser($pendingUser);
        $this->autenticacaoDAO->marcarMfaUsado((int) $pendingUser['FuncionarioID']);
        $this->sistemaService->registrarOperacao('MFA', 'Codigo TOTP validado com sucesso.', true, ['FuncionarioID' => $pendingUser['FuncionarioID']], 'Funcionario', (string) $pendingUser['FuncionarioID']);

        return ['funcionario' => $pendingUser];
    }

    public function logout(): void
    {
        $usuario = SessionAuth::currentUser();
        SessionAuth::clear();

        $this->sistemaService->registrarOperacao('LOGOUT', 'Logout realizado.', true, ['FuncionarioID' => $usuario['FuncionarioID'] ?? null], 'Funcionario', isset($usuario['FuncionarioID']) ? (string) $usuario['FuncionarioID'] : null);
    }

    public function me(): ?array
    {
        return SessionAuth::currentUser();
    }

    public function setupMfa(): array
    {
        $usuario = SessionAuth::currentUser();

        if ($usuario === null) {
            throw new ValidationException('Usuario nao autenticado.');
        }

        $secret = $this->sistemaService->gerarSecretTotp();
        $this->autenticacaoDAO->salvarMfa((int) $usuario['FuncionarioID'], $secret, true);

        return [
            'secret' => $secret,
            'uri' => Totp::buildProvisioningUri(
                (string) $this->sistemaService->obterConfiguracao('empresa.nome', 'Nexus RH'),
                (string) ($usuario['Email'] ?? $usuario['CPF']),
                $secret
            ),
        ];
    }

    private function sanitizarUsuario(array $funcionario): array
    {
        return [
            'FuncionarioID' => (int) $funcionario['FuncionarioID'],
            'Nome' => (string) $funcionario['Nome'],
            'CPF' => (string) $funcionario['CPF'],
            'Email' => $funcionario['Email'] !== null ? (string) $funcionario['Email'] : null,
            'PerfilAcesso' => (string) $funcionario['PerfilAcesso'],
        ];
    }
}