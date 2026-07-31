<?php

// Equivalente local das regras Apache: permite testar URLs amigáveis com php -S.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
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
    '/sair' => 'logout.php',
];
$legacy = [
    '/cliente' => '/portal-cliente', '/cliente.php' => '/portal-cliente', '/portal-cliente.html' => '/portal-cliente', '/cadastro.php' => '/portal-cliente/cadastro', '/login.php' => '/portal-cliente/entrar',
    '/recuperar-senha.php' => '/portal-cliente/recuperar-senha', '/redefinir-senha.php' => '/portal-cliente/redefinir-senha',
    '/admin/index.php' => '/admin', '/admin/login.php' => '/admin/entrar',
];
if (isset($legacy[$path])) { header('Location: ' . $legacy[$path], true, 302); exit; }
if (isset($routes[$path])) { require __DIR__ . '/' . $routes[$path]; exit; }
if (preg_match('#^/(?:lib|private-data|vendor|backup_site_antigo)/|^/(?:config(?:\.local)?\.php|\.env)$#i', $path)) { http_response_code(403); exit('Forbidden'); }
$staticPage = __DIR__ . rtrim($path, '/') . '.html';
if ($path !== '/' && is_file($staticPage)) { header('Content-Type: text/html; charset=utf-8'); readfile($staticPage); exit; }
return false;
