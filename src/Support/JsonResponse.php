<?php

declare(strict_types=1);

namespace NexusRH\Support;

final class JsonResponse
{
    public static function send(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function success(mixed $data = null, string $message = 'Operacao realizada com sucesso.', int $statusCode = 200): void
    {
        $payload = ['success' => true, 'message' => $message];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        self::send($payload, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $extra = []): void
    {
        self::send(array_merge(['success' => false, 'message' => $message], $extra), $statusCode);
    }
}