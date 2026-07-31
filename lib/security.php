<?php

declare(strict_types=1);

require_once __DIR__ . '/storage.php';

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) { return; }
    session_name('baken_portal');
    $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !in_array($host, ['localhost', '127.0.0.1'], true),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    start_secure_session();
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Solicitação de segurança inválida. Atualize a página e tente novamente.');
    }
}

function flash(string $type, string $message): void { start_secure_session(); $_SESSION['flash'][] = [$type, $message]; }
function consume_flashes(): array { start_secure_session(); $items = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $items; }
function keep_old_input(array $input): void { start_secure_session(); $_SESSION['old_input'] = $input; }
function consume_old_input(): array { start_secure_session(); $input = $_SESSION['old_input'] ?? []; unset($_SESSION['old_input']); return is_array($input) ? $input : []; }
function canonical_path(string $path): string
{
    return [
        '/cliente.php' => '/portal-cliente',
        '/cadastro.php' => '/portal-cliente/cadastro',
        '/login.php' => '/portal-cliente/entrar',
        '/recuperar-senha.php' => '/portal-cliente/recuperar-senha',
        '/redefinir-senha.php' => '/portal-cliente/redefinir-senha',
        '/politica-privacidade.html' => '/politica-privacidade',
        '/admin/index.php' => '/admin',
        '/admin/login.php' => '/admin/entrar',
    ][$path] ?? $path;
}

function app_url(string $path): string
{
    $path = canonical_path('/' . ltrim($path, '/'));
    return APP_URL . $path;
}

function redirect(string $path): never { header('Location: ' . canonical_path($path), true, 303); exit; }

function current_user(): ?array { start_secure_session(); return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function is_approved_client(): bool
{
    $user = current_user();
    if (($user['role'] ?? '') !== 'client' || ($user['status'] ?? '') !== 'approved') return false;
    foreach (storage_read('users') as $storedUser) {
        if (($storedUser['id'] ?? '') === ($user['id'] ?? '')) return ($storedUser['status'] ?? '') === 'approved' && (int) ($storedUser['session_version'] ?? 1) === (int) ($user['session_version'] ?? 1);
    }
    return false;
}
function require_admin(): void { if (!is_admin()) { redirect('/admin/login.php'); } }
function require_approved_client(): void { if (!is_approved_client()) { flash('error', 'Seu acesso precisa estar aprovado para abrir chamados.'); redirect('/cliente.php'); } }

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }
function rate_limit(string $action, int $limit, int $window): bool
{
    return rate_limit_for($action, client_ip(), $limit, $window);
}
function rate_limit_for(string $action, string $subject, int $limit, int $window): bool
{
    $key = hash('sha256', $action . '|' . $subject);
    return storage_update('rate-limits', function (&$data) use ($key, $limit, $window): bool {
        $now = time();
        $entry = $data[$key] ?? ['count' => 0, 'started' => $now];
        if ($now - (int) $entry['started'] >= $window) { $entry = ['count' => 0, 'started' => $now]; }
        $entry['count']++;
        $data[$key] = $entry;
        foreach ($data as $rateKey => $rate) { if ($now - (int) ($rate['started'] ?? 0) > 86400) unset($data[$rateKey]); }
        return $entry['count'] <= $limit;
    });
}
