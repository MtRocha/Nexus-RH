<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Services\AutenticacaoService;
use NexusRH\Support\JsonResponse;
use Throwable;

final class AuthController
{
    private AutenticacaoService $autenticacaoService;

    public function __construct(
    ) {
        $this->autenticacaoService = new AutenticacaoService();
    }

    public function handleRequest(string $method, string $action = ''): void
    {
        try {
            if ($method === 'GET' && $action === 'me') {
                JsonResponse::success($this->autenticacaoService->me());
                return;
            }

            if ($method === 'POST' && $action === 'login') {
                $payload = $this->readJsonPayload();
                JsonResponse::success($this->autenticacaoService->login((string) ($payload['login'] ?? ''), (string) ($payload['senha'] ?? '')), 'Login processado com sucesso.');
                return;
            }

            if ($method === 'POST' && $action === 'password-reset-cpf') {
                $payload = $this->readJsonPayload();
                $this->autenticacaoService->resetarSenhaPorCpf((string) ($payload['cpf'] ?? ''), (string) ($payload['senha'] ?? ''));
                JsonResponse::success(null, 'Senha atualizada com sucesso.');
                return;
            }

            if ($method === 'POST' && $action === 'mfa-verify') {
                $payload = $this->readJsonPayload();
                JsonResponse::success($this->autenticacaoService->verificarMfa((string) ($payload['codigo'] ?? '')), 'MFA validado com sucesso.');
                return;
            }

            if ($method === 'POST' && $action === 'logout') {
                $this->autenticacaoService->logout();
                JsonResponse::success(null, 'Logout realizado com sucesso.');
                return;
            }

            if ($method === 'POST' && $action === 'mfa-setup') {
                JsonResponse::success($this->autenticacaoService->setupMfa(), 'MFA configurado com sucesso.');
                return;
            }

            JsonResponse::error('Metodo HTTP nao suportado para este endpoint.', 405);
        } catch (ValidationException | BusinessRuleException $exception) {
            JsonResponse::error($exception->getMessage(), 400);
        } catch (Throwable $exception) {
            JsonResponse::error($exception->getMessage() !== '' ? $exception->getMessage() : 'Erro interno no servidor.', 500);
        }
    }

    private function readJsonPayload(): array
    {
        $rawInput = file_get_contents('php://input');

        if ($rawInput === false || trim($rawInput) === '') {
            throw new ValidationException('Corpo da requisicao vazio.');
        }

        $payload = json_decode($rawInput, true);

        if (!is_array($payload)) {
            throw new ValidationException('JSON invalido no corpo da requisicao.');
        }

        return $payload;
    }
}