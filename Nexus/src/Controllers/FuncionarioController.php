<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Models\DTO\FuncionarioDTO;
use NexusRH\Services\FuncionarioService;
use NexusRH\Support\JsonResponse;
use Throwable;

final class FuncionarioController
{
    private FuncionarioService $funcionarioService;

    public function __construct(
    ) {
        $this->funcionarioService = new FuncionarioService();
    }

    public function handleRequest(string $method, ?int $funcionarioId = null): void
    {
        try {
            if ($method === 'POST') {
                $payload = $this->readJsonPayload();
                $funcionario = $this->buildFuncionarioDTO($payload);
                $novoId = $this->funcionarioService->criar($funcionario);

                JsonResponse::success(['FuncionarioID' => $novoId], 'Funcionario criado com sucesso.', 201);
                return;
            }

            if ($method === 'GET') {
                if (isset($_GET['catalogos']) && $_GET['catalogos'] === '1') {
                    JsonResponse::success($this->funcionarioService->catalogos());
                    return;
                }

                if ($funcionarioId === null) {
                    $funcionarios = $this->funcionarioService->listarTodos();

                    JsonResponse::success($funcionarios);
                    return;
                }

                $funcionario = $this->funcionarioService->buscarPorId($funcionarioId);

                if ($funcionario === null) {
                    JsonResponse::error('Funcionario nao encontrado.', 404);
                    return;
                }

                JsonResponse::success($funcionario);
                return;
            }

            if ($method === 'DELETE' && $funcionarioId !== null) {
                $this->funcionarioService->desativar($funcionarioId);
                JsonResponse::success(null, 'Funcionario desativado com sucesso.');
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

    private function buildFuncionarioDTO(array $payload): FuncionarioDTO
    {
        $senhaHash = isset($payload['senhaHash']) ? trim((string) $payload['senhaHash']) : '';
        if ($senhaHash === '' && isset($payload['senha'])) {
            $senhaHash = password_hash((string) $payload['senha'], PASSWORD_DEFAULT);
        }

        if ($senhaHash === false || $senhaHash === '') {
            throw new ValidationException('Senha ou SenhaHash deve ser informado.');
        }

        $cpfLimpo = preg_replace('/\D/', '', (string) ($payload['cpf'] ?? '')) ?? '';

        return new FuncionarioDTO(
            isset($payload['funcionarioId']) ? (int) $payload['funcionarioId'] : null,
            trim((string) ($payload['nome'] ?? '')),
            $cpfLimpo,
            isset($payload['email']) ? trim((string) $payload['email']) : null,
            $senhaHash,
            isset($payload['perfilAcesso']) && trim((string) $payload['perfilAcesso']) !== ''
                ? trim((string) $payload['perfilAcesso'])
                : 'Usuario',
            (int) ($payload['cargoId'] ?? 0),
            (int) ($payload['centroCustoId'] ?? 0),
            isset($payload['supervisorId']) ? (int) $payload['supervisorId'] : null,
            (string) ($payload['salarioAtual'] ?? '0'),
            (string) ($payload['dataAdmissao'] ?? ''),
            isset($payload['dataDesligamento']) && trim((string) $payload['dataDesligamento']) !== ''
                ? (string) $payload['dataDesligamento']
                : null,
            isset($payload['status']) && trim((string) $payload['status']) !== ''
                ? trim((string) $payload['status'])
                : 'Ativo'
        );
    }
}
