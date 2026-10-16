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
- [x] Migrations: `status`, `clientes`, `tipo_chamados`, `preco_clientes`, `chamados`, `chamado_triagens`, `chamado_anexos`, `orcamentos`, `historico_slas`, `users`, `cache`, tabelas de permissão
- [x] Seeders: `StatusSeeder`, `TipoChamadoSeeder`, `ClienteSeeder`, `RoleSeeder`, `UserSeeder`
- [ ] `PrecoClienteSeeder` com dados de teste
- [x] Layout `layouts/main.blade.php` com `@vite` e Tailwind
- [x] Inicializar o Alpine.js em `resources/js/app.js`
- [ ] Remover as sobras do template de eventos: `EventController`, `resources/views/events/*`, `public/img/events`, `hdcevents_logo.svg`, `public/css/style.css` e `public/js/script.js`, se não forem usados

### Autenticação e controle de acesso
- [x] Login e logout (`AuthController`)
- [x] Papéis `admin` e `cliente` (spatie)
- [x] Tela admin para listar e criar usuários cliente (`/admin/usuarios`)
- [x] Teste de acesso à tela admin (`tests/Feature/AdminUsuariosAccessTest.php`)
- [ ] `ChamadoPolicy`: o cliente só vê os chamados do próprio `cliente_id`; o admin vê todos
- [ ] Editar e desativar usuários no admin

### Models Eloquent
- [x] `Chamado`, `Cliente`, `Status`, `TipoChamado`, `User`, `ChamadoTriagem`, `HistoricoSla`
- [ ] `ChamadoAnexo`, `Orcamento`, `PrecoCliente`
- [ ] Relacionamentos que faltam: `Chamado` → `anexos`, `orcamentos`; `Cliente` → `precoCliente`, `users`

### RF-01 — Abertura de chamado
- [~] Formulário e `store` existem, mas:
  - [ ] o cliente é escolhido num select; o certo é pegar de `Auth::user()->cliente_id` (o admin pode escolher)
  - [ ] a validação está no controller; mover para `StoreChamadoRequest` (descrição com no mínimo 20 caracteres)
  - [ ] usar `ChamadoService` com `DB::transaction()`
  - [x] criar o primeiro registro em `historico_slas` com status `ABERTO`
- [~] Listagem (`index`): existe, mas sem filtro por cliente, sem busca e sem filtro por status

### RF-02 — Triagem (matriz GUT)
Decisão de fluxo: o **cliente** informa só a urgência (`criticidade_cliente`) ao abrir; o **Márcio** faz a matriz GUT na tela de triagem (`/chamados/{id}/triagem`, só admin).
- [x] Tela de triagem com Gravidade, Urgência e Tendência (1 a 5), pontuação ao vivo em Alpine.js
- [x] Pontuação = G × U × T (1 a 125) e badge de prioridade (Baixa ≤ 24, Média 25–60, Alta 61–99, Crítica ≥ 100)
- [x] Gravar em `chamado_triagens` (`TriagemService`), corrigir o tipo e escolher o próximo status (`EM_TRIAGEM`, `EM_ANALISE`, `PENDENTE_CLIENTE`, `CANCELADO`)
- [x] Fila ordenada por pontuação GUT no painel do Márcio

### RF-03 — Anexos
- [ ] Upload de `.png`, `.jpg`, `.jpeg`, `.pdf`, `.txt`, `.log` e `.zip`, com até 10 MB cada
- [ ] Gravar em `storage/app/public/chamados/{id}` e registrar em `chamado_anexos`
- [ ] Download seguro, respeitando a policy

