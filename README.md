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
| Produção | Fly.io (Machine `shared-cpu-1x`, 512 MB, `gru`) + Neon (PostgreSQL, `aws-sa-east-1`) |
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

## 🚀 Deploy de Produção (Fly.io + Neon)

O deploy de referência usa o **Fly.io** para a aplicação, na região `gru` (São Paulo), e o **Neon** para o PostgreSQL, na região `aws-sa-east-1` (também São Paulo). O ficheiro `fly.toml` descreve a Machine, o healthcheck `/up` e as variáveis não secretas.

| Recurso | Configuração | Observações |
| :--- | :--- | :--- |
| Fly.io Machine | `shared-cpu-1x`, 512 MB, `gru` | Para por inatividade e volta no primeiro pedido (cold start) |
| Neon PostgreSQL | `free`, `aws-sa-east-1` | ~1 GB; backups e restore são responsabilidade da operação |

> O Fly.io não tem plano gratuito para organizações novas. Contas pessoais com fatura mensal abaixo de US$ 5 têm o valor dispensado. Uma Machine de 512 MB em `gru`, ligada 24 horas, fica na ordem de US$ 4–5/mês; com auto-stop e pouco tráfego, o custo tende a permanecer dentro da isenção. Confirme os preços em <https://fly.io/docs/about/pricing/>.

### Passo 1 — Confirmar o banco no Neon

O projeto Neon deve estar em **AWS South America (São Paulo) — `aws-sa-east-1`** para ficar próximo da Machine em `gru`. Confirme a região no painel do Neon (**Project → Region**) ou pelo hostname da connection string:

```text
postgresql://usuario:senha@ep-xxxxxx.sa-east-1.aws.neon.tech/neondb?sslmode=require
```

Copie a **connection string direta** (sem `-pooler`). Se o projeto ainda estiver noutra região, crie um novo projeto em São Paulo e restaure um dump antes de continuar:

```bash
# Exemplo com o cliente PostgreSQL 17
pg_dump --no-owner --no-acl -Fc "$OLD_DATABASE_URL" -f scea.dump
pg_restore --no-owner --no-acl -d "$NEW_DATABASE_URL" scea.dump
```

> Use a ligação direta para que migrations e PDO funcionem sem surpresas de pooling.
>
> Como o Neon é o mesmo banco, **não há cópia de dados**: o Fly.io passa a apontar para a base existente. Após o cutover, não mantenha Render e Fly.io ativos ao mesmo tempo.

### Passo 2 — Reaproveitar a APP_KEY

Reutilize a **mesma `APP_KEY` que está no Render**. Assim as sessões/cookies existentes continuam válidos. Se precisar de gerar uma nova:

```bash
docker run --rm php:8.4-cli -r 'echo "base64:".base64_encode(random_bytes(32)), PHP_EOL;'
```

> Trocar a `APP_KEY` invalida sessões e cookies existentes; evite a mudança sem necessidade.

### Passo 3 — Configurar os segredos no Fly.io

O app `controle-agua-pcpa` já está descrito no `fly.toml`. Defina os dois segredos:

```bash
fly secrets set \
  APP_KEY='base64:...' \
  DB_URL='postgresql://usuario:senha@ep-xxxxxx.sa-east-1.aws.neon.tech/neondb?sslmode=require' \
  -a controle-agua-pcpa
```

Os restantes valores (`APP_ENV`, `APP_FORCE_HTTPS`, `SESSION_SECURE_COOKIE`, `DB_SSLMODE`, `CACHE_STORE`, `PORT`, etc.) já vêm do bloco `[env]` do `fly.toml`.

### Passo 4 — Alocar IPs e fazer o deploy

```bash
# Apenas se ainda não existirem IPs para o app
fly ips allocate-v6 -a controle-agua-pcpa
fly ips allocate-v4 --shared -a controle-agua-pcpa

fly deploy --remote-only
```

O primeiro deploy constrói a imagem de produção (stage `production` do `docker/php/Dockerfile`), aplica as migrations e sobe o Apache na porta interna `8080`.

### Passo 5 — Validar

```bash
curl -fsS https://controle-agua-pcpa.fly.dev/up
fly logs -a controle-agua-pcpa
```

