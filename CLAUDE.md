# CLAUDE.md — Guia rápido do SCEA/PCPA

Este repositório contém o **SCEA — Sistema de Controle de Entrega de Água** da Polícia Civil do Estado do Pará (PCPA).

> **Leia `AGENTS.md` antes de alterar o projeto.** Ele contém as regras de negócio críticas, a arquitetura e as convenções obrigatórias.

---

## Stack atual

* **PHP 8.4 / Laravel 13**
* **Inertia.js 2 + Vue 3 (`<script setup>`) + Vuetify 4**
* **Tailwind CSS 3** (em conjunto com o Vuetify)
* **PostgreSQL 17** (desenvolvimento e produção)
* **Dompdf** (PDF) e **PhpSpreadsheet** (XLSX)
* **Docker Compose** para desenvolvimento
* **Oracle Cloud Always Free + Caddy** para produção

---

## Estrutura principal

```text
app/Http/Controllers/WaterDeliveryController.php  # lançamentos, histórico e baixa
app/Http/Controllers/ReportController.php         # dashboard e exportações
app/Services/DeliveryReportService.php            # agregações dos relatórios
app/Exports/DeliveryReportSpreadsheet.php         # geração do XLSX
app/Support/Charts/SvgChartRenderer.php           # gráficos SVG do PDF
resources/js/Pages/                              # páginas Inertia/Vue
routes/web.php                                   # rotas protegidas
docker/php/                                      # imagem, PHP.ini, OPcache e entrypoint
compose.yaml                                     # ambiente de desenvolvimento
compose.prod.yaml                                # produção com Caddy
specs/feature.md                                 # especificação funcional
```

---

## Comandos do dia a dia

```bash
# Subir o ambiente completo
docker compose up -d --build

# Aplicar migrations / popular dados
docker compose exec app php artisan migrate --seed

# Executar a suíte de testes (SQLite em memória)
docker compose exec app php artisan test

# Formatação
docker compose exec app ./vendor/bin/pint

# Logs
docker compose logs -f app

# Parar
docker compose down
```

A aplicação fica em `http://localhost:8000` e o Vite em `http://localhost:5173`.
O PostgreSQL local escuta em `127.0.0.1:5433`.

---

## Regras que não podem ser quebradas

1. Ordem com status `CONCLUIDO` **nunca** pode ser resgatada novamente (trava anti-reuso).
2. Código da ordem no formato `PCPA-YYYY-XXXX`.
3. `PADRAO` gera `DISPONIVEL`; `BATE_VOLTA` gera `CONCLUIDO`.
4. Login por `username`; senha inicial obriga troca (`must_change_password`).
5. Relatórios consideram apenas ordens `CONCLUIDO`.
6. Histórico (`/entregas/historico`) e relatórios (`/entregas/relatorios`) são páginas separadas.
7. Identificadores em inglês; comentários e documentação em português.

---

## Produção

O deploy de referência usa `compose.prod.yaml` em uma VM Oracle Cloud Always Free:

```bash
cp .env.production.example .env.production
# editar APP_DOMAIN, APP_KEY, APP_URL, senhas e credenciais
docker compose -f compose.prod.yaml up -d --build
```

O Caddy emite e renova o certificado HTTPS automaticamente. O PostgreSQL não expõe porta pública.

Consulte `README.md` para o passo a passo completo e `AGENTS.md` para as convenções de código.
