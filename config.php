<?php
/**
 * BAKEN CONSTRUTORA — Configurações Gerais do Sistema
 */

// E-mail de destino para recebimento das mensagens do formulário
define('DESTINATION_EMAIL', 'contato@baken.com.br');

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

// Chaves do Google reCAPTCHA v3 (lidas do .env)
define('RECAPTCHA_SITE_KEY', $_ENV['RECAPTCHA_SITE_KEY'] ?? '');
define('RECAPTCHA_SECRET_KEY', $_ENV['RECAPTCHA_SECRET_KEY'] ?? '');

// Pontuação mínima aceitável no reCAPTCHA v3 (de 0.0 bot a 1.0 humano)
define('RECAPTCHA_MIN_SCORE', 0.5);
