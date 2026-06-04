<?php

declare(strict_types=1);

use NexusRH\Controllers\FuncionarioController;
use NexusRH\Controllers\AuthController;
use NexusRH\Controllers\SistemaController;
use NexusRH\Services\SistemaService;
use NexusRH\Support\SessionAuth;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

spl_autoload_register(static function (string $className): void {
    $prefix = 'NexusRH\\';
    $baseDir = __DIR__ . '/src/';

    if (strncmp($prefix, $className, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

SessionAuth::start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$normalizedPath = rtrim($path, '/');

if ($normalizedPath === '') {
    $normalizedPath = '/';
}

$sistemaService = new SistemaService();

$dispatch = static function (callable $handler) use ($sistemaService, $path, $method): void {
    $startedAt = microtime(true);
    $handler();
    $statusCode = http_response_code() ?: 200;
    $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

    try {
        $sistemaService->registrarConsumoApi($path, $method, $statusCode, $elapsedMs);
    } catch (Throwable) {
    }

    exit();
};

if ($normalizedPath === '/api/auth/me' && $method === 'GET') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('GET', 'me');
    });
}

if ($normalizedPath === '/api/auth/login' && $method === 'POST') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('POST', 'login');
    });
}

if ($normalizedPath === '/api/auth/password/reset/cpf' && $method === 'POST') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('POST', 'password-reset-cpf');
    });
}

if ($normalizedPath === '/api/auth/mfa/verify' && $method === 'POST') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('POST', 'mfa-verify');
    });
}

if ($normalizedPath === '/api/auth/logout' && $method === 'POST') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('POST', 'logout');
    });
}

if ($normalizedPath === '/api/auth/mfa/setup' && $method === 'POST') {
    $dispatch(static function (): void {
        (new AuthController())->handleRequest('POST', 'mfa-setup');
    });
}

if ($normalizedPath === '/api/dashboard' && $method === 'GET') {
    $dispatch(static function (): void {
        (new SistemaController())->handleRequest('GET', 'dashboard');
    });
}

if ($normalizedPath === '/api/configuracoes' && $method === 'GET') {
    $dispatch(static function (): void {
        (new SistemaController())->handleRequest('GET', 'configuracoes');
    });
}

if ($normalizedPath === '/api/configuracoes' && $method === 'POST') {
    $dispatch(static function (): void {
        (new SistemaController())->handleRequest('POST', 'configuracoes');
    });
}

if ($normalizedPath === '/api/logs' && $method === 'GET') {
    $dispatch(static function (): void {
        (new SistemaController())->handleRequest('GET', 'logs');
    });
}

if ($normalizedPath === '/api/mapa' && $method === 'GET') {
    $dispatch(static function (): void {
        (new SistemaController())->handleRequest('GET', 'mapa');
    });
}

if ($normalizedPath === '/api/funcionarios' && $method === 'POST') {
    $dispatch(static function (): void {
        $controller = new FuncionarioController();
        $controller->handleRequest('POST');
    });
}

if ($normalizedPath === '/api/funcionarios' && $method === 'GET') {
    $dispatch(static function (): void {
        $controller = new FuncionarioController();
        $controller->handleRequest('GET');
    });
}

if (preg_match('#^/api/funcionarios/(\d+)$#', $normalizedPath, $matches) === 1 && $method === 'GET') {
    $funcionarioId = (int) $matches[1];
    $dispatch(static function () use ($funcionarioId): void {
        $controller = new FuncionarioController();
        $controller->handleRequest('GET', $funcionarioId);
    });
}

if (preg_match('#^/api/funcionarios/(\d+)$#', $normalizedPath, $matches) === 1 && $method === 'DELETE') {
    $funcionarioId = (int) $matches[1];
    $dispatch(static function () use ($funcionarioId): void {
        $controller = new FuncionarioController();
        $controller->handleRequest('DELETE', $funcionarioId);
    });
}

http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint nao encontrado',
]);
