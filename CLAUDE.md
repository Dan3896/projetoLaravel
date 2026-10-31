# CLAUDE.md — Sistema de Gestão de Chamados OiCram

Projeto de Extensão "Tecnologia e Sociedade II" (Universidade Positivo, Equipe 5, Turma 1, 2026).
Parceiro: **Márcio Pereira (OiCram)**, prestador de serviço que hoje recebe todos os chamados sem triagem nenhuma.

Spec técnica detalhada: [docs/specs/01-ordem-servico.md](docs/specs/01-ordem-servico.md). Siga os nomes de tabelas, campos e status que estão lá.

## Problemas que o sistema resolve

1. **Falta de triagem nos chamados**: muitos atendimentos simultâneos e conversas longas antes de o cliente dizer qual é o problema real. Não existe controle do perfil do cliente nem da base de preços.
2. **Falta de pré-orçamento automático**: o Márcio monta os orçamentos à mão, fora do fluxo do chamado. O valor se divide em desenvolvimento, QA e planejamento, e cada cliente tem uma faixa de preço própria.

## Stack e convenções

- Laravel 12 (PHP 8.2+), MySQL 8 no Docker (`127.0.0.1:3306`), phpMyAdmin em `localhost:8080`.
- Blade + Tailwind CSS v4 + Alpine.js, com Vite 6. Não usar Bootstrap.
- Papéis com `spatie/laravel-permission`: `admin` (Márcio/equipe) e `cliente`. O usuário cliente tem `cliente_id`.
- Requisito do parceiro: **PHP + Node**. O bot de triagem fica num serviço Node separado, que conversa com o Laravel por API.
- Código e mensagens em português. Controllers enxutos, regra de negócio em `app/Services`, validação em `FormRequest`.
- Commits no padrão `feat:`, `fix:`, `test:` (em português).

## Comandos

```bash
docker compose up -d
php artisan migrate:fresh --seed
php artisan serve
npm run dev
php artisan test
```

Usuários de seed (só para desenvolvimento): `admin@oicram.local` (admin) e `cliente@empresa.com` (cliente). A senha está em `database/seeders/UserSeeder.php`.
Os testes rodam com SQLite em memória (`phpunit.xml`).

---

## Status dos requisitos

Legenda: `[x]` feito · `[~]` parcial · `[ ]` pendente

### Infraestrutura e base
- [x] Docker com MySQL + phpMyAdmin e README de instalação
- [x] Migrations: `status`, `clientes`, `tipo_chamados`, `preco_clientes`, `chamados`, `chamado_triagens`, `chamado_anexos`, `orcamentos`, `historico_slas`, `users`, `cache`, tabelas de permissão, `etapas` (+ `chamados.etapa_id`), `chamado_historicos_internos`, `prazos_sla`; horas estimadas em `tipo_chamados`, detalhamento e status em `orcamentos`, `users.ativo`
- [x] Seeders: `StatusSeeder`, `EtapaSeeder`, `TipoChamadoSeeder` (com horas estimadas), `ClienteSeeder`, `PrecoClienteSeeder`, `PrazoSlaSeeder`, `RoleSeeder`, `UserSeeder`
- [x] Layout `layouts/main.blade.php` com `@vite` e Tailwind
- [x] Inicializar o Alpine.js em `resources/js/app.js`
- [x] Remover as sobras do template de eventos (`EventController`, `events/*`, `welcome.blade.php`, `public/img`, `public/css/style.css`, `public/js/script.js`)

### Autenticação e controle de acesso
- [x] Login e logout (`AuthController`)
- [x] Papéis `admin` e `cliente` (spatie)
- [x] Tela admin para listar e criar usuários cliente (`/admin/usuarios`)
- [x] Teste de acesso à tela admin (`tests/Feature/AdminUsuariosAccessTest.php`)
- [x] `ChamadoPolicy`: o cliente só vê (e só cancela) os chamados da própria empresa (`cliente_id`); o admin vê todos
- [x] Editar usuários no admin (inclusive trocar a empresa) e desativar/reativar o acesso (`users.ativo`; o login e as sessões abertas são bloqueados)

