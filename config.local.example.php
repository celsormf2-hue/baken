<?php

// Copie este arquivo para config.local.php no servidor. Não versione o arquivo copiado.
return [
    'APP_ENV' => 'production',
    'APP_URL' => 'https://www.baken.com.br',
    'APP_TIMEZONE' => 'America/Sao_Paulo',
    'DESTINATION_EMAIL' => 'rodrigo@baken.com.br',
    'ADMIN_NOTIFICATION_EMAILS' => 'leonardo@baken.com.br,rodrigo@baken.com.br',
    'SMTP_HOST' => 'smtp.office365.com',
    'SMTP_PORT' => '587',
    'SMTP_ENCRYPTION' => 'tls',
    'SMTP_USERNAME' => 'site@baken.com.br',
    'SMTP_PASSWORD' => 'SUBSTITUA_PELA_SENHA_SMTP',
    'MAIL_FROM' => 'site@baken.com.br',
    'MAIL_FROM_NAME' => 'Baken Construtora',
    'MAIL_TRANSPORT' => 'graph',
    // Na Vercel, esta variável é criada ao conectar o banco Neon ao projeto.
    'DATABASE_URL' => 'postgresql://usuario:senha@host/neondb?sslmode=require',
    'STORAGE_DRIVER' => 'postgres',
    'MS_GRAPH_TENANT_ID' => 'ID_DO_DIRETORIO_TENANT',
    'MS_GRAPH_CLIENT_ID' => 'ID_DO_APLICATIVO',
    'MS_GRAPH_CLIENT_SECRET' => 'SEGREDO_DO_APLICATIVO',
    'MS_GRAPH_SENDER' => 'site@baken.com.br',
    'ADMIN_USERNAME' => 'SUBSTITUA_PELO_USUARIO_ADMIN',
    'ADMIN_PASSWORD_HASH' => 'GERE_UM_HASH_ARGON2ID_COM_password_hash',
    // Usado somente no desenvolvimento local sem DATABASE_URL.
    'PRIVATE_DATA_DIR' => '/caminho/fora/do/document-root/baken-private-data',
];
