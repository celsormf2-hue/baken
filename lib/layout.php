<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function page_start(string $title, bool $showEyebrow = true): void
{
    start_secure_session();
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
    $user = current_user();
    $account = $user
        ? '<span class="portal-user">' . htmlspecialchars($user['name']) . '</span><a class="portal-account-link" href="/sair">Sair</a>'
        : '<a class="portal-account-link" href="/portal-cliente/entrar">Entrar</a>';

    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . ' — Baken Construtora</title><meta name="robots" content="noindex"><link rel="stylesheet" href="/css/main.css"><link rel="stylesheet" href="/css/portal.css"><link rel="icon" type="image/svg+xml" href="/img/favicon.svg"></head><body class="portal-page"><div class="portal-wrap"><header class="portal-bar"><div class="container portal-bar__inner"><a href="/" class="portal-bar__logo"><img src="/img/logo.svg" alt="Baken Construtora"></a><nav class="portal-bar__actions">' . $account . '<a class="portal-account-link" href="/">Voltar ao site</a></nav></div></header><main class="portal-main"><div class="container portal-content">' . ($showEyebrow ? '<div class="portal-eyebrow">Portal do Cliente</div>' : '');
    foreach (consume_flashes() as [$type, $message]) {
        echo '<div class="portal-flash portal-flash--' . htmlspecialchars($type) . '">' . htmlspecialchars($message) . '</div>';
    }
}

function page_end(): void
{
    echo '</div></main><footer class="portal-footer"><div class="container">Baken Construtora · Assistência técnica pós-obra</div></footer></div><div class="portal-modal" id="portal-confirm-modal" hidden aria-hidden="true"><div class="portal-modal__backdrop" data-modal-close></div><section class="portal-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="portal-confirm-title" aria-describedby="portal-confirm-message"><button type="button" class="portal-modal__close" data-modal-close aria-label="Fechar">×</button><p class="portal-modal__eyebrow">Confirmação necessária</p><h2 id="portal-confirm-title">Remover cadastro?</h2><p id="portal-confirm-message"></p><div class="portal-modal__actions"><button type="button" class="btn btn--ghost" data-modal-close>Cancelar</button><button type="button" class="btn portal-modal__confirm" id="portal-confirm-action">Confirmar remoção</button></div></section></div><script src="/js/portal.js"></script></body></html>';
}