### Models Eloquent
- [x] `Chamado`, `Cliente`, `Status`, `TipoChamado`, `User`, `ChamadoTriagem`, `HistoricoSla`, `Etapa`, `ChamadoHistoricoInterno`, `ChamadoAnexo`, `Orcamento`, `PrecoCliente`, `PrazoSla`
- [x] Relacionamentos: `Chamado` → `anexos`, `orcamentos`, `orcamento` (vigente); `Cliente` → `precoCliente`, `users`

### RF-01 — Abertura de chamado
- [x] Formulário e `store`:
  - [x] o cliente abre sempre para a própria empresa (`Auth::user()->cliente_id`); só o admin escolhe a empresa
  - [x] validação em `StoreChamadoRequest`, com descrição de no mínimo 20 caracteres e anexos
  - [x] usar `ChamadoService` com `DB::transaction()` (o chamado começa na primeira etapa)
  - [x] criar o primeiro registro em `historico_slas` com status `ABERTO`
- [x] Listagem (`index`): o cliente vê só a própria empresa; busca (nº, descrição, empresa), filtros por empresa/status/etapa e paginação

### RF-02 — Triagem (matriz GUT)
Decisão de fluxo: o **cliente** informa só a urgência (`criticidade_cliente`) ao abrir; o **Márcio** faz a matriz GUT na tela de triagem (`/chamados/{id}/triagem`, só admin).
- [x] Tela de triagem com Gravidade, Urgência e Tendência (1 a 5), pontuação ao vivo em Alpine.js
- [x] Pontuação = G × U × T (1 a 125) e badge de prioridade (Baixa ≤ 24, Média 25–60, Alta 61–99, Crítica ≥ 100)
- [x] Gravar em `chamado_triagens` (`TriagemService`), corrigir o tipo e escolher o próximo status (`EM_TRIAGEM`, `EM_ANALISE`, `PENDENTE_CLIENTE`, `CANCELADO`)
- [x] Fila ordenada por pontuação GUT no painel do Márcio

### Reunião de 16/10/2026 — empresa, etapas, histórico interno e cancelamento
- [x] Cliente vinculado a uma empresa; vê e cancela só os chamados dela
- [x] Etapas visíveis ao cliente (Recebido, Triagem, Em andamento...), cadastradas pelo Márcio em `/admin/etapas` e escolhidas num select no chamado. São separadas do status interno, que continua controlando o SLA
- [x] Andamento "etapa x/y" com barra de progresso no detalhe do chamado
- [x] Aba "Histórico interno" no chamado (só admin), com anotações livres e registro automático de troca de etapa e cancelamento
- [x] Cancelar ordem (cliente ou admin), com motivo opcional

### RF-03 — Anexos
- [x] Upload de `.png`, `.jpg`, `.jpeg`, `.pdf`, `.txt`, `.log` e `.zip`, com até 10 MB cada (até 5 por envio), na abertura e depois
- [x] Gravar em `chamados/{id}` no disco **privado** (`storage/app/private`, não no `public`, para o arquivo não ficar acessível por link) e registrar em `chamado_anexos`
- [x] Download seguro, respeitando a policy; só o admin exclui

### RF-04 — Pré-orçamento automático (problema 2)
- [x] Catálogo de horas estimadas por tipo de chamado (planejamento, desenvolvimento e QA), editável em `/admin/tipos-chamado`
- [x] Faixa de preço por empresa (`preco_clientes.valor_hora`), no cadastro de empresas
- [x] `OrcamentoService`: cria o pré-orçamento ao abrir o chamado (horas × valor_hora por componente) quando a empresa tem valor-hora; o admin também cria manualmente
- [x] Detalhamento por componente guardado em `orcamentos`
- [x] Tela para o Márcio ajustar os valores antes de enviar
- [x] Status do orçamento: pendente, enviado, aprovado, recusado. O cliente só vê depois de enviado e aprova ou recusa (com motivo); aprovar move o chamado para `APROVADO`
- [x] Exportar o orçamento em PDF com Dev, QA e Planejamento separados (`barryvdh/laravel-dompdf`)

