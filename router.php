<?php

// Entrada única local e da Vercel: executa PHP e serve somente arquivos públicos.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . ltrim(rawurldecode($path), '/');
if (str_contains($path, "\0") || str_contains($path, '\\') || preg_match('#(?:^|/)\.\.(?:/|$)#', $path)) {
    http_response_code(400);
    exit('Bad Request');
}
$routes = [
    '/portal-cliente' => 'cliente.php',
    '/portal-cliente/cadastro' => 'cadastro.php',
    '/portal-cliente/entrar' => 'login.php',
    '/portal-cliente/recuperar-senha' => 'recuperar-senha.php',
    '/portal-cliente/redefinir-senha' => 'redefinir-senha.php',
    '/portal-cliente/cadastrar' => 'registrar.php',
    '/portal-cliente/autenticar' => 'autenticar.php',
    '/portal-cliente/chamados' => 'abrir-chamado.php',
    '/portal-cliente/solicitar-redefinicao' => 'solicitar-redefinicao.php',
    '/portal-cliente/confirmar-redefinicao' => 'confirmar-redefinicao.php',
    '/portal-cliente/descadastro' => 'solicitar-descadastro.php',
    '/admin' => 'admin/index.php',
    '/admin/entrar' => 'admin/login.php',
    '/admin/usuarios' => 'admin/atualizar-usuario.php',
    '/admin/chamados' => 'admin/atualizar-chamado.php',
    '/send-mail.php' => 'send-mail.php',
    '/sair' => 'logout.php',
];
$legacy = [
    '/cliente' => '/portal-cliente', '/cliente.php' => '/portal-cliente', '/portal-cliente.html' => '/portal-cliente', '/cadastro.php' => '/portal-cliente/cadastro', '/login.php' => '/portal-cliente/entrar',
    '/recuperar-senha.php' => '/portal-cliente/recuperar-senha', '/redefinir-senha.php' => '/portal-cliente/redefinir-senha',
    '/admin/index.php' => '/admin', '/admin/login.php' => '/admin/entrar',
];
if (isset($legacy[$path])) { header('Location: ' . $legacy[$path], true, 302); exit; }
if (isset($routes[$path])) { require __DIR__ . '/' . $routes[$path]; exit; }

// Qualquer implementação, configuração ou dado privado é inacessível pela web.
if (preg_match('#^/(?:api|admin|lib|private-data|vendor|scripts|docs|backup_site_antigo)(?:/|$)|^/(?:config(?:\.local)?\.php|composer\.(?:json|lock)|router\.php|\.env|\.git|\.htaccess|vercel\.json)#i', $path)) {
    http_response_code(404);
    exit('Not Found');
}

// URLs antigas com .html continuam normalizadas.
if (preg_match('#^/([a-z0-9-]+)\.html$#i', $path, $match) && is_file(__DIR__ . '/' . $match[1] . '.html')) {
    header('Location: /' . $match[1], true, 301);
    exit;
}

$mimeTypes = [
    'css' => 'text/css; charset=utf-8', 'js' => 'application/javascript; charset=utf-8',
    'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp',
    'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
    'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
    'txt' => 'text/plain; charset=utf-8', 'xml' => 'application/xml; charset=utf-8',
];

$serve = static function (string $file, string $contentType): never {
    $resolved = realpath($file);
    $root = realpath(__DIR__);
    if ($resolved === false || $root === false || !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR) || !is_file($resolved)) {
        http_response_code(404);
        exit('Not Found');
    }
    header('Content-Type: ' . $contentType);
    header('X-Content-Type-Options: nosniff');
    readfile($resolved);
    exit;
};

if ($path === '/') {
    $serve(__DIR__ . '/index.html', 'text/html; charset=utf-8');
}

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if ($extension !== '' && isset($mimeTypes[$extension])) {
    $serve(__DIR__ . $path, $mimeTypes[$extension]);
}

$staticPage = __DIR__ . rtrim($path, '/') . '.html';
if ($path !== '/' && is_file($staticPage)) {
    $serve($staticPage, 'text/html; charset=utf-8');
}

http_response_code(404);
exit('Not Found');