### RF-04 — Pré-orçamento automático (problema 2)
- [ ] Catálogo de horas estimadas por tipo de chamado, separadas em desenvolvimento, QA e planejamento (precisa de tabela/migration nova)
- [ ] Faixa de preço por cliente (`preco_clientes`), com CRUD no admin
- [ ] `OrcamentoService`: cria o orçamento ao abrir o chamado (horas × valor_hora por componente)
- [ ] Guardar o detalhamento por componente (hoje `orcamentos` só tem `valor_total`)
- [ ] Tela para o Márcio ajustar os valores antes de enviar
- [ ] Status do orçamento: pendente, enviado, aprovado, recusado
- [ ] Exportar o orçamento em PDF, com Dev, QA e Planejamento separados (jsPDF no front ou dompdf no Laravel)

### RF-05 — SLA
- [x] `SlaService`: grava em `historico_slas` toda vez que o status muda
- [x] Pausar a contagem enquanto o chamado está em `PENDENTE_CLIENTE`
- [x] Calcular o tempo líquido de atendimento
- [ ] Prazo de SLA (por tipo de chamado ou por prioridade) e alerta de atraso — falta o Márcio definir os prazos
- [x] Preencher os marcos `inicio_/fim_` de planejamento, dev e QA e o `fechado_em`

### RF-06 — Detalhe do chamado e painel do Márcio
- [~] `chamados.show` mostra os dados gerais e a descrição
  - [x] linha do tempo do SLA
  - [ ] lista de anexos
  - [x] resumo GUT
  - [ ] orçamento vinculado
  - [x] badge de status com cor por status (`x-status-badge`, `x-prioridade-badge`)
- [~] Ações do admin: trocar status e triar feitos; falta editar o chamado
- [x] Dashboard (`/dashboard`, só admin): aguardando triagem, fila por GUT, contagem por status e prioridade
- [ ] Cadastro de clientes (perfil + faixa de preço), com consulta da base de preços durante o atendimento
- [ ] Histórico de chamados por cliente

### Bot de triagem (problema 1 e diferencial do projeto)
- [ ] Serviço Node (Fastify) para o bot de triagem
- [ ] NLP.js para classificar a intenção e o tipo de chamado a partir do texto do cliente
- [ ] Chat em tempo real no site (Socket.io)
- [ ] Endpoint de API no Laravel para o bot criar o chamado já triado (com autenticação entre os serviços)
- [ ] Gravar a conversa do bot como histórico do atendimento no chamado

### Requisitos não funcionais
- [x] `@csrf` nos formulários e rotas protegidas com `auth`
- [ ] Layout responsivo (mobile-first) revisado em todas as telas
- [ ] Bloquear upload de executáveis (`.php`, `.sh`, `.exe`)
- [ ] Rollback completo se a gravação de anexos ou da triagem falhar
- [ ] Proteção de dados conforme a LGPD (tarefa do Rafael, etapa 5)
- [ ] Deploy em produção/homologação com HTTPS

### Testes
- [x] Acesso à tela admin
- [x] Triagem, SLA (com pausa) e painel (`tests/Feature/TriagemSlaTest.php`)
- [ ] `tests/Feature/ChamadoTest.php`: validação de anexos, isolamento entre clientes
- [ ] Testes do cálculo de orçamento

---

## Entregas do projeto de extensão (fora do código)

- [x] Etapas 1 a 4: equipe, parceiro (OiCram), problemas, pesquisa de tecnologias e ideia aprovada
- [~] Etapa 5: planejamento feito; corrigir as tarefas que citam Java/Spring Boot e Next.js, porque o projeto usa Laravel + Blade
- [ ] Etapa 6: link da pasta do projeto, vídeo de demonstração, evidências de produção por integrante, registro das validações com o Márcio (bot, preços, segurança, core, clientes, PDF, manual)
- [ ] Manual de uso do painel administrativo (Vitor)
- [ ] Etapa 7: plano de melhorias com as tarefas "Roteiro do vídeo", "Gravação do vídeo", "Edição do vídeo" e "Publicação do vídeo"
- [ ] Etapa 8: entrega ao parceiro, roteiro do vídeo e vídeo final com o depoimento do Márcio, postado no Instagram marcando @extensaoup e @mestrerafael47 (sem marca da Universidade Positivo, com os integrantes creditados)
