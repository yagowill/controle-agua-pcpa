# 🤖 AGENTS.md — SCEA Sistema de Controle de Entrega de Água (PCPA)

Este ficheiro contém as diretrizes técnicas, regras de negócio e padrões arquiteturais que **devem ser rigorosamente seguidos** por agentes de IA e desenvolvedores ao analisar, refatorar ou gerar código para este repositório.

---

## 📌 1. Visão Geral do Projeto

* **Nome do Sistema:** SCEA — Sistema de Controle de Entrega de Água
* **Cliente / Contexto:** Polícia Civil do Estado do Pará (PCPA) — Belém/PA
* **Objetivo:** Digitalizar o processo de recebimento de garrafões vazios e entrega de garrafões cheios, substituindo talões físicos de papel por comprovantes digitais com QR Code e validação estrita anti-reuso.
* **Arquitetura:** Monólito moderno (SPA) com Laravel + Inertia.js + Vue 3. Não existe API REST separada.

---

## 🛠️ 2. Stack Tecnológica Obrigatória

| Camada | Tecnologia | Detalhes / Regras |
| :--- | :--- | :--- |
| **Backend** | PHP 8.4 / Laravel 13 | Controllers, Form Requests, Migrations, Eloquent ORM e Services. |
| **Bridge** | Inertia.js 2 | Páginas em `resources/js/Pages`, sem endpoints REST dedicados. |
| **Frontend** | Vue.js 3 (Composition API) | Utilizar `<script setup>` e reatividade nativa. |
| **UI Kit** | Vuetify 4 | Componentes `v-card`, `v-btn`, `v-form`, `v-data-table`, etc. |
| **Estilos** | Tailwind CSS 3 + PostCSS | O Tailwind é usado em conjunto com o Vuetify; não remover as regras de compatibilidade de `resources/css/app.css`. |
| **Banco de Dados** | PostgreSQL 17 | Desenvolvimento via Docker Compose; produção em PostgreSQL 17. |
| **Testes** | PHPUnit 12 / SQLite `:memory:` | O ambiente de testes é isolado e nunca usa o PostgreSQL de desenvolvimento. |
| **PDF** | `dompdf/dompdf` | Relatórios em PDF com gráficos SVG embutidos como data URI. |
| **Planilhas** | `phpoffice/phpspreadsheet` | Exportação XLSX (resumo, séries, setores e registros). |
| **Infra local** | Docker + Docker Compose | Serviços `app`, `vite` e `postgres`. |
| **Produção** | Fly.io (Machine Docker, `shared-cpu-1x` 512 MB, região `gru`) + Neon (PostgreSQL 17 em `aws-sa-east-1`) | Configuração em `fly.toml`; HTTPS e domínio `*.fly.dev` fornecidos pelo Fly.io. |
| **Produção alternativa** | Oracle Cloud Always Free + Docker Compose + Caddy | HTTPS automático via Let's Encrypt (exige CNPJ institucional). |
| **Versionamento** | Git / GitHub | Conventional Commits em inglês (ex: `feat:`, `fix:`). |

---

## 🏗️ 3. Estrutura Arquitetural

```text
├── app/
│   ├── Exports/
│   │   └── DeliveryReportSpreadsheet.php   # Geração do XLSX
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/UserController.php    # Gestão de usuários (admin)
│   │   │   ├── Auth/                       # Login, logout e troca de senha
│   │   │   ├── ReportController.php        # Dashboard e exportações
│   │   │   └── WaterDeliveryController.php # Lançamentos, histórico e baixa
│   │   ├── Middleware/                     # Admin e troca obrigatória de senha
│   │   └── Requests/                       # Validação (Form Requests)
│   ├── Models/
│   │   ├── User.php
│   │   └── WaterDelivery.php
│   ├── Services/
│   │   └── DeliveryReportService.php       # Agregações e KPIs dos relatórios
│   └── Support/Charts/SvgChartRenderer.php # Gráficos SVG para o PDF
├── database/migrations/                    # Schema de usuários e entregas
├── docker/
│   ├── php/                                # Dockerfile, PHP.ini, OPcache e entrypoint
│   └── caddy/Caddyfile                     # Reverse proxy e HTTPS em produção
├── resources/
│   ├── css/app.css                         # Tailwind + ajustes do Vuetify
│   ├── js/
│   │   ├── app.js                          # Vue 3 + Vuetify + Inertia
│   │   └── Pages/                          # Páginas Inertia
│   └── views/reports/dashboard-pdf.blade.php
├── routes/
│   ├── auth.php
│   └── web.php
├── specs/feature.md                        # Especificação funcional
├── compose.yaml                            # Ambiente de desenvolvimento
├── compose.prod.yaml                       # Produção alternativa (app + postgres + caddy)
├── fly.toml                                # Deploy principal no Fly.io + Neon
├── .github/workflows/fly-deploy.yml        # Deploy contínuo via GitHub Actions
├── .env.example                            # Variáveis do ambiente local
├── .env.production.example                 # Variáveis da produção com Caddy
└── .env.fly.example                        # Referência das variáveis do Fly.io + Neon
```

