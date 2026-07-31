# Portal restrito de assistência técnica

## Fluxo

1. O cliente cria uma conta em `/portal-cliente/cadastro`.
2. O cadastro é gravado como `pending` e a notificação vai para o destino do ambiente.
3. O administrador entra em `/admin/login.php` e aprova ou recusa a solicitação.
4. Apenas contas `approved` conseguem abrir chamados em `/portal-cliente`.

## Configuração de produção

1. Hospede o projeto em um servidor com PHP 8.2+ e Apache/Nginx. Um host exclusivamente estático não executa este portal.
2. Execute `composer install --no-dev --optimize-autoloader` no servidor.
3. Copie `config.local.example.php` para `config.local.php` e preencha os valores reais fora do Git.
4. Defina `APP_ENV=production`, remova `MAIL_TEST_DESTINATION` e use `DESTINATION_EMAIL=rodrigo@baken.com.br` somente após autorização. Para Microsoft 365, use `smtp.office365.com`, porta `587` e STARTTLS.
5. Coloque `PRIVATE_DATA_DIR` fora do document root. Se isso não for possível, mantenha as regras de bloqueio e permissões 0700/0600.
6. Para Microsoft 365, registre um aplicativo no Entra ID, conceda a permissão de aplicativo `Mail.Send` do Microsoft Graph e dê consentimento administrativo. Preencha `MS_GRAPH_TENANT_ID`, `MS_GRAPH_CLIENT_ID` e `MS_GRAPH_CLIENT_SECRET`.
7. Confirme o envio Graph com um único teste controlado antes de abrir o portal ao público.

## Dados e LGPD

Os dados armazenados são identificação, contato, empreendimento, senha em hash Argon2id e chamados. Não armazene documentos ou anexos nesta versão. Defina período de retenção, processo de exclusão mediante solicitação e publique a política de privacidade antes do lançamento.

## Rotina administrativa

Revise periodicamente os cadastros pendentes, revogue acessos inativos quando necessário e faça backup criptografado do diretório privado. Os logs de auditoria ficam em `audit.json`.
# Execução local

Para que as URLs amigáveis do Portal funcionem localmente, inicie o PHP com o roteador do projeto:

```powershell
php -S localhost:8000 router.php
```

Ou execute `./iniciar-local.ps1` na raiz do projeto. Não utilize apenas `php -S localhost:8000`, pois esse modo devolve a página inicial para URLs amigáveis que não são arquivos físicos.
