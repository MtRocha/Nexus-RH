<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Services\HoleriteService;
use NexusRH\Support\JsonResponse;
use Throwable;

final class HoleriteController
{
    private HoleriteService $holeriteService;

    public function __construct()
    {
        $this->holeriteService = new HoleriteService();
    }

    public function handleRequest(string $method, string $action = '', ?int $holeriteId = null): void
    {
        try {
            if ($method === 'GET' && $action === 'list') {
                JsonResponse::success($this->holeriteService->listarPorUsuarioAtual());
                return;
            }

            if ($method === 'POST' && $action === 'create') {
                $payload = $this->readJsonPayload();
                $resultado = $this->holeriteService->gerarPorAdmin(
                    (int) ($payload['funcionarioId'] ?? 0),
                    (int) ($payload['mes'] ?? 0),
                    (int) ($payload['ano'] ?? 0),
                    (int) ($payload['diasTrabalhados'] ?? 0)
                );

                JsonResponse::success($resultado, 'Holerite gerado com sucesso.', 201);
                return;
            }

            if ($method === 'GET' && $action === 'pdf' && $holeriteId !== null) {
                $pdf = $this->holeriteService->gerarPdfPorId($holeriteId);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $pdf['filename'] . '"');
                header('Content-Length: ' . strlen($pdf['content']));
                echo $pdf['content'];
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
