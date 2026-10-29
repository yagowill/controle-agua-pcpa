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
| **Frontend** | Vue.js 3 (Composition API) e Tailwind css | Utilizar `<script setup>` e reacção nativa. |
| **UI Kit** | Vuetify 3 | Usar componentes Vuetify (`v-card`, `v-btn`, `v-form`, `v-data-table`) |
| **Banco de Dados** | MySQL (Local) / PostgreSQL (Prod) | Nomes de colunas e tabelas sempre em inglês e snake_case. |
| **Versionamento** | Git / GitHub | Padrão *Conventional Commits* em inglês (ex: `feat:`, `fix:`). |

---

## 🎨 3. Padrão de Identidade Visual (PCPA)

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

4. **Controle de Acesso e Autenticação:**
   * Rotas de operações e relatórios devem ser protegidas por middleware de autenticação (`auth`).
   * Usuários com perfil `admin` possuem acesso total (inclusive relatórios analíticos e controle de usuários).
   * Usuários com perfil `atendente` possuem acesso restrito aos fluxos de recebimento, baixa e consulta operacional.
   * Ao cadastrar e autenticar usuários, utilizar hashing seguro (`Hash::make`) e rate limiting nas tentativas de login.

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
│   │   ├── Auth/                         # Controladores de Login e Cadastro
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   └── RegisteredUserController.php
│   │   └── WaterDeliveryController.php   # Lógica principal de lançamentos e baixas
│   └── Models/
│       ├── User.php                      # Model de Usuário com perfis e escopos
│       └── WaterDelivery.php             # Eloquent Model da entrega
├── database/
│   └── migrations/
│       ├── xxxx_create_users_table.php            # Schema de usuários (com role e setor)
│       └── xxxx_create_water_deliveries_table.php  # Schema relacional de entregas
├── resources/
│   ├── js/
│   │   ├── app.js                       # Configuração do Vue 3 + Vuetify 3
│   │   └── Pages/                        # Páginas Inertia.js em Vue
│   │       ├── Auth/
│   │       │   ├── Login.vue             # Tela de login institucional PCPA
│   │       │   └── Register.vue          # Tela de cadastro de usuários
│   │       ├── NovaEntrega.vue
│   │       ├── BaixarEntrega.vue
│   │       └── Relatorios.vue
└── routes/
    ├── auth.php                         # Rotas de login, registro e logout
    └── web.php                          # Rotas protegidas da aplicação (middleware auth)