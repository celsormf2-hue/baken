<?php

declare(strict_types=1);

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/security.php';

function find_client_by_email(string $email): ?array
{
    foreach (storage_read('users') as $user) {
        if (isset($user['email']) && hash_equals(strtolower($user['email']), strtolower($email))) return $user;
    }
    return null;
}

function find_client(string $id): ?array
{
    foreach (storage_read('users') as $user) { if (($user['id'] ?? '') === $id) return $user; }
    return null;
}

function create_client(array $input): array
{
    $email = strtolower(trim($input['email']));
    return storage_update('users', function (&$users) use ($input, $email): array {
        foreach ($users as $user) {
            if (isset($user['email']) && hash_equals(strtolower($user['email']), $email)) {
                throw new DomainException('E-mail já cadastrado.');
            }
        }
        $user = [
            'id' => new_id(), 'name' => trim($input['name']), 'email' => $email,
            'phone' => trim($input['phone']), 'development' => trim($input['development']),
            'unit' => trim($input['unit'] ?? ''), 'password_hash' => password_hash($input['password'], PASSWORD_ARGON2ID),
            'status' => 'pending', 'privacy_accepted_at' => gmdate('c'), 'created_at' => gmdate('c'),
            'approved_at' => null, 'approved_by' => null, 'session_version' => 1,
        ];
        $users[] = $user;
        return $user;
    });
}

function update_client_status(string $id, string $status, string $admin): ?array
{
    if (!in_array($status, ['approved', 'rejected', 'disabled'], true)) throw new InvalidArgumentException('Status inválido.');
    return storage_update('users', function (&$users) use ($id, $status, $admin): ?array {
        foreach ($users as &$user) {
            if (($user['id'] ?? '') === $id) {
                $user['status'] = $status;
                $user['approved_at'] = $status === 'approved' ? gmdate('c') : null;
                $user['approved_by'] = $status === 'approved' ? $admin : null;
                return $user;
            }
        }
        return null;
    });
}

function delete_client(string $id): ?array
{
    $deleted = null;
    storage_update('users', function (&$users) use ($id, &$deleted): void {
        foreach ($users as $index => $user) {
            if (($user['id'] ?? '') === $id) {
                $deleted = $user;
                unset($users[$index]);
                break;
            }
        }
        $users = array_values($users);
    });
    if ($deleted) {
        storage_update('password-resets', function (&$items) use ($id): void {
            $items = array_values(array_filter($items, fn($item) => ($item['user_id'] ?? '') !== $id));
        });
    }
    return $deleted;
}

function request_client_deletion(string $id): ?array
{
    return storage_update('users', function (&$users) use ($id): ?array {
        foreach ($users as &$user) {
            if (($user['id'] ?? '') !== $id) continue;
            $user['status'] = 'disabled';
            $user['deletion_requested_at'] = gmdate('c');
            return $user;
        }
        return null;
    });
}

function record_audit(string $action, string $actor, string $subject, array $meta = []): void
{
    storage_update('audit', function (&$items) use ($action, $actor, $subject, $meta): void {
        $items[] = ['id' => new_id(), 'action' => $action, 'actor' => $actor, 'subject' => $subject, 'meta' => $meta, 'at' => gmdate('c')];
        if (count($items) > 5000) $items = array_slice($items, -5000);
    });
}

function authenticate(string $login, string $password): ?array
{
    if (ADMIN_USERNAME !== '' && ADMIN_PASSWORD_HASH !== '' && hash_equals(strtolower(ADMIN_USERNAME), strtolower($login)) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        return ['id' => 'admin:' . strtolower(ADMIN_USERNAME), 'name' => ADMIN_USERNAME, 'role' => 'admin', 'status' => 'approved'];
    }
    $user = find_client_by_email($login);
    if ($user && password_verify($password, $user['password_hash'])) {
        return ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => 'client', 'status' => $user['status'], 'session_version' => (int) ($user['session_version'] ?? 1)];
    }
    return null;
}

function login_user(array $user): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $_SESSION['last_activity'] = time();
}

function request_password_reset(string $email): void
{
    $user = find_client_by_email($email);
    if (!$user) return;
    $token = bin2hex(random_bytes(32));
    storage_update('password-resets', function (&$items) use ($user, $token): void {
        foreach ($items as $key => $item) if (($item['user_id'] ?? '') === $user['id']) unset($items[$key]);
        $items[] = ['user_id' => $user['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => time() + PASSWORD_RESET_TTL, 'created_at' => gmdate('c')];
    });
    $url = app_url('/portal-cliente/redefinir-senha') . '?token=' . urlencode($token);
    send_site_mail($user['email'], 'Redefinição de senha — Baken', '<p>Olá,</p><p>Recebemos uma solicitação para redefinir sua senha no Portal do Cliente Baken.</p>' . email_button($url, 'Redefinir senha') . '<p style="color:#5f6470;font-size:14px">Este link expira em uma hora. Se você não fez essa solicitação, ignore esta mensagem.</p>');
}

function email_button(string $url, string $label): string
{
    return '<p style="margin:28px 0"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#d80b32;color:#ffffff;padding:13px 22px;border-radius:3px;font:600 14px Arial,sans-serif;text-decoration:none;text-transform:uppercase;letter-spacing:.3px">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></p>';
}

function reset_password(string $token, string $password): bool
{
    $hash = hash('sha256', $token);
    $reset = null;
    storage_update('password-resets', function (&$items) use ($hash, &$reset): void {
        foreach ($items as $key => $item) {
            if (hash_equals($item['token_hash'] ?? '', $hash) && (int) ($item['expires_at'] ?? 0) >= time()) { $reset = $item; unset($items[$key]); break; }
        }
        $items = array_values($items);
    });
    if (!$reset) return false;
    storage_update('users', function (&$users) use ($reset, $password): void {
        foreach ($users as &$user) if (($user['id'] ?? '') === $reset['user_id']) { $user['password_hash'] = password_hash($password, PASSWORD_ARGON2ID); $user['session_version'] = (int) ($user['session_version'] ?? 1) + 1; break; }
    });
    return true;
}
