---
name: deploy-baken
description: Passo a passo e automação para realizar o deploy/sincronização do site da Baken Construtora via Git no GitHub.
---

# Guia de Deploy - Baken Construtora

Este guia ensina o passo a passo manual e automático para subir atualizações do site para produção através do repositório remoto do GitHub.

O deploy deste site é disparado **automaticamente** ao fazermos um `git push` para a branch `main` no GitHub.

---

## 🚀 Método 1: Deploy Automático via Script (Recomendado)

Criamos um script interativo em PowerShell para realizar todos os passos do deploy em sequência com segurança. Ele confere o status, pergunta qual alteração você realizou, commita com padrão semântico e faz o push.

### Como usar:
1. Abra o PowerShell na raiz do projeto (`c:\xampp\htdocs\baken`).
2. Execute o seguinte comando:
   ```powershell
   .\.agents\skills\deploy-baken\scripts\deploy.ps1
   ```
3. O script guiará você por todas as etapas.

---

## 🛠️ Método 2: Deploy Manual Passo a Passo

Caso prefira fazer o processo manualmente via terminal, siga rigorosamente as etapas abaixo para evitar inconsistências no repositório.

### Passo 1: Verificar Alterações Locais
Antes de qualquer alteração, veja o que foi modificado na sua máquina:
```bash
git status
```
*Isto listará todos os arquivos modificados (em vermelho) e arquivos novos não rastreados.*

### Passo 2: Adicionar Arquivos para Envio (Stage)
Adicione todos os arquivos modificados ou novos ao "palco" de preparação:
```bash
git add .
```
*(Caso queira adicionar apenas um arquivo específico, use `git add caminho/do/arquivo.html`)*

### Passo 3: Criar um Commit Semântico
Grave as alterações localmente com uma mensagem clara que siga o padrão do projeto:
```bash
git commit -m "style: ajusta layout da hero e tamanho das logos"
```

#### 💡 Padrões de Mensagens de Commit (Commits Semânticos):
- **style:** Alterações puramente visuais, CSS, ajustes de layout ou design (ex: `style: muda cor do botão principal`).
- **feat:** Adição de novos elementos ou seções no HTML/JS (ex: `feat: adiciona seção de depoimentos na home`).
- **fix:** Correção de bugs, links quebrados ou erros de código (ex: `fix: corrige alinhamento do menu no celular`).
- **docs:** Atualização de documentações, briefing ou handoff (ex: `docs: atualiza handoff da sessão`).
- **chore:** Atualizações que não modificam código de produção (ex: `chore: atualiza regras de gitignore`).

### Passo 4: Sincronizar com o Servidor (Prevenir Conflitos)
Antes de enviar, é sempre uma excelente prática puxar as alterações que possam ter sido feitas diretamente no GitHub por outros desenvolvedores ou interfaces:
```bash
git pull origin main
```
*Se houver conflitos, o Git avisará para resolvê-los antes de prosseguir.*

### Passo 5: Enviar para Produção (Deploy)
Envie as alterações commitadas para a branch principal do GitHub. Isso ativará a publicação de produção:
```bash
git push origin main
```

---

## ⚠️ Resolução de Problemas Comuns

### 1. Erro 403: Permission Denied (Permissão Negada)
* **Causa**: Sua conta Git local (`celsomf`) não tinha permissão de escrita no repositório `celsormf2-hue/baken`.
* **Solução**: Garantir que o perfil `celsomf` está adicionado como colaborador nas configurações de acesso do repositório no GitHub (já configurado e ativo).

### 2. Conflitos de Merge (Conflict)
* **Causa**: Alguém modificou o mesmo trecho do mesmo arquivo no repositório remoto.
* **Solução**:
  1. O Git inserirá marcadores de conflito (`<<<<<<<`, `=======`, `>>>>>>>`) nos arquivos afetados.
  2. Abra os arquivos no VS Code ou editor de texto.
  3. Escolha se mantém a sua alteração local, a alteração que veio do GitHub, ou ambas.
  4. Salve o arquivo resolvido.
  5. Rode `git add .` -> `git commit -m "merge: resolve conflitos de integração"` -> `git push origin main`.
