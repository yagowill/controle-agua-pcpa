<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# 🤖 AGENTS.md — SCEA Sistema Controle de Entrega de Água (PCPA)

Este ficheiro contém as diretrizes técnicas, regras de negócio e padrões arquiteturais que **devem ser rigorosamente seguidos** por agentes de IA e desenvolvedores ao analisar, refatorar ou gerar código para este repositório.

---

## 📌 1. Visão Geral do Projeto
* **Nome do Sistema:** SCEA — Sistema de Controle de Entrega de Água
* **Cliente / Contexto:** Polícia Civil do Estado do Pará (PCPA) — Belém/PA
* **Objetivo:** Digitalizar o processo de recebimento de garrafões vazios e entrega de garrafões cheios, substituindo talões físicos de papel por comprovantes digitais com QR Code e validação estrita anti-reuso

---

## 🛠️ 2. Stack Tecnológica Obrigatória

| Camada | Tecnologia | Detalhes / Regras |
| :--- | :--- | :--- |
| **Backend** | PHP 8.2+ / Laravel 11 | Utilizar Controllers, Migrations, Eloquent ORM e Form Requests. |
| **Bridge** | Inertia.js | Arquitetura de Monólito Moderno (SPA sem API REST separada). |
| **Frontend** | Vue.js 3 (Composition API) | Utilizar `<script setup>` e reacção nativa. |
| **UI Kit** | Vuetify 3 | Usar componentes Vuetify (`v-card`, `v-btn`, `v-form`, `v-data-table`) com o tema institucional. |
| **Banco de Dados** | MySQL (Local) / PostgreSQL (Prod) | Nomes de colunas e tabelas sempre em inglês e snake_case. |
| **Versionamento** | Git / GitHub | Padrão *Conventional Commits* em inglês (ex: `feat:`, `fix:`). |

---

## 🎨 3. Padrão de Identidade Visual (SRH / PCPA)

Qualquer alteração na interface do utilizador deve respeitar a paleta oficial configurada no Vuetify:

* **Azul Institucional (`primary`):** `#2b6cb0` (Utilizado em botões principais, destaques e acções ativas).
* **Grafite Escuro (`secondary`):** `#212529` (Utilizado no cabeçalho/Top Bar estilo SRH)
* **Cinza de Fundo (`background`):** `#f8f9fa` (Fundo geral da aplicação)
* **Bordas e Superfícies:** Cartões e formulários leves com fundo branco e bordas em `#ced4da`

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

---

## 💻 5. Convenções de Código para Agentes de IA

Ao programar neste projeto, siga **obrigatoriamente** as seguintes convenções:

* **Nomenclatura no Código:**
  * Funções, variáveis, métodos, classes, tabelas e colunas **devem ser escritos em inglês** (ex: `WaterDelivery`, `order_number`, `calculateNextDeliveryDate`).
  * **Comentários de código e documentação:** Devem ser escritos em **português** para servir de documentação aos desenvolvedores e auditores do sistema.

* **Padrões Laravel:**
  * Mantenha validações dentro de `$request->validate()` ou em `FormRequests` dedicados.
  * Utilize datas tratadas via `Carbon\Carbon`.

* **Padrões Vue 3 / Vuetify:**
  * Prefira o estilo `<script setup>` do Vue 3.
  * Mantenha a estrutura responsiva adaptada a dispositivos móveis e desktops.

---

## 📁 6. Estrutura Direcionada de Arquivos

```text
├── app/
│   ├── Http/Controllers/
│   │   └── WaterDeliveryController.php   # Lógica principal de lançamentos e baixas
│   └── Models/
│       └── WaterDelivery.php             # Eloquent Model da entrega
├── database/
│   └── migrations/
│       └── xxxx_create_water_deliveries_table.php  # Schema relacional
├── resources/
│   ├── js/
│   │   ├── app.js                       # Configuração do Vue 3 + Vuetify 3
│   │   └── Pages/                        # Páginas Inertia.js em Vue
│   │       ├── NovaEntrega.vue
│   │       ├── BaixarEntrega.vue
│   │       └── Relatorios.vue
└── routes/
    └── web.php                          # Rotas protegidas da aplicação