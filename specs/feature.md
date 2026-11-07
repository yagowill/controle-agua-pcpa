# 📄 Especificação de Funcionalidade: SCEA — Sistema de Controle de Entrega de Água

**Sistema:** SCEA — Sistema de Controle de Entrega de Água
**Órgão:** Polícia Civil do Estado do Pará (PCPA)
**Status:** Em desenvolvimento / especificado
**Última atualização:** novembro/2026

---

## 1. Visão Geral

O SCEA digitaliza a recepção de garrafões vazios (secos) e a entrega de garrafões cheios para os setores e delegacias da Polícia Civil do Pará. O sistema substitui os talões físicos por comprovantes digitais com QR Code, garantindo rastreabilidade, auditoria e bloqueio de retiradas duplicadas.

### 1.1 Objetivos

* Eliminar o controlo em papel.
* Emitir comprovantes digitais com QR Code único.
* Impedir reutilização de protocolos já concluídos.
* Permitir atendimento imediato no fluxo “bate volta”.
* Gerar histórico operacional e relatórios analíticos de consumo.

---

## 2. Arquitetura e Stack

| Camada | Tecnologia |
| :--- | :--- |
| Backend | PHP 8.4 / Laravel 13 |
| Bridge SPA | Inertia.js 2 |
| Frontend | Vue 3 (Composition API, `<script setup>`) |
| UI Kit | Vuetify 4 |
| Estilos | Tailwind CSS 3 + PostCSS |
| Banco de dados | PostgreSQL 17 |
| PDF | `dompdf/dompdf` |
| Planilhas | `phpoffice/phpspreadsheet` |
| Testes | PHPUnit 12 com SQLite `:memory:` |
| Desenvolvimento | Docker Compose (`app`, `vite`, `postgres`) |
| Produção | Oracle Cloud Always Free + Docker Compose + Caddy (HTTPS automático) |

### 2.1 Decisões arquiteturais

* Monólito moderno com Inertia.js; **não existe API REST separada**.
* Identificadores de código em inglês; comentários e documentação em português.
* Sessões, cache e fila usam o próprio PostgreSQL (`SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`).
* O sistema é stateless: não há uploads persistentes nem jobs em background.
* Healthcheck disponível em `/up`.

---

## 3. Atores do Sistema

* **Atendente do Almoxarifado:** registra a entrada dos garrafões secos, emite o protocolo digital e realiza a baixa do garrafão cheio.
* **Solicitante (Setor/Delegacia):** entrega o garrafão seco e recebe o comprovante digital para posterior retirada do cheio.
* **Administrador do Sistema:** acessa relatórios, gerencia usuários, redefine senhas, ativa/desativa contas e remove usuários.

---

## 4. Fluxos de Negócio e Histórias de Usuário

### US00 — Autenticação e Gestão de Usuários

**Como** servidor da PCPA, **quero** autenticar-me e ter minha conta gerida por um administrador, **para** garantir autenticidade e auditoria.

#### Critérios de aceitação

1. **Login:**
   * Autenticação por `username` e senha.
   * `email` e `matricula` são opcionais no cadastro.
   * Rate limiting nas tentativas de login.
   * Contas com `is_active = false` não podem autenticar.
   * Redirecionamento pós-login para `/entregas/nova`.
2. **Primeiro acesso:**
   * Todo usuário novo é criado com `must_change_password = true`.
   * O middleware `require.password.change` bloqueia o restante do sistema até a troca.
   * A troca fica disponível em `/alterar-senha`.
3. **Gestão de usuários (apenas `admin`):**
   * Listagem em `/admin/usuarios`.
   * Criação com senha temporária.
   * Redefinição de senha.
   * Ativação/desativação de conta.
   * Remoção de usuário.
   * Atendentes recebem `403` em qualquer rota `/admin/*`.
4. **Logout:**
   * Invalida a sessão e regenera o token CSRF.
   * Redireciona para `/login`.

---

### US01 — Lançamento no Fluxo Padrão (2 Etapas)

**Como** atendente do almoxarifado, **quero** registar a entrega de garrafões secos por um setor, **para** gerar um protocolo com número único, previsão de entrega e QR Code.

#### Critérios de aceitação

1. O código sequencial segue `PCPA-YYYY-XXXX` (ex.: `PCPA-2026-0001`).
2. A previsão de entrega é calculada automaticamente:
   * Segunda a quarta ➔ quinta-feira da mesma semana.
   * Quinta a domingo ➔ segunda-feira seguinte.
   * A data pode ser ajustada manualmente (ex.: feriados).
