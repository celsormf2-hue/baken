<?php
require_once __DIR__ . '/../lib/layout.php';
if (is_admin()) redirect('/admin/index.php');
consume_flashes(); // Descarta avisos residuais de tentativas anteriores de acesso administrativo.
page_start('Administração');
?>
<h1>Administração Baken</h1>
<div class="card">
  <form method="post" action="/portal-cliente/autenticar">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="field"><label>Usuário administrativo</label><input name="login" required autocomplete="username"></div>
    <div class="field"><label>Senha</label><input type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn">Entrar</button>
  </form>
</div>
<?php page_end(); ?>