Abra `https://controle-agua-pcpa.fly.dev/login`. Como o banco de produção foi reaproveitado, os utilizadores existentes continuam a funcionar. Se estiver a usar uma base **nova**, crie o administrador inicial uma única vez:

```bash
fly ssh console -a controle-agua-pcpa -C "php artisan db:seed --force"
```

O seeder é idempotente e cria `yago.martins / Admin@pcpa2026`, com troca de senha obrigatória no primeiro acesso.

### Passo 6 — Domínio próprio (opcional)

```bash
fly certs add scea.pcpa.pa.gov.br -a controle-agua-pcpa
fly certs show scea.pcpa.pa.gov.br -a controle-agua-pcpa
```

Crie no DNS um `CNAME` para `controle-agua-pcpa.fly.dev` (subdomínios) ou os registos `A`/`AAAA` indicados por `fly ips list` (domínio raiz). Depois defina `APP_URL=https://scea.pcpa.pa.gov.br` no bloco `[env]` do `fly.toml` e execute `fly deploy` novamente.

### Passo 7 — Deploy contínuo no GitHub Actions

O workflow `.github/workflows/fly-deploy.yml` já está no repositório e corre a cada push para `main` ou `master`. Crie um token de deploy e guarde-o como segredo `FLY_API_TOKEN` no GitHub (**Settings → Secrets and variables → Actions**):

```bash
fly tokens create deploy -a controle-agua-pcpa -x 999999h
```

### Passo 8 — Desligar o Render (cutover)

Depois de validar o Fly.io e, se aplicável, trocar o DNS do domínio próprio:

1. No dashboard do Render, **suspenda** o web service (ou reduza para zero instâncias) para evitar duas aplicações a escrever na mesma base.
2. Mantenha o serviço suspenso por alguns dias como rollback; o Neon continua a ser o mesmo.
3. Confirme em `fly logs` que não há erros e que os fluxos de entrega, baixa e relatórios funcionam.
4. Só então elimine o serviço no Render e o app vazio `controle-agua-pcpa-tender-coral-535` (criado pelo `fly launch`), se existir.

### Operação do dia a dia

```bash
fly status -a controle-agua-pcpa              # estado da Machine
fly logs -a controle-agua-pcpa                # logs em tempo real
fly ssh console -a controle-agua-pcpa         # shell no contentor
fly apps restart controle-agua-pcpa           # reiniciar
fly scale show -a controle-agua-pcpa          # CPU/memória atuais
fly scale memory 1024 -a controle-agua-pcpa   # mais memória para relatórios
fly secrets list -a controle-agua-pcpa        # segredos (sem valores)
```

### Como o deploy funciona

* O Fly.io constrói o **stage `production`** do `docker/php/Dockerfile` (`build-target` no `fly.toml`), com assets compilados e `vendor` sem dependências de dev.
* `PORT=8080` no `[env]` e `internal_port = 8080` no `fly.toml` alinham o Apache com o proxy do Fly.io.
* O entrypoint aguarda o PostgreSQL (por `DB_URL`), aplica `php artisan migrate --force` e gera os caches `config`, `route` e `view` a cada start.
* `APP_URL` é resolvida por `FLY_APP_NAME` (`https://<app>.fly.dev`) quando não definida; `APP_FORCE_HTTPS=true` garante URLs `https`.
* Sessões, cache e fila usam o próprio PostgreSQL. O disco da Machine é efémero, mas a aplicação não guarda uploads.
* A Machine para após inatividade (`auto_stop_machines = "stop"`) e volta no primeiro pedido; o healthcheck é `/up`.
* O Neon free não tem backup automático; agende `pg_dump` ou use o histórico de restore do plano.

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
fly.toml                    # Deploy principal no Fly.io + Neon
.github/workflows/fly-deploy.yml  # Deploy contínuo via GitHub Actions
```

---

## 📚 Documentação

* [`AGENTS.md`](AGENTS.md) — regras de negócio, arquitetura e convenções para agentes de IA.
* [`CLAUDE.md`](CLAUDE.md) — guia rápido para o Claude.
* [`specs/feature.md`](specs/feature.md) — especificação funcional detalhada.

---

## 📄 Licença

MIT.
