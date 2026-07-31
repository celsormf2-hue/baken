<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

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
        $data = $contents === false || $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Formato de armazenamento inválido.');
        }
        return $data;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function storage_update(string $name, callable $mutator): mixed
{
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
            $data = $contents === false || $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new RuntimeException('Formato de armazenamento inválido.');
            }
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