### 3.1 Fluxo de requisição

1. A rota é definida em `routes/web.php` e protegida por `auth` e `require.password.change`.
2. O Controller valida a entrada (Form Request ou `$request->validate()`).
3. A regra de negócio é executada no Controller ou em `App\Services`.
4. A resposta Inertia renderiza uma página em `resources/js/Pages`.

---

## ⚠️ 4. Regras de Negócio Críticas (Não Alterar sem Autorização)

1. **Validação Estrita Anti-Reuso (`redeem`):**
   * Uma ordem com o status `CONCLUIDO` **nunca** pode ser resgatada novamente.
   * Tentativas de reuso devem retornar uma mensagem de erro indicando a data/hora exata do resgate prévio e o nome do servidor responsável.

2. **Tipos de Operação:**
   * **`PADRAO` (2 Etapas):** Registra o garrafão seco com status `DISPONIVEL` e calcula a previsão de entrega (próxima segunda-feira ou quinta-feira).
   * **`BATE_VOLTA` (Troca Imediata):** Registra a entrada e a saída simultaneamente, definindo o status diretamente como `CONCLUIDO`.

3. **Formato do Código da Ordem:**
   * O código da ordem deve seguir estritamente o padrão `PCPA-YYYY-XXXX` (ex: `PCPA-2026-0001`).

4. **Controle de Acesso e Autenticação:**
   * Rotas de operações e relatórios devem ser protegidas por `auth` e `require.password.change`.
   * Usuários com perfil `admin` possuem acesso total (relatórios analíticos e gestão de usuários).
   * Usuários com perfil `atendente` possuem acesso restrito aos fluxos de recebimento, baixa, histórico e consulta operacional.
   * O login é feito por `username`. `email` e `matricula` são opcionais.
   * Todo usuário novo nasce com `must_change_password = true` e deve trocar a senha no primeiro acesso.
   * Utilizar `Hash::make` e rate limiting nas tentativas de login.

5. **Relatórios:**
   * O consumo considera **apenas ordens com status `CONCLUIDO`**.
   * Histórico e relatórios são páginas separadas (`/entregas/historico` e `/entregas/relatorios`).
   * Exportações disponíveis: PDF, XLSX e CSV.

---

## 🐳 5. Ambiente de Desenvolvimento (Docker)

### 5.1 Serviços

| Serviço | Imagem | Porta host | Observação |
| :--- | :--- | :--- | :--- |
| `app` | build `development` (`php:8.4-apache`) | `8000` | Laravel + Apache |
| `vite` | `node:22-alpine` | `5173` | HMR do Vue/Tailwind |
| `postgres` | `postgres:17-alpine` | `5433` | PostgreSQL local |

