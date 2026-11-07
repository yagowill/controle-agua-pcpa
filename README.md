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
| Produção | Oracle Cloud Always Free + Docker Compose + Caddy |

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

## 🚀 Deploy de Produção (Oracle Cloud Always Free + Caddy)

O repositório inclui `compose.prod.yaml` com três serviços:

| Serviço | Função |
| :--- | :--- |
| `app` | Imagem de produção (assets compilados, OPcache, vendor sem dev) |
| `postgres` | PostgreSQL 17 sem porta pública |
| `caddy` | Reverse proxy com HTTPS automático (Let's Encrypt) |

### Passo a passo resumido

1. **Criar a VM** na Oracle Cloud (Always Free, Ampere A1, Ubuntu 24.04 ARM).
2. **Abrir as portas** `80` e `443` na Security List/VCN e no firewall da VM.
3. **Instalar Docker e Compose** na VM.
4. **Apontar o domínio** (A/AAAA) para o IP público da VM.
5. **Clonar o repositório** e criar o ambiente:

```bash
cp .env.production.example .env.production
# editar APP_DOMAIN, APP_URL, APP_KEY, senhas e demais variáveis
docker compose -f compose.prod.yaml up -d --build
```

6. **Gerar a APP_KEY** (se ainda não existir) e colá-la em `.env.production`:

```bash
# O --entrypoint php ignora a validação de APP_KEY do entrypoint de produção.
docker compose -f compose.prod.yaml run --rm --entrypoint php app artisan key:generate --show
```

7. **Verificar**:

```bash
docker compose -f compose.prod.yaml ps
curl -fsS https://SEU-DOMINIO/up
```

### Notas importantes

* `APP_DOMAIN` deve ser o domínio público usado pelo Caddy.
* `APP_URL` deve usar `https://`.
* `APP_FORCE_HTTPS=true` e `SESSION_SECURE_COOKIE=true`.
* As migrations são aplicadas automaticamente no start.
* O PostgreSQL não publica portas no host.
* **Backups são responsabilidade da operação.** O free tier da Oracle não inclui backup automático.
* Para dados reais, avalie os requisitos institucionais de LGPD, SLA e retenção.
* O mesmo `compose.prod.yaml` funciona em qualquer VPS/VM com Docker. O entrypoint também respeita a variável `PORT`, permitindo uso em plataformas como Render com um PostgreSQL externo (Neon/Aiven).

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
compose.prod.yaml           # Produção
```

---

## 📚 Documentação

* [`AGENTS.md`](AGENTS.md) — regras de negócio, arquitetura e convenções para agentes de IA.
* [`CLAUDE.md`](CLAUDE.md) — guia rápido para o Claude.
* [`specs/feature.md`](specs/feature.md) — especificação funcional detalhada.

---

## 📄 Licença

MIT.
