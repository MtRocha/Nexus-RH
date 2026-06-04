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

            if ($method === 'GET' && $action === 'espelho') {
                $inicio = (string) ($_GET['inicio'] ?? '');
                $fim = (string) ($_GET['fim'] ?? '');
                JsonResponse::success($this->registroPontoService->listarEspelho($inicio, $fim));
                return;
            }

            if ($method === 'GET' && $action === 'espelho-pdf') {
                $inicio = (string) ($_GET['inicio'] ?? '');
                $fim = (string) ($_GET['fim'] ?? '');
                $pdf = $this->registroPontoService->gerarEspelhoPdf($inicio, $fim);
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
}
