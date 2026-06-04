<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Models\DTO\ConfiguracaoDTO;
use NexusRH\Services\SistemaService;
use NexusRH\Support\JsonResponse;
use Throwable;

final class SistemaController
{
    private SistemaService $sistemaService;

    public function __construct(
    ) {
        $this->sistemaService = new SistemaService();
    }

    public function handleRequest(string $method, string $action = ''): void
    {
        try {
            if ($method === 'GET' && $action === 'dashboard') {
                JsonResponse::success($this->sistemaService->resumoDashboard());
                return;
            }

            if ($method === 'GET' && $action === 'configuracoes') {
                JsonResponse::success($this->sistemaService->listarConfiguracoes());
                return;
            }

            if ($method === 'POST' && $action === 'configuracoes') {
                $payload = $this->readJsonPayload();
                $configuracao = $this->buildConfiguracaoDTO($payload);
                $id = $this->sistemaService->salvarConfiguracao($configuracao);
                $this->sistemaService->registrarOperacao('CONFIGURACAO', 'Configuracao salva via interface.', true, ['Chave' => $configuracao->Chave], 'ConfiguracaoSistema', (string) $id);
                JsonResponse::success(['ConfiguracaoID' => $id], 'Configuracao salva com sucesso.', 201);
                return;
            }

            if ($method === 'GET' && $action === 'logs') {
                JsonResponse::success([
                    'operacoes' => $this->sistemaService->listarLogsOperacao(),
                    'api' => $this->sistemaService->listarLogsApi(),
                ]);
                return;
            }

            if ($method === 'GET' && $action === 'mapa') {
                JsonResponse::success([
                    'latitude' => (float) $this->sistemaService->obterConfiguracao('mapa.latitude', '-23.5629'),
                    'longitude' => (float) $this->sistemaService->obterConfiguracao('mapa.longitude', '-46.6560'),
                    'zoom' => (int) $this->sistemaService->obterConfiguracao('mapa.zoom', '15'),
                    'titulo' => (string) $this->sistemaService->obterConfiguracao('empresa.nome', 'Nexus RH'),
                ]);
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

    private function buildConfiguracaoDTO(array $payload): ConfiguracaoDTO
    {
        $chave = trim((string) ($payload['chave'] ?? ''));
        $valor = trim((string) ($payload['valor'] ?? ''));

        if ($chave === '' || $valor === '') {
            throw new ValidationException('Chave e valor da configuracao sao obrigatorios.');
        }

        return new ConfiguracaoDTO(
            isset($payload['configuracaoId']) ? (int) $payload['configuracaoId'] : null,
            $chave,
            $valor,
            trim((string) ($payload['categoria'] ?? 'Geral')),
            isset($payload['descricao']) ? trim((string) $payload['descricao']) : null,
            trim((string) ($payload['tipoCampo'] ?? 'texto')),
            filter_var($payload['editavel'] ?? true, FILTER_VALIDATE_BOOL),
            filter_var($payload['ativo'] ?? true, FILTER_VALIDATE_BOOL)
        );
    }
}