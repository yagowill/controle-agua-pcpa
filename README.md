<div align="center">

# 🚰 SCEA — Sistema de Controle de Entrega de Água

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-2.x-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Vuetify](https://img.shields.io/badge/Vuetify-4.x-1867C0?style=for-the-badge&logo=vuetify&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

<p align="center">
  <b>Aplicação Web corporativa para modernização e gestão do fluxo de distribuição de garrafões de água do Almoxarifado da Polícia Civil do Estado do Pará (PCPA).</b>
</p>

</div>

---

## 📌 Sobre o Projeto

O **SCEA (Sistema de Controle de Entrega de Água)** foi desenvolvido para substituir processos manuais em papel e planilhas por uma solução digital auditável, ágil e segura. O sistema automatiza o recebimento de garrafões vazios, o fornecimento de garrafões cheios, a emissão de protocolos com QR Code e o controlo estatístico do consumo por setor e delegacia.

### 🎯 Principais Objetivos

* **Eliminação do papel:** comprovantes digitais (PNG/WhatsApp) com código QR único.
* **Segurança anti-reuso:** bloqueio estrito de dupla retirada com o mesmo comprovante.
* **Agilidade no “bate volta”:** registo em etapa única para trocas imediatas.
* **Rastreabilidade:** histórico completo e relatórios de consumo mensal e anual.

---

## 🚀 Stack Tecnológica

Arquitetura de **monólito moderno (SPA)**, sem API REST separada.

| Camada | Tecnologia |
| :--- | :--- |
| Backend | PHP 8.4 / Laravel 13 |
| Bridge SPA | Inertia.js 2 |
| Frontend | Vue.js 3 (Composition API / `<script setup>`) |
| UI | Vuetify 4 + Tailwind CSS 3 |
| Banco de dados | PostgreSQL 17 |
| PDF | `dompdf/dompdf` |
| Planilhas | `phpoffice/phpspreadsheet` |
| Ambiente local | Docker Compose (`app`, `vite`, `postgres`) |
| Produção | Render (Docker, plano free) + Neon (PostgreSQL free) |
| Produção alternativa | Oracle Cloud Always Free + Docker Compose + Caddy |

---

## 🛠️ Regras de Negócio e Funcionalidades

### 1. Fluxo Padrão (2 Etapas)

* **Entrada:** registo do garrafão seco ➔ protocolo `PCPA-YYYY-XXXX` ➔ previsão de entrega (próxima segunda ou quinta) ➔ status `DISPONIVEL`.
* **Saída:** leitura do QR Code ➔ registo do recebedor e atendente ➔ status `CONCLUIDO`.

### 2. Fluxo Bate Volta

* Registo unificado de entrada e saída ➔ status `CONCLUIDO` imediatamente.

### 3. Trava Anti-Reuso

* Protocolo `CONCLUIDO` não pode ser resgatado novamente.
* O sistema exibe data, hora e nome do atendente responsável pela baixa anterior.

### 4. Histórico e Relatórios

* **Histórico** (`/entregas/historico`): filtros por ordem, setor, servidor e status.
* **Relatórios** (`/entregas/relatorios`): consumo mensal/anual, KPIs, ranking de setores, variação e gráficos.
* Apenas ordens `CONCLUIDO` entram nos cálculos de consumo.
* Exportações em **PDF**, **XLSX** e **CSV**.

### 5. Usuários e Acesso

* Login por `username`.
* Perfis `admin` e `atendente`.
* Troca obrigatória de senha no primeiro acesso.
* Gestão de usuários (criar, redefinir senha, ativar/desativar e remover) exclusiva do `admin`.

---

## 🎨 Identidade Visual (PCPA)

* **Azul institucional (`primary`):** `#2b6cb0` — botões e destaques.
* **Grafite escuro (`secondary`):** `#212529` — Top Bar.
* **Cinza de fundo (`background`):** `#f8f9fa` — fundo geral.
* **Bordas:** `#ced4da` em cartões e campos.

---

## 🐳 Desenvolvimento com Docker

### Pré-requisitos

* Docker Desktop / Docker Engine com Compose v2.
* Git.

> Não é necessário instalar PHP, Composer, Node.js ou PostgreSQL no host.

### Primeira execução

```bash
git clone https://github.com/SEU-USUARIO/controle-agua-pcpa.git
cd controle-agua-pcpa

cp .env.example .env

docker compose up -d --build
```

Aguarde o build e a instalação automática das dependências. Depois aceda a:

* **Aplicação:** <http://localhost:8000>
* **Vite/HMR:** <http://localhost:5173>
* **Healthcheck:** <http://localhost:8000/up>
* **PostgreSQL (host):** `127.0.0.1:5433` — banco `scea`, utilizador `scea`, senha `scea`

### Comandos úteis

```bash
# Estado dos serviços
docker compose ps

# Logs
docker compose logs -f app
docker compose logs -f vite

# Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan tinker

# Testes (SQLite em memória)
docker compose exec app php artisan test

# Formatação
docker compose exec app ./vendor/bin/pint

# Parar
docker compose down

# Parar e apagar volumes (banco local incluído)
docker compose down -v
```

### Como o ambiente funciona

* O código é montado por bind mount em `/var/www/html`.
* `vendor`, `node_modules` e `pgdata` ficam em volumes nomeados.
* O serviço `app` sobrescreve `DB_HOST=postgres` e `DB_PORT=5432`; o `.env` do host usa `127.0.0.1:5433` para comandos fora do container.
* O entrypoint instala o Composer na primeira execução, gera `APP_KEY` se necessário e aplica migrations.
* O `vite` aguarda o `vendor` (o `app.js` importa o Ziggy do Composer) antes de iniciar.

### Execução sem Docker (opcional)

Requisitos: PHP 8.4, Composer, Node.js 22 e PostgreSQL 17.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

Ajuste `DB_HOST`/`DB_PORT` no `.env` para o PostgreSQL local.

---

## 🚀 Deploy de Produção (Render + Neon)

O deploy de referência é 100% gratuito, sem CNPJ e sem cartão: a aplicação corre no **Render** (web service Docker, `plan: free`) e o PostgreSQL fica no **Neon** (`free`, ~1 GB, sem expiração).

| Recurso | Plano free | Limitações |
| :--- | :--- | :--- |
| Render web service | 512 MB de RAM | Dorme após ~15 min de inatividade; cold start de ~50 s |
| Neon PostgreSQL | ~1 GB, 100 CU-hours | Sem backup automático; histórico de restore curto |

O blueprint `render.yaml` já descreve o serviço, o healthcheck `/up` e todas as variáveis de ambiente.

### Passo 1 — Criar o banco no Neon

1. Crie a conta em <https://neon.com> (não pede cartão).
2. Crie um projeto e copie a **connection string direta** (sem `-pooler`):

```text
postgresql://usuario:senha@ep-xxxxxx.us-east-2.aws.neon.tech/neondb?sslmode=require
```

> Use a ligação direta para que as migrations e o PDO funcionem sem surpresas de pooling.

### Passo 2 — Gerar a APP_KEY

```bash
# Gera 32 bytes aleatórios no formato esperado pelo Laravel.
docker run --rm php:8.4-cli -r 'echo "base64:".base64_encode(random_bytes(32)), PHP_EOL;'
```

Copie o valor devolvido no formato `base64:...`. (Com PHP instalado no host, o equivalente é `php -r '...'` com o mesmo código.)

### Passo 3 — Criar o Blueprint no Render

1. Crie a conta em <https://render.com> (não pede cartão).
2. **New → Blueprint** e selecione o repositório.
3. O Render deteta o `render.yaml` e propõe o serviço `scea-pcpa` na região `virginia`.
4. Confirme a criação.

### Passo 4 — Preencher os segredos

No dashboard do Render, o serviço pede os valores marcados com `sync: false`:

| Variável | Valor |
| :--- | :--- |
| `APP_KEY` | `base64:...` gerado no passo 2 |
| `DB_URL` | connection string direta do Neon |

Todas as outras variáveis (`APP_ENV`, `APP_FORCE_HTTPS`, `SESSION_SECURE_COOKIE`, `DB_SSLMODE`, `CACHE_STORE`, etc.) já vêm do `render.yaml`.

### Passo 5 — Criar o administrador inicial

No primeiro deploy, altere `RUN_SEEDER` para `true` no dashboard. O entrypoint aplica as migrations e executa o `AdminUserSeeder`, que cria:

```text
yago.martins / Admin@pcpa2026
```

O primeiro acesso obriga à troca de senha. Depois de entrar e trocar a senha, volte `RUN_SEEDER` para `false`.

> Sem shell no plano free, esta é a forma de criar o primeiro administrador. Se preferir, o seeder é idempotente e ignora utilizadores já existentes.

### Passo 6 — Validar

```bash
curl -fsS https://scea-pcpa.onrender.com/up
```

Depois abra `https://scea-pcpa.onrender.com/login` e entre com o utilizador criado. Para usar um domínio próprio, adicione-o em **Settings → Custom Domains** — o Render emite o certificado TLS automaticamente.

### Como o deploy funciona

* O Render constrói o **último stage** do `docker/php/Dockerfile` (`production`), com assets compilados e `vendor` sem dependências de dev.
* O entrypoint respeita a variável `PORT` e aguarda o PostgreSQL — por `DB_URL` quando definida ou por `DB_HOST`/`DB_PORT` no ambiente local.
* As migrations são aplicadas a cada deploy (`php artisan migrate --force`).
* `config:cache`, `route:cache` e `view:cache` são gerados no start.
* `APP_URL` é resolvida por `RENDER_EXTERNAL_URL`; `APP_FORCE_HTTPS=true` garante URLs `https`.
* Sessões, cache e fila usam o próprio PostgreSQL, porque o Render free não oferece disco persistente.
* O healthcheck configurado no Blueprint é `/up`.

### Alternativa: Oracle Cloud Always Free + Caddy

A Oracle exige **CNPJ institucional** no fluxo Corporate. Se houver autorização formal da PCPA, o repositório mantém o `compose.prod.yaml` com três serviços:

| Serviço | Função |
| :--- | :--- |
| `app` | Imagem de produção (assets compilados, OPcache, vendor sem dev) |
| `postgres` | PostgreSQL 17 sem porta pública |
| `caddy` | Reverse proxy com HTTPS automático (Let's Encrypt) |

```bash
cp .env.production.example .env.production
# editar APP_DOMAIN, APP_URL, APP_KEY, senhas e demais variáveis
docker compose -f compose.prod.yaml up -d --build
```

Notas: `APP_DOMAIN` deve apontar para o DNS público, `APP_URL` deve usar `https://`, e os backups do PostgreSQL são responsabilidade da operação. O mesmo compose funciona em qualquer VPS/VM com Docker.

---

## 🧪 Testes e Qualidade

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
```

* A suíte usa SQLite `:memory:` e não toca no PostgreSQL de desenvolvimento.
* O `phpunit.xml` força as variáveis de teste também em `$_SERVER`, porque o Laravel dá precedência a `$_SERVER` sobre `$_ENV`.
* Cobertura atual: fluxos de entrega, anti-reuso, autenticação, gestão de usuários, dashboard e exportações.

---

## 📁 Estrutura Principal

```text
app/
├── Exports/DeliveryReportSpreadsheet.php
├── Http/Controllers/
│   ├── Admin/UserController.php
│   ├── Auth/
│   ├── ReportController.php
│   └── WaterDeliveryController.php
├── Models/{User,WaterDelivery}.php
├── Services/DeliveryReportService.php
└── Support/Charts/SvgChartRenderer.php

docker/
├── php/{Dockerfile,php.ini,opcache*.ini,entrypoint.sh,vhost.conf}
└── caddy/Caddyfile

resources/js/Pages/         # Páginas Inertia/Vue
routes/{web,auth}.php
specs/feature.md            # Especificação funcional
compose.yaml                # Desenvolvimento
compose.prod.yaml           # Produção alternativa (Oracle + Caddy)
render.yaml                 # Blueprint do Render + Neon
```

---

## 📚 Documentação

* [`AGENTS.md`](AGENTS.md) — regras de negócio, arquitetura e convenções para agentes de IA.
* [`CLAUDE.md`](CLAUDE.md) — guia rápido para o Claude.
* [`specs/feature.md`](specs/feature.md) — especificação funcional detalhada.

---

## 📄 Licença

MIT.