3. O registo é criado com status `DISPONIVEL`.
4. O protocolo é exibido em modal, com download em PNG e envio por WhatsApp.
5. O atendente responsável é preenchido automaticamente com o usuário autenticado.

---

### US02 — Lançamento “Bate Volta” (Troca Imediata)

**Como** atendente, **quero** registar a troca simultânea de garrafões, **para** atender setores distantes sem gerar pendências.

#### Critérios de aceitação

1. Data/hora, recebedor e atendente são preenchidos automaticamente.
2. O registo é criado diretamente com status `CONCLUIDO`.
3. O protocolo emitido já indica a conclusão.
4. `estimated_date` permanece nula.

---

### US03 — Baixa do Garrafão Cheio (Anti-Reuso)

**Como** atendente, **quero** ler o QR Code ou digitar o número da ordem, **para** entregar o garrafão cheio e encerrar o protocolo com segurança.

#### Critérios de aceitação

1. Consulta por câmara (`html5-qrcode`) ou digitação manual.
2. **Trava anti-reuso (crítica):**
   * Se o status for `CONCLUIDO`, a baixa é bloqueada.
   * A mensagem deve informar data, hora e nome do atendente responsável pelo resgate anterior.
3. Se o status for `DISPONIVEL`, o sistema:
   * Atualiza o status para `CONCLUIDO`.
   * Regista `delivery_date`, `delivered_to_person` e `delivery_attendant`.

---

### US04 — Histórico Operacional

**Como** atendente ou administrador, **quero** consultar o histórico de entregas, **para** auditar atendimentos e localizar protocolos.

#### Critérios de aceitação

1. Página própria em `/entregas/historico`, separada dos relatórios.
2. Pesquisa por número da ordem, setor ou nome do servidor.
3. Filtros por status (`DISPONIVEL` / `CONCLUIDO`).
4. Paginação e ordenação dos resultados.
5. Link direto para o dashboard de relatórios.

---

### US05 — Relatórios Analíticos e Exportações

**Como** administrador ou atendente, **quero** visualizar o consumo mensal e anual, **para** acompanhar a demanda por setor e apoiar a gestão.

#### Critérios de aceitação

1. Página própria em `/entregas/relatorios`.
2. Filtros por período mensal e anual.
3. **Apenas ordens `CONCLUIDO` entram nos cálculos de consumo.**
4. Indicadores (KPIs):
   * Total de garrafões entregues.
   * Total de atendimentos concluídos.
   * Média por atendimento.
   * Variação em relação ao período anterior.
5. Ranking de consumo por setor.
6. Gráficos de evolução e distribuição.
7. Tabela detalhada dos registros do período.
8. Exportações:
   * **PDF:** dashboard com KPIs, ranking e gráficos (SVG embutido como data URI).
   * **XLSX:** resumo, séries, setores e registos.
   * **CSV:** base detalhada, com BOM UTF-8 e separador `;` para compatibilidade com Excel pt-BR.
9. Períodos sem registos devem gerar exportações válidas.

---

## 5. Modelo de Dados

### 5.1 Tabela `users`

| Campo | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | BigInteger (PK) | Não | Identificador interno. |
| `name` | String | Não | Nome completo. |
| `username` | String (50, unique) | Não | Identificador de login. |
| `matricula` | String (50) | Sim | Matrícula funcional. |
| `email` | String | Sim | E-mail funcional. |
| `password` | String | Não | Hash seguro da senha. |
| `role` | Enum(`admin`, `atendente`) | Não | Perfil de acesso. Default: `atendente`. |
| `sector` | String | Sim | Setor/lotação. |
| `is_active` | Boolean | Não | Conta ativa. Default: `true`. |
| `must_change_password` | Boolean | Não | Obriga troca no primeiro acesso. Default: `true`. |
| `remember_token` | String | Sim | Token “lembrar-me”. |
| `created_at` / `updated_at` | Timestamp | Não | Auditoria. |

### 5.2 Tabela `water_deliveries`

| Campo | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | BigInteger (PK) | Não | Identificador interno. |
| `order_number` | String (unique) | Não | Código `PCPA-YYYY-XXXX`. |
| `operation_type` | Enum(`PADRAO`, `BATE_VOLTA`) | Não | Tipo de operação. Default: `PADRAO`. |
| `sector` | String | Não | Setor/delegacia solicitante. |
| `empty_bottles_count` | Integer | Não | Quantidade de garrafões secos. |
| `reception_date` | Timestamp | Não | Data/hora do recebimento. |
| `estimated_date` | Timestamp | Sim | Previsão de entrega (nula em bate volta). |
| `received_from_person` | String | Não | Quem entregou o garrafão seco. |
| `reception_attendant` | String | Não | Atendente que recebeu. |
| `delivery_date` | Timestamp | Sim | Data/hora da entrega do cheio. |
| `delivered_to_person` | String | Sim | Quem retirou o garrafão cheio. |
| `delivery_attendant` | String | Sim | Atendente que entregou. |
| `status` | Enum(`DISPONIVEL`, `CONCLUIDO`) | Não | Situação do protocolo. Default: `DISPONIVEL`. |
| `notes` | Text | Sim | Observações. |
| `created_at` / `updated_at` | Timestamp | Não | Auditoria. |

