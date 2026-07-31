<?php
require_once __DIR__ . '/../lib/tickets.php';
verify_csrf(); require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/admin');
$id = $_POST['id'] ?? ''; $operation = $_POST['operation'] ?? '';
if (!is_string($id) || !in_array($operation, ['complete', 'delete'], true)) { flash('error', 'Ação de chamado inválida.'); redirect('/admin#chamados'); }
if ($operation === 'delete') { $ticket = delete_ticket($id); if ($ticket) { record_audit('ticket_deleted', current_user()['id'], $id, ['number' => $ticket['number'] ?? '']); flash('success', 'Chamado removido.'); } else flash('error', 'Chamado não encontrado.'); redirect('/admin#chamados'); }
$ticket = find_ticket($id);
if (!$ticket) { flash('error', 'Chamado não encontrado.'); redirect('/admin#chamados'); }
$safe = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
if (!send_site_mail($ticket['client_email'], 'Chamado concluído ' . $ticket['number'] . ' — Baken', '<p>Olá, ' . $safe($ticket['client_name']) . '.</p><p>Informamos que o chamado <strong>' . $safe($ticket['number']) . '</strong> foi concluído pela nossa equipe.</p><p style="padding:16px;background:#f6f7f8"><strong>Empreendimento:</strong> ' . $safe($ticket['development']) . '<br><strong>Serviço:</strong> ' . $safe($ticket['system']) . '</p><p>Se precisar de novo atendimento, acesse o Portal do Cliente.</p>')) { flash('error', 'O e-mail ao cliente falhou; o chamado permaneceu aberto.'); redirect('/admin#chamados'); }
$ticket = update_ticket_status($id, 'completed');
if (!$ticket) { flash('error', 'Não foi possível concluir o chamado; verifique antes de reenviar o e-mail.'); redirect('/admin#chamados'); }
record_audit('ticket_completed', current_user()['id'], $id, ['number' => $ticket['number'] ?? '']);
flash('success', 'Chamado concluído e cliente notificado por e-mail.'); redirect('/admin#chamados');
