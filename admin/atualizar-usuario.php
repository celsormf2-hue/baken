<?php
require_once __DIR__ . '/../lib/accounts.php';

verify_csrf();
require_admin();
$id = $_POST['id'] ?? '';
$operation = $_POST['operation'] ?? 'status';
$status = $_POST['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($id) || !is_string($operation)) redirect('/admin/index.php');

try {
    if ($operation === 'delete') {
        $user = delete_client($id);
        if (!$user) {
            flash('error', 'Cadastro não encontrado.');
        } else {
            record_audit('client_deleted', current_user()['id'], $user['id']);
            flash('success', 'Cadastro removido e acesso revogado. Os chamados existentes foram preservados no histórico.');
        }
    } elseif (is_string($status)) {
        $user = update_client_status($id, $status, current_user()['name']);
        if (!$user) {
            flash('error', 'Cadastro não encontrado.');
        } else {
            record_audit('client_' . $status, current_user()['id'], $user['id']);
            $label = $status === 'approved' ? 'aprovado' : ($status === 'rejected' ? 'recusado' : 'desativado');
            $message = '<p>Olá, ' . htmlspecialchars($user['name']) . '.</p><p>Seu cadastro no Portal do Cliente Baken foi <strong>' . $label . '</strong>.</p>';
            if ($status === 'approved') {
                $message .= '<p>Seu acesso está liberado e você já pode abrir chamados de assistência técnica.</p>' . email_button(app_url('/portal-cliente/entrar'), 'Acessar Portal do Cliente');
            } else {
                $message .= '<p>Em caso de dúvidas, entre em contato com a Baken.</p>';
            }
            send_site_mail($user['email'], 'Atualização do seu acesso — Baken', $message);
            flash('success', 'Cadastro ' . $label . '.');
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('error', 'Não foi possível atualizar o cadastro.');
}
redirect('/admin/index.php');