### 5.3 Índices de desempenho

Criados para acelerar filtros e agregações dos relatórios:

* `reception_date`
* `sector`
* `status`
* `(reception_date, status)`

---

## 6. Regras de Negócio Consolidadas

1. `CONCLUIDO` é estado terminal: não pode ser resgatado novamente.
2. `PADRAO` nasce `DISPONIVEL`; `BATE_VOLTA` nasce `CONCLUIDO`.
3. O código da ordem é único e segue `PCPA-YYYY-XXXX`.
4. O consumo nos relatórios considera apenas `CONCLUIDO`.
5. Histórico e relatórios são páginas distintas.
6. O atendente autenticado é sempre associado à operação.
7. A senha inicial é temporária e deve ser trocada no primeiro acesso.
8. Apenas `admin` gere usuários.

---

## 7. Requisitos Não Funcionais

### 7.1 Segurança

* Autenticação com `Hash::make` e rate limiting.
* CSRF ativo em todas as rotas web.
* Rotas de operações e relatórios sob `auth` e `require.password.change`.
* Rotas administrativas sob o middleware `admin`.
* Em produção: `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS obrigatório.

### 7.2 Desenvolvimento (Docker)

* Serviços: `app` (PHP 8.4 + Apache), `vite` (Node 22) e `postgres` (17).
* Portas: `8000` (aplicação), `5173` (Vite/HMR) e `5433` (PostgreSQL no host).
* `vendor`, `node_modules` e `pgdata` em volumes nomeados.
* Migrations aplicadas automaticamente no start.
* Testes executados com SQLite em memória, nunca contra o PostgreSQL de desenvolvimento.

### 7.3 Produção

* Deploy de referência em VM Oracle Cloud Always Free (ARM Ampere A1).
* Imagem multi-stage: build de assets, dependências de produção e runtime PHP/Apache.
* Caddy como reverse proxy, com certificado Let's Encrypt automático.
* PostgreSQL sem porta pública.
* Migrations e caches (`config`, `route`, `view`) aplicados no entrypoint.
* Backups do banco sob responsabilidade da operação.

### 7.4 Localização

* Timezone: `America/Belem`.
* Locale: `pt_BR`.
* Moeda e datas no formato brasileiro.
* CSV com BOM UTF-8 e separador `;`.

---

## 8. Interface e Experiência de Usuário

* **Tema institucional:** Top Bar grafite `#212529`, botões azuis `#2b6cb0`, fundo `#f8f9fa` e cartões brancos.
* **Responsividade:** otimizada para celulares, tablets e computadores do almoxarifado.
* **Navegação:** abas separadas para Nova Entrega, Baixa, Histórico e Relatórios.
* **Acessibilidade:** rótulos externos nos campos, contraste adequado e mensagens de erro claras.
* **Feedback:** confirmações, estados de carregamento e mensagens de bloqueio anti-reuso destacadas.

---

## 9. Fora de Escopo (versão atual)

* Aplicativo móvel nativo.
* Envio automático de WhatsApp por API oficial.
* Integração com sistemas de RH ou almoxarifado.
* Multitenancy.
* Jobs assíncronos e filas dedicadas.
* Armazenamento de ficheiros em nuvem (S3).

---

## 10. Critérios de Aceitação da Entrega

1. `docker compose up -d --build` sobe `app`, `vite` e `postgres` sem erros.
2. Migrations aplicadas no PostgreSQL.
3. Login, troca de senha, nova entrega, bate volta, baixa e histórico funcionam.
4. Trava anti-reuso bloqueia ordens `CONCLUIDO`.
5. Dashboard mensal/anual calcula apenas `CONCLUIDO`.
6. Exportações PDF, XLSX e CSV são geradas corretamente.
7. `docker compose exec app php artisan test` passa (52 testes / 279 asserções).
8. `./vendor/bin/pint --test` passa.
9. `docker compose -f compose.prod.yaml config` é válido.
10. A imagem de produção responde `200` em `/up`.