### RF-05 — SLA
- [x] `SlaService`: grava em `historico_slas` toda vez que o status muda
- [x] Pausar a contagem enquanto o chamado está em `PENDENTE_CLIENTE`
- [x] Calcular o tempo líquido de atendimento
- [x] Prazo de SLA por prioridade GUT (`/admin/prazos-sla`) e alerta de atraso no detalhe e no painel. Os valores iniciais (4h/8h/24h/72h) são sugestões: **o Márcio precisa confirmar**
- [x] Preencher os marcos `inicio_/fim_` de planejamento, dev e QA e o `fechado_em`

### RF-06 — Detalhe do chamado e painel do Márcio
- [x] `chamados.show` mostra os dados gerais e a descrição
  - [x] linha do tempo do SLA
  - [x] lista de anexos
  - [x] resumo GUT
  - [x] orçamento vinculado
  - [x] badge de status com cor por status (`x-status-badge`, `x-prioridade-badge`)
- [x] Ações do admin: trocar status, triar, editar o chamado e cancelar
- [x] Dashboard (`/dashboard`, só admin): aguardando triagem, fila por GUT, contagem por status e prioridade
- [x] Cadastro de empresas (`/admin/empresas`, nome, perfil e valor-hora), com o valor-hora visível no detalhe do chamado durante o atendimento
- [x] Histórico de chamados por empresa (link na lista de empresas, que abre a listagem filtrada)

### Bot de triagem (problema 1 e diferencial do projeto)
- [ ] Serviço Node (Fastify) para o bot de triagem
- [ ] NLP.js para classificar a intenção e o tipo de chamado a partir do texto do cliente
- [ ] Chat em tempo real no site (Socket.io)
- [x] Endpoint de API no Laravel para o bot: `GET /api/bot/tipos-chamado` e `POST /api/bot/chamados`, com token em `BOT_TRIAGEM_TOKEN` (`Authorization: Bearer`)
- [x] Gravar a conversa do bot no histórico interno do chamado

### Requisitos não funcionais
- [x] `@csrf` nos formulários e rotas protegidas com `auth`
- [ ] Layout responsivo (mobile-first) revisado em todas as telas
- [x] Bloquear upload de executáveis (`.php`, `.sh`, `.exe`): só as extensões e tipos MIME da lista passam
- [x] Rollback completo se a gravação de anexos ou do orçamento falhar (a transação desfaz o banco e os arquivos gravados são apagados)
- [ ] Proteção de dados conforme a LGPD (tarefa do Rafael, etapa 5)
- [ ] Deploy em produção/homologação com HTTPS

### Testes
- [x] Acesso à tela admin
- [x] Triagem, SLA (com pausa) e painel (`tests/Feature/TriagemSlaTest.php`)
- [x] Empresas, etapas, histórico interno, cancelamento e isolamento entre empresas (`tests/Feature/EmpresaEtapaHistoricoTest.php`)
- [x] Anexos, orçamento, prazo de SLA, desativação de usuário, edição, listagem e API do bot (`tests/Feature/AnexoOrcamentoPrazoTest.php`)

---

## Entregas do projeto de extensão (fora do código)

- [x] Etapas 1 a 4: equipe, parceiro (OiCram), problemas, pesquisa de tecnologias e ideia aprovada
- [~] Etapa 5: planejamento feito; corrigir as tarefas que citam Java/Spring Boot e Next.js, porque o projeto usa Laravel + Blade
- [ ] Etapa 6: link da pasta do projeto, vídeo de demonstração, evidências de produção por integrante, registro das validações com o Márcio (bot, preços, segurança, core, clientes, PDF, manual)
- [ ] Manual de uso do painel administrativo (Vitor)
- [ ] Etapa 7: plano de melhorias com as tarefas "Roteiro do vídeo", "Gravação do vídeo", "Edição do vídeo" e "Publicação do vídeo"
- [ ] Etapa 8: entrega ao parceiro, roteiro do vídeo e vídeo final com o depoimento do Márcio, postado no Instagram marcando @extensaoup e @mestrerafael47 (sem marca da Universidade Positivo, com os integrantes creditados)
