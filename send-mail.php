<?php
/**
 * BAKEN CONSTRUTORA — Handler do Formulário de Contato com Google reCAPTCHA v3
 * Processa dados recebidos via POST e envia e-mail para contato@baken.com.br
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// Permite apenas requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -----------------------------------------------------------------------------
// Validação do Google reCAPTCHA v3
// -----------------------------------------------------------------------------
if (defined('RECAPTCHA_SECRET_KEY') && !empty(RECAPTCHA_SECRET_KEY) && RECAPTCHA_SECRET_KEY !== 'SUA_SECRET_KEY_AQUI') {
    $recaptcha_token = isset($_POST['g-recaptcha-response']) ? trim($_POST['g-recaptcha-response']) : '';

    if (empty($recaptcha_token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Falha no teste de segurança (token ausente).'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $post_data = http_build_query([
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_token,
        'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
    ]);

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $post_data,
            'timeout' => 5
        ]
    ];

    $context  = stream_context_create($opts);
    $response = @file_get_contents($verify_url, false, $context);
    $result   = json_decode($response, true);

    if (!$result || empty($result['success']) || (isset($result['score']) && $result['score'] < RECAPTCHA_MIN_SCORE)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Submissão bloqueada pelo filtro anti-spam.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Coleta e sanitiza os dados do formulário
$nome = isset($_POST['nome']) ? trim(strip_tags($_POST['nome'])) : '';
$empresa = isset($_POST['empresa']) ? trim(strip_tags($_POST['empresa'])) : '';
$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
$telefone = isset($_POST['telefone']) ? trim(strip_tags($_POST['telefone'])) : '';
$servico = isset($_POST['servico']) ? trim(strip_tags($_POST['servico'])) : '';
$mensagem = isset($_POST['mensagem']) ? trim(strip_tags($_POST['mensagem'])) : '';

// Validações básicas de campos obrigatórios
if (empty($nome)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, informe um e-mail válido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($telefone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, informe um número de telefone.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($servico)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, selecione o serviço de interesse.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Mapeamento legível do código de serviço selecionado
$servicos_map = [
    'eletrica'   => 'Instalações Elétricas',
    'hidraulica'  => 'Instalações Hidráulicas',
    'incendio'   => 'Combate a Incêndio',
    'completo'   => 'Todos os Serviços',
    'outro'      => 'Outro'
];

$servico_nome = isset($servicos_map[$servico]) ? $servicos_map[$servico] : $servico;
$empresa_exibicao = !empty($empresa) ? htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8') : 'Não informada';
$mensagem_exibicao = !empty($mensagem) ? nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')) : 'Nenhuma descrição informada.';
$data_envio = date('d/m/Y H:i:s');

// Configurações do e-mail de destino
$to = defined('DESTINATION_EMAIL') ? DESTINATION_EMAIL : 'contato@baken.com.br';
$subject = '=?UTF-8?B?' . base64_encode('Novo Contato do Site — ' . $nome) . '?=';

// Template HTML do e-mail
$body = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background-color: #f4f4f5; margin: 0; padding: 20px; color: #18181b; }
    .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .header { background-color: #0F0F0F; padding: 24px; text-align: center; border-bottom: 3px solid #C8102E; }
    .header h1 { color: #ffffff; font-size: 20px; margin: 0; font-weight: 600; letter-spacing: 0.05em; }
    .content { padding: 30px 24px; }
    .field { margin-bottom: 20px; }
    .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.1em; color: #71717a; font-weight: 700; margin-bottom: 4px; }
    .value { font-size: 15px; color: #09090b; font-weight: 400; line-height: 1.5; }
    .highlight { background-color: #fef2f2; border-left: 4px solid #C8102E; padding: 12px 16px; border-radius: 0 4px 4px 0; }
    .footer { background-color: #fafafa; padding: 16px 24px; font-size: 12px; color: #a1a1aa; text-align: center; border-top: 1px solid #f4f4f5; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>BAKEN CONSTRUTORA</h1>
    </div>
    <div class="content">
      <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 20px; color: #0F0F0F;">Solicitação de Orçamento / Contato</h2>
      
      <div class="field">
        <div class="label">Nome Completo</div>
        <div class="value">' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</div>
      </div>

      <div class="field">
        <div class="label">Empresa</div>
        <div class="value">' . $empresa_exibicao . '</div>
      </div>

      <div class="field">
        <div class="label">E-mail</div>
        <div class="value"><a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '" style="color: #C8102E; text-decoration: none;">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a></div>
      </div>

      <div class="field">
        <div class="label">Telefone / WhatsApp</div>
        <div class="value">' . htmlspecialchars($telefone, ENT_QUOTES, 'UTF-8') . '</div>
      </div>

      <div class="field highlight">
        <div class="label" style="color: #991b1b;">Serviço de Interesse</div>
        <div class="value" style="font-weight: 600; color: #991b1b;">' . htmlspecialchars($servico_nome, ENT_QUOTES, 'UTF-8') . '</div>
      </div>

      <div class="field">
        <div class="label">Descrição do Projeto / Mensagem</div>
        <div class="value">' . $mensagem_exibicao . '</div>
      </div>
    </div>
    <div class="footer">
      Mensagem enviada pelo site baken.com.br em ' . $data_envio . '
    </div>
  </div>
</body>
</html>
';

// Cabeçalhos HTTP para e-mail
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: Baken Site <' . $to . '>',
    'Reply-To: ' . $nome . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion()
];

// Disparo do e-mail nativo PHP mail()
$mail_sent = @mail($to, $subject, $body, implode("\r\n", $headers));

if ($mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso! Em breve nossa equipe entrará em contato.'
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocorreu um problema ao enviar a mensagem pelo servidor. Por favor, tente novamente ou entre em contato via WhatsApp.'
    ], JSON_UNESCAPED_UNICODE);
}
