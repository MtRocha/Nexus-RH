<?php

declare(strict_types=1);

namespace NexusRH\Controllers;

use NexusRH\Exceptions\BusinessRuleException;
use NexusRH\Exceptions\ValidationException;
use NexusRH\Services\RegistroPontoService;
use NexusRH\Support\JsonResponse;
use Throwable;

final class RegistroPontoController
{
    private RegistroPontoService $registroPontoService;

    public function __construct()
    {
        $this->registroPontoService = new RegistroPontoService();
    }

    public function handleRequest(string $method, string $action = ''): void
    {
        try {
            if ($method === 'POST' && $action === 'registrar') {
                JsonResponse::success($this->registroPontoService->registrarBatida(), 'Ponto registrado com sucesso.');
                return;
            }

            JsonResponse::error('Metodo HTTP nao suportado para este endpoint.', 405);
        } catch (ValidationException | BusinessRuleException $exception) {
            JsonResponse::error($exception->getMessage(), 400);
        } catch (Throwable $exception) {
            JsonResponse::error($exception->getMessage() !== '' ? $exception->getMessage() : 'Erro interno no servidor.', 500);
        }
    }
}
