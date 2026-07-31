<?php

declare(strict_types=1);

// Uso: DATABASE_URL="..." php scripts/migrate-json-to-postgres.php C:\caminho\baken-private-data
// Nunca sobrescreve coleções que já existam no Postgres.
putenv('STORAGE_DRIVER=postgres');
require_once __DIR__ . '/../lib/storage.php';

$source = $argv[1] ?? '';
if ($source === '' || !is_dir($source)) {
    fwrite(STDERR, "Informe o diretório privado com os JSONs de origem.\n");
    exit(1);
}

foreach (['users', 'tickets', 'password-resets', 'audit', 'rate-limits'] as $name) {
    $file = $source . DIRECTORY_SEPARATOR . $name . '.json';
    if (!is_file($file)) {
        continue;
    }
    $json = file_get_contents($file);
    $data = $json === false ? [] : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) {
        throw new RuntimeException("Arquivo inválido: {$name}");
    }
    storage_update($name, function (&$current) use ($data, $name): void {
        if ($current !== []) {
            throw new RuntimeException("A coleção {$name} já contém dados no Postgres; migração interrompida.");
        }
        $current = $data;
    });
    fwrite(STDOUT, "Migrado: {$name}\n");
}
