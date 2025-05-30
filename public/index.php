<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

// === GLOBAL CORS HEADERS ===
header('Access-Control-Allow-Origin: *'); // Or set specific domain
header('Content-Type: application/json');

// === CORS Preflight Handler ===
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

// === ROUTING ===
$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // Allow both POST and OPTIONS for /graphql
    $r->addRoute(['POST', 'OPTIONS'], '/graphql', [App\Controller\GraphQL::class, 'handle']);
});

$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        try {
            echo json_encode(['error' => 'Route not found'], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Silently fail if encoding fails
        }
        break;

    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
        try {
            echo json_encode(['error' => 'Method not allowed. Allowed: ' . implode(', ', $allowedMethods)], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
        }
        break;

    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        echo $handler($vars);
        break;
}
