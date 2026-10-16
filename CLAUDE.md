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
- [ ] Inicializar o Alpine.js em `resources/js/app.js` (está instalado, mas não é carregado)
- [ ] Remover as sobras do template de eventos: `EventController`, `resources/views/events/*`, `public/img/events`, `hdcevents_logo.svg`, `public/css/style.css` e `public/js/script.js`, se não forem usados

### Autenticação e controle de acesso
- [x] Login e logout (`AuthController`)
- [x] Papéis `admin` e `cliente` (spatie)
- [x] Tela admin para listar e criar usuários cliente (`/admin/usuarios`)
- [x] Teste de acesso à tela admin (`tests/Feature/AdminUsuariosAccessTest.php`)
- [ ] `ChamadoPolicy`: o cliente só vê os chamados do próprio `cliente_id`; o admin vê todos
- [ ] Editar e desativar usuários no admin

### Models Eloquent
- [x] `Chamado`, `Cliente`, `Status`, `TipoChamado`, `User`
- [ ] `ChamadoTriagem`, `ChamadoAnexo`, `Orcamento`, `HistoricoSla`, `PrecoCliente`
- [ ] Relacionamentos que faltam: `Chamado` → `triagem`, `anexos`, `orcamentos`, `historicosSla`; `Cliente` → `precoCliente`, `users`

### RF-01 — Abertura de chamado
- [~] Formulário e `store` existem, mas:
  - [ ] o cliente é escolhido num select; o certo é pegar de `Auth::user()->cliente_id` (o admin pode escolher)
  - [ ] a validação está no controller; mover para `StoreChamadoRequest` (descrição com no mínimo 20 caracteres)
  - [ ] usar `ChamadoService` com `DB::transaction()`
  - [ ] criar o primeiro registro em `historico_slas` com status `ABERTO`
- [~] Listagem (`index`): existe, mas sem filtro por cliente, sem busca e sem filtro por status

### RF-02 — Assistente de triagem (matriz GUT)
- [ ] Wizard em Alpine.js com as perguntas de Gravidade, Urgência e Tendência (1 a 5)
- [ ] Pontuação = G × U × T (1 a 125) e badge de prioridade (Baixa ≤ 24, Média 25–60, Alta 61–99, Crítica ≥ 100)
- [ ] Gravar em `chamado_triagens` (`TriagemService`)
- [ ] Fila ordenada por pontuação GUT no painel do Márcio

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
- [ ] `SlaService`: grava em `historico_slas` toda vez que o status muda
- [ ] Pausar a contagem enquanto o chamado está em `PENDENTE_CLIENTE`
- [ ] Calcular o tempo líquido de atendimento e o prazo por tipo de chamado
- [ ] Preencher os marcos `inicio_/fim_` de planejamento, dev e QA e o `fechado_em`

### RF-06 — Detalhe do chamado e painel do Márcio
- [~] `chamados.show` mostra os dados gerais e a descrição
  - [ ] linha do tempo do SLA
  - [ ] lista de anexos
  - [ ] resumo GUT
  - [ ] orçamento vinculado
  - [ ] badge de status com cor por status
- [ ] Ações do admin: trocar status, editar o chamado, cancelar
- [ ] Dashboard: fila de chamados abertos, contagem por status e prioridade
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
- [ ] `tests/Feature/ChamadoTest.php`: abertura com sucesso, cálculo GUT, validação de anexos, isolamento entre clientes
- [ ] Testes do cálculo de orçamento e do SLA (com pausa)

---

## Entregas do projeto de extensão (fora do código)

- [x] Etapas 1 a 4: equipe, parceiro (OiCram), problemas, pesquisa de tecnologias e ideia aprovada
- [~] Etapa 5: planejamento feito; corrigir as tarefas que citam Java/Spring Boot e Next.js, porque o projeto usa Laravel + Blade
- [ ] Etapa 6: link da pasta do projeto, vídeo de demonstração, evidências de produção por integrante, registro das validações com o Márcio (bot, preços, segurança, core, clientes, PDF, manual)
- [ ] Manual de uso do painel administrativo (Vitor)
- [ ] Etapa 7: plano de melhorias com as tarefas "Roteiro do vídeo", "Gravação do vídeo", "Edição do vídeo" e "Publicação do vídeo"
- [ ] Etapa 8: entrega ao parceiro, roteiro do vídeo e vídeo final com o depoimento do Márcio, postado no Instagram marcando @extensaoup e @mestrerafael47 (sem marca da Universidade Positivo, com os integrantes creditados)
