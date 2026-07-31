<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function graph_is_configured(): bool
{
    return MS_GRAPH_TENANT_ID !== ''
        && MS_GRAPH_CLIENT_ID !== ''
        && MS_GRAPH_CLIENT_SECRET !== ''
        && filter_var(MS_GRAPH_SENDER, FILTER_VALIDATE_EMAIL) !== false;
}

function http_status_from_headers(array $headers): int
{
    foreach (array_reverse($headers) as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $match)) {
            return (int) $match[1];
        }
    }
    return 0;
}

function graph_access_token(): ?string
{
    if (!graph_is_configured()) {
        error_log('Microsoft Graph não configurado: informe Tenant ID, Client ID e Client Secret.');
        return null;
    }

    $tokenUrl = 'https://login.microsoftonline.com/' . rawurlencode(MS_GRAPH_TENANT_ID) . '/oauth2/v2.0/token';
    $body = http_build_query([
        'client_id' => MS_GRAPH_CLIENT_ID,
        'client_secret' => MS_GRAPH_CLIENT_SECRET,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials',
    ]);
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
        'content' => $body,
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $response = @file_get_contents($tokenUrl, false, $context);
    $headers = $http_response_header ?? [];
    $status = http_status_from_headers($headers);
    $payload = is_string($response) ? json_decode($response, true) : null;

    if ($status !== 200 || !is_array($payload) || empty($payload['access_token'])) {
        $code = is_array($payload) ? ($payload['error'] ?? 'unknown_error') : 'no_response';
        error_log('Falha ao obter token Microsoft Graph: HTTP ' . $status . ' / ' . $code);
        return null;
    }
    return (string) $payload['access_token'];
}

function send_graph_mail(string $to, string $subject, string $html, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Destinatário de e-mail inválido.');
        return false;
    }
    $token = graph_access_token();
    if ($token === null) return false;

    $message = [
        'subject' => $subject,
        'body' => ['contentType' => 'HTML', 'content' => $html],
        'toRecipients' => [['emailAddress' => ['address' => $to]]],
    ];
    if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
        $message['replyTo'] = [['emailAddress' => ['address' => $replyToEmail, 'name' => $replyToName ?? '']]];
    }
    $payload = json_encode(['message' => $message, 'saveToSentItems' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $url = 'https://graph.microsoft.com/v1.0/users/' . rawurlencode(MS_GRAPH_SENDER) . '/sendMail';
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
        'content' => $payload,
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);
    $response = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $status = http_status_from_headers($headers);
    if ($status !== 202) {
        $result = is_string($response) ? json_decode($response, true) : null;
        $code = is_array($result) ? ($result['error']['code'] ?? 'unknown_error') : 'no_response';
        error_log('Falha no envio Microsoft Graph: HTTP ' . $status . ' / ' . $code);
        return false;
    }
    return true;
}

function send_smtp_mail(string $to, string $subject, string $html, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '' || MAIL_FROM === '') {
        error_log('Configuração SMTP incompleta. E-mail não enviado.');
        return false;
    }
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->Port = SMTP_PORT;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isHTML(true);
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->addAddress($to);
        if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyToEmail, $replyToName ?? '');
        }
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
        return $mail->send();
    } catch (Exception $exception) {
        error_log('Falha no envio SMTP: ' . $exception->getMessage());
        return false;
    }
}

function send_site_mail(string $to, string $subject, string $html, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    $html = email_layout($subject, $html);
    if (MAIL_TRANSPORT === 'graph' || (MAIL_TRANSPORT === 'auto' && graph_is_configured())) {
        return send_graph_mail($to, $subject, $html, $replyToEmail, $replyToName);
    }
    return send_smtp_mail($to, $subject, $html, $replyToEmail, $replyToName);
}

function email_layout(string $title, string $content): string
{
    return '<!doctype html><html lang="pt-BR"><body style="margin:0;padding:0;background:#f4f4f2;font-family:Arial,sans-serif;color:#222"><div style="max-width:640px;margin:0 auto;padding:28px 14px"><div style="padding:22px 28px;background:#111;color:#fff;border-radius:10px 10px 0 0"><strong style="font-size:22px;letter-spacing:.08em;color:#ed2445">BAKEN</strong><span style="margin-left:8px;font-size:12px;letter-spacing:.12em">CONSTRUTORA</span></div><div style="padding:30px 28px;background:#fff;border:1px solid #e7e7e4;border-top:0;border-radius:0 0 10px 10px"><h1 style="margin:0 0 20px;font-size:24px;font-weight:500;color:#111">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><div style="font-size:16px;line-height:1.65;color:#3f3f3f">' . $content . '</div></div><p style="margin:18px 0 0;text-align:center;color:#777;font-size:12px">Baken Construtora · Assistência técnica pós-obra</p></div></body></html>';
}
