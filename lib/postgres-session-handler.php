<?php

declare(strict_types=1);

/**
 * Sessões persistentes para ambientes serverless.
 *
 * A Vercel pode encaminhar requisições consecutivas para instâncias diferentes,
 * portanto o armazenamento padrão em arquivos temporários não é confiável. O
 * identificador da sessão é armazenado apenas como hash e cada sessão recebe um
 * advisory lock transacional para evitar perda de dados em requisições paralelas.
 */
final class PostgresSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private bool $transactionOpen = false;
    private ?string $lockedSessionHash = null;

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
        if ($this->transactionOpen && $this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
        $this->transactionOpen = false;
        $this->lockedSessionHash = null;
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $hash = $this->lockSession($id);
            $query = $this->pdo->prepare(
                'SELECT payload FROM portal_sessions WHERE session_id_hash = :hash AND expires_at > now()'
            );
            $query->execute(['hash' => $hash]);
            $row = $query->fetch();
            return $row ? (string) $row['payload'] : '';
        } catch (Throwable $exception) {
            $this->rollback();
            error_log('Falha ao ler sessão persistente: ' . $exception->getMessage());
            return false;
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $hash = $this->lockSession($id);
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
            $this->rollback();
            error_log('Falha ao salvar sessão persistente: ' . $exception->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $hash = $this->lockSession($id);
            $query = $this->pdo->prepare('DELETE FROM portal_sessions WHERE session_id_hash = :hash');
            return $query->execute(['hash' => $hash]);
        } catch (Throwable $exception) {
            $this->rollback();
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

    private function lockSession(string $id): string
    {
        $hash = hash('sha256', $id);
        if ($this->lockedSessionHash === $hash && $this->transactionOpen) {
            return $hash;
        }
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $this->transactionOpen = true;
        }
        $lock = $this->pdo->prepare('SELECT pg_advisory_xact_lock(hashtextextended(:hash, 0))');
        $lock->execute(['hash' => $hash]);
        $this->lockedSessionHash = $hash;
        return $hash;
    }

    private function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        $this->transactionOpen = false;
        $this->lockedSessionHash = null;
    }
}

function configure_persistent_sessions(): void
{
    static $configured = false;
    static $handler = null;

    if ($configured || !storage_uses_postgres()) {
        return;
    }
    $handler = new PostgresSessionHandler();
    session_set_save_handler($handler, true);
    $configured = true;
}
