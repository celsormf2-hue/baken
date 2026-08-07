<?php
/**
 * BAKEN CONSTRUTORA — Configurações Gerais do Sistema
 */

// Carrega variáveis do .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

// Configurações locais prevalecem sobre o .env e nunca são versionadas.
$localConfigFile = __DIR__ . '/config.local.php';
if (file_exists($localConfigFile)) {
    $localConfig = require $localConfigFile;
    if (is_array($localConfig)) {
        foreach ($localConfig as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $_ENV[$key] = (string) $value;
            }
        }
    }
}

function env_value(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    return is_string($value) && $value !== '' ? $value : $default;
}

$appTimezone = env_value('APP_TIMEZONE', 'America/Sao_Paulo');
if (!in_array($appTimezone, DateTimeZone::listIdentifiers(), true)) {
    error_log('APP_TIMEZONE inválido; usando America/Sao_Paulo.');
    $appTimezone = 'America/Sao_Paulo';
}
define('APP_TIMEZONE', $appTimezone);
date_default_timezone_set(APP_TIMEZONE);

define('APP_ENV', env_value('APP_ENV', 'production'));
define('APP_URL', rtrim(env_value('APP_URL', APP_ENV === 'development' ? 'http://localhost:8000' : 'https://www.baken.com.br'), '/'));
define('MAIL_TEST_DESTINATION', env_value('MAIL_TEST_DESTINATION'));
define(
    'DESTINATION_EMAIL',
    APP_ENV === 'development' && MAIL_TEST_DESTINATION !== ''
        ? MAIL_TEST_DESTINATION
        : env_value('DESTINATION_EMAIL', 'contato@baken.com.br')
);
define(
    'ADMIN_NOTIFICATION_EMAILS',
    APP_ENV === 'development' && MAIL_TEST_DESTINATION !== ''
        ? MAIL_TEST_DESTINATION
        : env_value(
            'ADMIN_NOTIFICATION_EMAILS',
            'lindomar.sousa@baken.com.br,rodrigo@baken.com.br'
        )
);

define('SMTP_HOST', env_value('SMTP_HOST'));
define('SMTP_PORT', (int) env_value('SMTP_PORT', '587'));
define('SMTP_ENCRYPTION', env_value('SMTP_ENCRYPTION', 'tls'));
define('SMTP_USERNAME', env_value('SMTP_USERNAME'));
define('SMTP_PASSWORD', env_value('SMTP_PASSWORD'));
define('MAIL_FROM', env_value('MAIL_FROM', SMTP_USERNAME));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'Baken Construtora'));
define('MAIL_TRANSPORT', env_value('MAIL_TRANSPORT', 'auto'));
define('DATABASE_URL', env_value('DATABASE_URL'));
define('STORAGE_DRIVER', env_value('STORAGE_DRIVER', DATABASE_URL !== '' ? 'postgres' : 'file'));
define('MS_GRAPH_TENANT_ID', env_value('MS_GRAPH_TENANT_ID'));
define('MS_GRAPH_CLIENT_ID', env_value('MS_GRAPH_CLIENT_ID'));
define('MS_GRAPH_CLIENT_SECRET', env_value('MS_GRAPH_CLIENT_SECRET'));
define('MS_GRAPH_SENDER', env_value('MS_GRAPH_SENDER', MAIL_FROM));
define('PRIVATE_DATA_DIR', env_value('PRIVATE_DATA_DIR', dirname(__DIR__) . '/baken-private-data'));
define('ADMIN_USERNAME', env_value('ADMIN_USERNAME'));
define('ADMIN_PASSWORD_HASH', env_value('ADMIN_PASSWORD_HASH'));
define('PASSWORD_RESET_TTL', 3600);
define('SESSION_IDLE_TIMEOUT', 1800);

// Chaves do Google reCAPTCHA v3 (lidas do .env)
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

// Pontuação mínima aceitável no reCAPTCHA v3 (de 0.0 bot a 1.0 humano)
define('RECAPTCHA_MIN_SCORE', 0.5);
