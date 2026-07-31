<?php
require_once __DIR__ . '/lib/accounts.php';
verify_csrf();
$client = current_user();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$client || ($client['role'] ?? '') !== 'client') redirect('/portal-cliente');
$user = request_client_deletion($client['id']);
if (!$user) { flash('error', 'Não foi possível registrar o descadastro agora.'); redirect('/portal-cliente'); }
record_audit('client_deletion_requested', $client['id'], $client['id']);
$safe = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminUrl = app_url('/admin/entrar');
send_site_mail(DESTINATION_EMAIL, 'Solicitação de descadastro — Baken', '<p>Há uma solicitação de descadastro que requer análise.</p><p style="padding:16px;background:#f6f7f8"><strong>Cliente:</strong> ' . $safe($user['name']) . '<br><strong>E-mail:</strong> ' . $safe($user['email']) . '<br><strong>Telefone:</strong> ' . $safe($user['phone']) . '<br><strong>Solicitado em:</strong> ' . $safe(date('d/m/Y H:i')) . '</p><p>O acesso já foi bloqueado. Revise a solicitação e conduza a exclusão ou anonimização dos dados conforme a política de privacidade.</p>' . email_button($adminUrl, 'Acessar administração'));
send_site_mail($user['email'], 'Recebemos sua solicitação de descadastro — Baken', '<p>Olá, ' . $safe($user['name']) . '.</p><p>Recebemos sua solicitação de descadastro do Portal do Cliente. Seu acesso foi bloqueado imediatamente e a Baken analisará a exclusão ou anonimização dos dados conforme a política de privacidade.</p><p>Se esta solicitação não foi realizada por você, entre em contato com a Baken.</p>');
start_secure_session(); $_SESSION = []; session_destroy();
start_secure_session(); flash('success', 'Solicitação de descadastro recebida. Seu acesso foi bloqueado e a Baken fará a análise.'); redirect('/portal-cliente/entrar');
