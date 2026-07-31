<?php
require_once __DIR__ . '/../lib/layout.php';
require_once __DIR__ . '/../lib/accounts.php';
require_once __DIR__ . '/../lib/tickets.php';

require_admin();
$users = storage_read('users');
$tickets = array_values(array_filter(storage_read('tickets'), fn($ticket) => ($ticket['status'] ?? 'open') !== 'completed'));
$pending = array_values(array_filter($users, fn($user) => ($user['status'] ?? '') === 'pending'));
$processed = array_values(array_filter($users, fn($user) => ($user['status'] ?? '') !== 'pending'));
$safe = fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$statusLabel = fn($status) => ['approved' => 'Liberado', 'rejected' => 'Recusado', 'disabled' => 'Revogado', 'pending' => 'Pendente'][$status] ?? $status;

function admin_icon(string $name): string
{
    $icons = [
        'approve' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.2 4.2L19.5 6"/></svg>',
        'reject' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>',
        'revoke' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
        'delete' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg>',
        'complete' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.2 4.2L19.5 6"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function admin_action_button(string $icon, string $tooltip, string $status = '', bool $delete = false): string
{
    $attributes = $delete
        ? 'name="operation" value="delete" data-confirm="Remover este cadastro? O acesso será revogado. Os chamados existentes permanecerão no histórico."'
        : 'name="status" value="' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '"';
    return '<button class="admin-icon-button admin-icon-button--' . $icon . '" type="submit" ' . $attributes . ' aria-label="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '" data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '">' . admin_icon($icon) . '</button>';
}

page_start('Administração', false);
?>
<h1 class="admin-page-title">Administração</h1>

<nav class="admin-menu" role="tablist" aria-label="Menu administrativo">
  <button id="tab-cadastros" type="button" role="tab" aria-selected="true" aria-controls="cadastros" data-admin-tab="cadastros">Cadastros <span><?= count($pending) ?></span></button>
  <button id="tab-contas" type="button" role="tab" aria-selected="false" aria-controls="contas" data-admin-tab="contas">Contas <span><?= count($processed) ?></span></button>
  <button id="tab-chamados" type="button" role="tab" aria-selected="false" aria-controls="chamados" data-admin-tab="chamados">Chamados <span><?= count($tickets) ?></span></button>
</nav>

<section id="cadastros" class="card admin-section" role="tabpanel" aria-labelledby="tab-cadastros">
  <div class="admin-section__header"><div><p class="admin-section__eyebrow">Aprovação de acesso</p><h2 id="cadastros-title">Cadastros pendentes</h2></div><span class="admin-count"><?= count($pending) ?> pendente<?= count($pending) === 1 ? '' : 's' ?></span></div>
  <?php if (!$pending): ?><p class="muted">Nenhum cadastro pendente.</p><?php else: ?>
    <div class="data-table" data-datatable data-page-size="10">
      <div class="data-table__toolbar"><label class="data-table__search"><span>Pesquisar cadastros</span><input type="search" placeholder="Nome, e-mail, telefone ou obra" data-dt-search></label><label class="data-table__page-size"><span>Exibir</span><select data-dt-page-size><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></label></div>
      <div class="data-table__scroll"><table class="table"><thead><tr><th><button type="button" data-dt-sort="0">Cliente</button></th><th><button type="button" data-dt-sort="1">Obra</button></th><th><button type="button" data-dt-sort="2">Solicitado</button></th><th>Ações</th></tr></thead><tbody>
      <?php foreach ($pending as $item): ?><tr><td data-sort="<?= $safe($item['name']) ?>"><strong><?= $safe($item['name']) ?></strong><br><span class="small"><?= $safe($item['email']) ?><br><?= $safe($item['phone']) ?></span></td><td data-sort="<?= $safe($item['development']) ?>"><?= $safe($item['development']) ?></td><td data-sort="<?= $safe($item['created_at']) ?>"><?= $safe(date('d/m/Y H:i', strtotime($item['created_at']))) ?></td><td><form class="admin-actions" method="post" action="/admin/usuarios"><input type="hidden" name="csrf" value="<?= $safe(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $safe($item['id']) ?>"><?= admin_action_button('approve', 'Aprovar cadastro', 'approved') ?><?= admin_action_button('reject', 'Recusar cadastro', 'rejected') ?><?= admin_action_button('delete', 'Remover cadastro', '', true) ?></form></td></tr><?php endforeach; ?>
      </tbody></table></div><div class="data-table__footer"><span data-dt-summary></span><div data-dt-pagination></div></div>
    </div>
  <?php endif; ?>
</section>

<section id="contas" class="card admin-section" role="tabpanel" aria-labelledby="tab-contas" hidden>
  <div class="admin-section__header"><div><p class="admin-section__eyebrow">Gestão de acesso</p><h2 id="contas-title">Contas cadastradas</h2></div><span class="admin-count"><?= count($processed) ?> conta<?= count($processed) === 1 ? '' : 's' ?></span></div>
  <?php if (!$processed): ?><p class="muted">Nenhuma conta processada.</p><?php else: ?>
    <div class="data-table" data-datatable data-page-size="10">
      <div class="data-table__toolbar"><label class="data-table__search"><span>Pesquisar contas</span><input type="search" placeholder="Nome, e-mail ou obra" data-dt-search></label><label class="data-table__page-size"><span>Exibir</span><select data-dt-page-size><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></label></div>
      <div class="data-table__scroll"><table class="table"><thead><tr><th><button type="button" data-dt-sort="0">Cliente</button></th><th><button type="button" data-dt-sort="1">Obra</button></th><th><button type="button" data-dt-sort="2">Status</button></th><th>Ações</th></tr></thead><tbody>
      <?php foreach ($processed as $item): $status = $item['status'] ?? ''; ?><tr><td data-sort="<?= $safe($item['name']) ?>"><strong><?= $safe($item['name']) ?></strong><br><span class="small"><?= $safe($item['email']) ?><br><?= $safe($item['phone']) ?></span></td><td data-sort="<?= $safe($item['development']) ?>"><?= $safe($item['development']) ?></td><td data-sort="<?= $safe($status) ?>"><span class="status-pill status-pill--<?= $safe($status) ?>"><?= $safe($statusLabel($status)) ?></span></td><td><form class="admin-actions" method="post" action="/admin/usuarios"><input type="hidden" name="csrf" value="<?= $safe(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $safe($item['id']) ?>"><?php if ($status === 'approved'): ?><?= admin_action_button('revoke', 'Revogar acesso', 'disabled') ?><?php else: ?><?= admin_action_button('approve', 'Liberar acesso', 'approved') ?><?php endif; ?><?= admin_action_button('delete', 'Remover conta', '', true) ?></form></td></tr><?php endforeach; ?>
      </tbody></table></div><div class="data-table__footer"><span data-dt-summary></span><div data-dt-pagination></div></div>
    </div>
  <?php endif; ?>
</section>

<section id="chamados" class="card admin-section" role="tabpanel" aria-labelledby="tab-chamados" hidden>
  <div class="admin-section__header"><div><p class="admin-section__eyebrow">Acompanhamento</p><h2 id="chamados-title">Chamados</h2></div><span class="admin-count"><?= count($tickets) ?> chamado<?= count($tickets) === 1 ? '' : 's' ?></span></div>
  <?php if (!$tickets): ?><p class="muted">Nenhum chamado registrado.</p><?php else: ?>
    <div class="data-table" data-datatable data-page-size="10">
      <div class="data-table__toolbar"><label class="data-table__search"><span>Pesquisar chamados</span><input type="search" placeholder="Número, cliente, sistema ou prioridade" data-dt-search></label><label class="data-table__page-size"><span>Exibir</span><select data-dt-page-size><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select></label></div>
      <div class="data-table__scroll"><table class="table"><thead><tr><th><button type="button" data-dt-sort="0">Número</button></th><th><button type="button" data-dt-sort="1">Cliente</button></th><th><button type="button" data-dt-sort="2">Serviço</button></th><th><button type="button" data-dt-sort="3">Data</button></th><th>Ações</th></tr></thead><tbody>
      <?php foreach (array_reverse($tickets) as $ticket): $ticketData = htmlspecialchars(json_encode(['number' => $ticket['number'], 'client' => $ticket['client_name'], 'email' => $ticket['client_email'], 'development' => $ticket['development'], 'unit' => $ticket['unit'] ?? '', 'system' => $ticket['system'], 'priority' => $ticket['priority'], 'description' => $ticket['description'], 'createdAt' => date('d/m/Y H:i', strtotime($ticket['created_at']))], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?><tr class="admin-ticket-row" tabindex="0" role="button" data-ticket-detail='<?= $ticketData ?>'><td data-sort="<?= $safe($ticket['number']) ?>"><strong><?= $safe($ticket['number']) ?></strong></td><td data-sort="<?= $safe($ticket['client_name']) ?>"><?= $safe($ticket['client_name']) ?></td><td data-sort="<?= $safe($ticket['system']) . ' ' . $safe($ticket['priority']) ?>"><strong><?= $safe($ticket['system']) ?></strong> · <?= $safe($ticket['priority']) ?></td><td data-sort="<?= $safe($ticket['created_at']) ?>"><?= $safe(date('d/m/Y H:i', strtotime($ticket['created_at']))) ?></td><td><form class="admin-actions" method="post" action="/admin/chamados"><input type="hidden" name="csrf" value="<?= $safe(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $safe($ticket['id']) ?>"><button class="admin-icon-button admin-icon-button--complete" type="submit" name="operation" value="complete" aria-label="Marcar como concluído" data-tooltip="Marcar como concluído"><?= admin_icon('complete') ?></button><button class="admin-icon-button admin-icon-button--delete" type="submit" name="operation" value="delete" data-confirm="Remover este chamado permanentemente?" aria-label="Remover chamado" data-tooltip="Remover chamado"><?= admin_icon('delete') ?></button></form></td></tr><?php endforeach; ?>
      </tbody></table></div><div class="data-table__footer"><span data-dt-summary></span><div data-dt-pagination></div></div>
    </div>
  <?php endif; ?>
</section>
<div class="ticket-detail-modal" id="ticket-detail-modal" hidden aria-hidden="true"><div class="ticket-detail-modal__backdrop" data-ticket-detail-close></div><section class="ticket-detail-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ticket-detail-title"><button type="button" class="ticket-detail-modal__close" data-ticket-detail-close aria-label="Fechar">×</button><p class="admin-section__eyebrow">Detalhes do chamado</p><h2 id="ticket-detail-title"></h2><dl id="ticket-detail-content"></dl></section></div>
<?php page_end(); ?>
