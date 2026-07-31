<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * O armazenamento em arquivo continua disponível apenas para desenvolvimento
 * local. Em produção na Vercel, STORAGE_DRIVER=postgres usa o Neon via
 * DATABASE_URL, garantindo persistência e bloqueios transacionais.
 */
function storage_uses_postgres(): bool
{
    return STORAGE_DRIVER === 'postgres';
}

function storage_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if (DATABASE_URL === '') {
        throw new RuntimeException('DATABASE_URL não foi configurada para o armazenamento Postgres.');
    }

    $parts = parse_url(DATABASE_URL);
    if (!is_array($parts) || !isset($parts['host'], $parts['path'])) {
        throw new RuntimeException('DATABASE_URL inválida.');
    }
    parse_str($parts['query'] ?? '', $query);
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $parts['host'],
        $parts['port'] ?? 5432,
        ltrim($parts['path'], '/'),
        $query['sslmode'] ?? 'require'
    );
    $pdo = new PDO($dsn, rawurldecode($parts['user'] ?? ''), rawurldecode($parts['pass'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS portal_storage (storage_key varchar(80) PRIMARY KEY, payload jsonb NOT NULL DEFAULT '[]'::jsonb, updated_at timestamptz NOT NULL DEFAULT now())");
    return $pdo;
}

function storage_decode(string $contents): array
{
    $data = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new RuntimeException('Formato de armazenamento inválido.');
    }
    return $data;
}

function storage_path(string $name): string
{
    if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
        throw new InvalidArgumentException('Nome de armazenamento inválido.');
    }
    if (!is_dir(PRIVATE_DATA_DIR) && !mkdir(PRIVATE_DATA_DIR, 0700, true) && !is_dir(PRIVATE_DATA_DIR)) {
        throw new RuntimeException('Não foi possível preparar o armazenamento privado.');
    }
    return PRIVATE_DATA_DIR . DIRECTORY_SEPARATOR . $name . '.json';
}

function storage_read(string $name): array
{
    if (storage_uses_postgres()) {
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            throw new InvalidArgumentException('Nome de armazenamento inválido.');
        }
        $query = storage_pdo()->prepare('SELECT payload::text AS payload FROM portal_storage WHERE storage_key = :key');
        $query->execute(['key' => $name]);
        $row = $query->fetch();
        return $row ? storage_decode((string) $row['payload']) : [];
    }
    $path = storage_path($name);
    if (!file_exists($path)) {
        return [];
    }
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('Não foi possível ler o armazenamento.');
    }
    try {
        if (!flock($handle, LOCK_SH)) {
            throw new RuntimeException('Não foi possível bloquear o armazenamento para leitura.');
        }
        $contents = stream_get_contents($handle);
        return storage_decode($contents === false ? '' : $contents);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function storage_update(string $name, callable $mutator): mixed
{
    if (storage_uses_postgres()) {
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            throw new InvalidArgumentException('Nome de armazenamento inválido.');
        }
        $pdo = storage_pdo();
        $pdo->beginTransaction();
        try {
            $ensure = $pdo->prepare("INSERT INTO portal_storage (storage_key, payload) VALUES (:key, '[]'::jsonb) ON CONFLICT (storage_key) DO NOTHING");
            $ensure->execute(['key' => $name]);
            $query = $pdo->prepare('SELECT payload::text AS payload FROM portal_storage WHERE storage_key = :key FOR UPDATE');
            $query->execute(['key' => $name]);
            $row = $query->fetch();
            $data = storage_decode((string) ($row['payload'] ?? '[]'));
            $result = $mutator($data);
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $save = $pdo->prepare('UPDATE portal_storage SET payload = CAST(:payload AS jsonb), updated_at = now() WHERE storage_key = :key');
            $save->execute(['key' => $name, 'payload' => $encoded]);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    $path = storage_path($name);
    $lockPath = $path . '.lock';
    $lock = fopen($lockPath, 'c+');
    if ($lock === false) {
        throw new RuntimeException('Não foi possível bloquear o armazenamento para escrita.');
    }
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Não foi possível obter bloqueio de escrita.');
        }
        $data = [];
        if (file_exists($path)) {
            $contents = file_get_contents($path);
            $data = storage_decode($contents === false ? '' : $contents);
        }
        $result = $mutator($data);
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $temp = tempnam(PRIVATE_DATA_DIR, $name . '-');
        if ($temp === false || file_put_contents($temp, $encoded, LOCK_EX) === false || !rename($temp, $path)) {
            if ($temp !== false && file_exists($temp)) { unlink($temp); }
            throw new RuntimeException('Não foi possível salvar os dados.');
        }
        @chmod($path, 0600);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function new_id(): string
{
    return bin2hex(random_bytes(16));
}
