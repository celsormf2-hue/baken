# Script de Deploy Automatizado - Baken Construtora
# Este script simplifica o fluxo de commit e push para o repositório remoto.

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "       DEPLOY AUTOMATIZADO - BAKEN           " -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan

# 1. Verificar status atual
Write-Host "`n[1/5] Inspecionando alterações locais..." -ForegroundColor Yellow
git status

# Verificar se há alterações
$status = git status --porcelain
if ([string]::IsNullOrEmpty($status)) {
    Write-Host "`nNenhuma alteração detectada para commit. Seu repositório está limpo." -ForegroundColor Green
    Exit
}

# 2. Perguntar o tipo de commit
Write-Host "`n[2/5] Escolha o tipo de alteração que realizou:" -ForegroundColor Yellow
Write-Host "1. style (CSS, Design, ajustes visuais)"
Write-Host "2. feat  (Novo elemento, seção ou página)"
Write-Host "3. fix   (Correção de bugs ou links quebrados)"
Write-Host "4. docs  (Atualização de documentações/handoffs)"
Write-Host "5. chore (Tarefas operacionais/configurações)"

$opcao = Read-Host "Digite o número (1-5)"
$tipo = "chore"

switch ($opcao) {
    "1" { $tipo = "style" }
    "2" { $tipo = "feat" }
    "3" { $tipo = "fix" }
    "4" { $tipo = "docs" }
    "5" { $tipo = "chore" }
    default { Write-Host "Opção inválida. Usando 'chore' por padrão." -ForegroundColor DarkYellow }
}

# 3. Perguntar a mensagem do commit
$mensagem = ""
while ([string]::IsNullOrWhiteSpace($mensagem)) {
    $mensagem = Read-Host "Digite uma descrição curta da alteração (ex: 'corrige margens da hero')"
}

$commitMsg = "$tipo: $mensagem"
Write-Host "`nMensagem do commit definida como: '$commitMsg'" -ForegroundColor Cyan

# 4. Sincronizar com repositório remoto (pull)
Write-Host "`n[3/5] Sincronizando com o GitHub (git pull)..." -ForegroundColor Yellow
git pull origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host "`n[ERRO] Falha ao sincronizar com o GitHub. Resolva os conflitos antes de prosseguir." -ForegroundColor Red
    Exit
}

# 5. Adicionar arquivos e fazer commit
Write-Host "`n[4/5] Preparando arquivos e commitando..." -ForegroundColor Yellow
git add .
git commit -m "$commitMsg"

if ($LASTEXITCODE -ne 0) {
    Write-Host "`n[ERRO] Falha ao criar o commit local." -ForegroundColor Red
    Exit
}

# 6. Enviar para o repositório remoto (push/deploy)
Write-Host "`n[5/5] Subindo alterações para o GitHub (deploy)..." -ForegroundColor Yellow
git push origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=============================================" -ForegroundColor Green
    Write-Host "   DEPLOY CONCLUÍDO COM SUCESSO! 🚀          " -ForegroundColor Green
    Write-Host "=============================================" -ForegroundColor Green
} else {
    Write-Host "`n[ERRO] Falha ao enviar para o GitHub. Verifique as credenciais ou a conexão." -ForegroundColor Red
}
