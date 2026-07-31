<?php

declare(strict_types=1);

// Entrada única das rotas dinâmicas quando o projeto é executado como
// Vercel Function. O roteador também continua sendo usado pelo php -S local.
$route = $_GET['route'] ?? '';
if (is_string($route)) {
    $_SERVER['REQUEST_URI'] = '/' . ltrim($route, '/');
}
require __DIR__ . '/../router.php';
