<?php

declare(strict_types=1);

/**
 * Sessões persistentes para ambientes serverless.
 *
 * A Vercel pode encaminhar requisições consecutivas para instâncias diferentes,
 * portanto o armazenamento padrão em arquivos temporários não é confiável. O
 * identificador da sessão é armazenado apenas como hash. As gravações usam um
 * UPSERT atômico e são confirmadas imediatamente, pois uma função serverless pode
 * ser encerrada logo após emitir o redirecionamento da resposta.
 */
final class PostgresSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = storage_pdo();
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS portal_sessions (' .
            'session_id_hash char(64) PRIMARY KEY, ' .
            'payload text NOT NULL, ' .
            'expires_at timestamptz NOT NULL, ' .
            'updated_at timestamptz NOT NULL DEFAULT now()' .
            ')'
        );
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS portal_sessions_expires_at_idx ON portal_sessions (expires_at)');
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $hash = hash('sha256', $id);
            $query = $this->pdo->prepare(
                'SELECT payload FROM portal_sessions WHERE session_id_hash = :hash AND expires_at > now()'
            );
            $query->execute(['hash' => $hash]);
            $row = $query->fetch();
            return $row ? (string) $row['payload'] : '';
        } catch (Throwable $exception) {
            error_log('Falha ao ler sessão persistente: ' . $exception->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $hash = hash('sha256', $id);
            $expiresAt = time() + SESSION_IDLE_TIMEOUT + 300;
            $query = $this->pdo->prepare(
                'INSERT INTO portal_sessions (session_id_hash, payload, expires_at, updated_at) ' .
                'VALUES (:hash, :payload, to_timestamp(:expires_at), now()) ' .
                'ON CONFLICT (session_id_hash) DO UPDATE SET ' .
                'payload = EXCLUDED.payload, expires_at = EXCLUDED.expires_at, updated_at = now()'
            );
            return $query->execute([
                'hash' => $hash,
                'payload' => $data,
                'expires_at' => $expiresAt,
            ]);
        } catch (Throwable $exception) {
            error_log('Falha ao salvar sessão persistente: ' . $exception->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $hash = hash('sha256', $id);
            $query = $this->pdo->prepare('DELETE FROM portal_sessions WHERE session_id_hash = :hash');
            return $query->execute(['hash' => $hash]);
        } catch (Throwable $exception) {
            error_log('Falha ao destruir sessão persistente: ' . $exception->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $query = $this->pdo->prepare('DELETE FROM portal_sessions WHERE expires_at <= now()');
            $query->execute();
            return $query->rowCount();
        } catch (Throwable $exception) {
            error_log('Falha ao limpar sessões expiradas: ' . $exception->getMessage());
            return false;
        }
    }

}

function configure_persistent_sessions(): void
{
    static $configured = false;
    static $handler = null;

    if ($configured || DATABASE_URL === '') {
        return;
    }
    $handler = new PostgresSessionHandler();
    session_set_save_handler($handler, true);
    $configured = true;
}