### 5.2 Comandos essenciais

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint
docker compose logs -f app
docker compose down
```

### 5.3 Regras do ambiente Docker

* O código-fonte é montado via bind mount; `vendor`, `node_modules` e `pgdata` são volumes nomeados.
* O serviço `app` sobrescreve `DB_HOST=postgres`, `DB_PORT=5432` e as credenciais locais via `environment:` no Compose.
* O `.env` do host continua válido para comandos executados fora do container (`DB_HOST=127.0.0.1`, `DB_PORT=5433`).
* O `vite` depende do diretório `vendor` (o `app.js` importa o Ziggy do Composer) e aguarda o Composer terminar.
* O entrypoint de desenvolvimento instala dependências na primeira execução, gera `APP_KEY` se necessário e aplica migrations.
* **Não** executar os testes apontando para o PostgreSQL de desenvolvimento. O `phpunit.xml` força `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:` também em `$_SERVER`, porque o Laravel dá precedência a `$_SERVER` sobre `$_ENV`.

---

## 🚀 6. Produção

### 6.1 Destino principal — Fly.io + Neon

O deploy oficial de referência é definido em `fly.toml` e no workflow `.github/workflows/fly-deploy.yml`:

| Recurso | Configuração | Função |
| :--- | :--- | :--- |
| App `controle-agua-pcpa` | `shared-cpu-1x`, 512 MB, `gru` | Imagem Docker de produção, HTTPS e domínio `*.fly.dev` |
| Neon PostgreSQL | `free`, `aws-sa-east-1` | Banco externo, ligado por `DB_URL` com `DB_SSLMODE=require` |

Regras:

* O Fly.io constrói o stage `production` do `docker/php/Dockerfile`, definido em `build-target` no `fly.toml`.
* `dockerfile = 'docker/php/Dockerfile'`, `build-target = 'production'`, `internal_port = 8080`, healthcheck `/up` e `auto_stop_machines = 'stop'`.
* `PORT=8080` é definido em `[env]` para que o entrypoint reconfigure o Apache para a porta interna.
* Segredos (`APP_KEY` e `DB_URL`) são definidos com `fly secrets set` e não ficam versionados.
* `APP_URL` é resolvida por `FLY_APP_NAME` (fallback em `config/app.php`); ao usar domínio próprio, definir `APP_URL` explicitamente.
* O entrypoint aceita `DB_URL` (banco externo) e respeita `PORT`.
* Migrations e caches (`config`, `route`, `view`) são aplicados no start.
* `RUN_SEEDER=true` cria o administrador inicial; com o banco de produção reaproveitado, manter `false` e usar `fly ssh console -C "php artisan db:seed --force"` apenas em bases novas.
* A Machine para por inatividade (cold start) e reinicia no primeiro pedido; o Neon free não tem backup automático.

### 6.2 Destino alternativo — Oracle Cloud + Caddy

Para quem tem CNPJ institucional, o repositório mantém o `compose.prod.yaml`:

| Serviço | Função |
| :--- | :--- |
| `app` | Imagem de produção (assets compilados, `vendor` sem dev, OPcache otimizado) |
| `postgres` | PostgreSQL 17 sem porta exposta no host |
| `caddy` | Reverse proxy com HTTPS automático (Let's Encrypt) |

Regras do deploy com Caddy:

* `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` obrigatória.
* `APP_DOMAIN` deve apontar para o domínio público configurado no DNS.
* `APP_URL` deve usar `https://`.
* `APP_FORCE_HTTPS=true` e `SESSION_SECURE_COOKIE=true`.
* O `postgres` não deve publicar portas no host.
* Backups do PostgreSQL são responsabilidade da operação (não há backup automático no plano gratuito).

### 6.3 Regras comuns

* Migrations são aplicadas no entrypoint (`php artisan migrate --force`).
* O entrypoint respeita a variável `PORT` para compatibilidade com o Fly.io.
* Em produção com dados reais, avaliar requisitos de LGPD/SLA institucionais antes de usar planos gratuitos ou de baixo custo.

---

## 💻 7. Convenções de Código para Agentes de IA

* **Nomenclatura no Código:**
  * Funções, variáveis, métodos, classes, tabelas e colunas **devem ser escritos em inglês** (ex: `WaterDelivery`, `order_number`, `calculateNextDeliveryDate`).
  * **Comentários de código e documentação:** Devem ser escritos em **português**.
  * Rotas e nomes de páginas podem permanecer em português (`/entregas/nova`, `Relatorios.vue`), pois fazem parte da interface do utilizador.
* **Padrões Laravel:**
  * Validações em `Form Requests` ou `$request->validate()`.
  * Datas tratadas via `Carbon\Carbon`.
  * Evitar SQL bruto; usar Eloquent/Query Builder (compatível com PostgreSQL).
  * Não reintroduzir dependência de MySQL; o banco oficial é PostgreSQL.
* **Padrões Vue 3 / Vuetify:**
  * Preferir `<script setup>`.
  * Manter responsividade para celulares e desktops.
  * Preservar o tema institucional definido em `resources/js/app.js`.
* **Qualidade:**
  * Executar `php artisan test` e `./vendor/bin/pint` antes de concluir alterações.
  * Não alterar `phpunit.xml` para apontar para PostgreSQL.

---

## 🎨 8. Padrão de Identidade Visual (PCPA)

* **Azul Institucional (`primary`):** `#2b6cb0` — botões principais, destaques e ações ativas.
* **Grafite Escuro (`secondary`):** `#212529` — cabeçalho/Top Bar estilo SRH.
* **Cinza de Fundo (`background`):** `#f8f9fa` — fundo geral da aplicação.
* **Bordas e Superfícies:** cartões brancos com bordas em `#ced4da`.
* **Fonte e contraste:** manter legibilidade em ambientes de almoxarifado e em dispositivos móveis.

---

## ✅ 9. Checklist antes de concluir uma alteração

1. A regra anti-reuso continua intacta?
2. O código da ordem segue `PCPA-YYYY-XXXX`?
3. As rotas novas estão sob `auth` e `require.password.change`?
4. O relatório considera apenas `CONCLUIDO`?
5. `docker compose up -d` e `docker compose exec app php artisan test` passam?
6. `./vendor/bin/pint --test` passa?
7. O `README.md` e `specs/feature.md` continuam coerentes com a alteração?
8. O `fly.toml` continua válido (`flyctl config validate`) e o healthcheck `/up` responde no Fly.io?
