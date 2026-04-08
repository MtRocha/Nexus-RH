<?php

declare(strict_types=1);

use NexusRH\Controllers\FuncionarioController;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$normalizedPath = rtrim($path, '/');

if ($normalizedPath === '') {
    $normalizedPath = '/';
}

if ($normalizedPath === '/api/funcionarios' && $method === 'POST') {
    $controller = new FuncionarioController();
    $controller->handleRequest('POST');
    exit();
}

if ($normalizedPath === '/api/funcionarios' && $method === 'GET') {
    $controller = new FuncionarioController();
    $controller->handleRequest('GET');
    exit();
}

if (preg_match('#^/api/funcionarios/(\d+)$#', $normalizedPath, $matches) === 1 && $method === 'GET') {
    $funcionarioId = (int) $matches[1];
    $controller = new FuncionarioController();
    $controller->handleRequest('GET', $funcionarioId);
    exit();
}

http_response_code(404);
echo json_encode([
    'success' => false,
    'message' => 'Endpoint nao encontrado',
]);
