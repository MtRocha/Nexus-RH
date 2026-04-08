<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Models\DTO\FuncionarioDTO;
use NexusRH\Services\FuncionarioService;
use Throwable;

final class FuncionarioController
{
    public function __construct(
        private readonly FuncionarioService $funcionarioService = new FuncionarioService()
    ) {
    }

    public function handleRequest(string $method, ?int $funcionarioId = null): void
    {
        header('Content-Type: application/json');

        try {
            if ($method === 'POST') {
                $payload = $this->readJsonPayload();
                $funcionario = $this->buildFuncionarioDTO($payload);
                $novoId = $this->funcionarioService->criar($funcionario);

                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Funcionario criado com sucesso.',
                    'data' => ['FuncionarioID' => $novoId],
                ]);
                return;
            }

            if ($method === 'GET') {
                if ($funcionarioId === null) {
                    $funcionarios = $this->funcionarioService->listarTodos();

                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'data' => $funcionarios,
                    ]);
                    return;
                }

                $funcionario = $this->funcionarioService->buscarPorId($funcionarioId);

                if ($funcionario === null) {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Funcionario nao encontrado.',
                    ]);
                    return;
                }

                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $funcionario,
                ]);
                return;
            }

            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Metodo HTTP nao suportado para este endpoint.',
            ]);
        } catch (ValidationException | BusinessRuleException $exception) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno no servidor.',
            ]);
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
