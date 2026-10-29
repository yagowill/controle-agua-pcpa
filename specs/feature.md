# 📄 Especificação de Funcionalidade: SCEA - Sistema de Controle de Entrega de Água

**Sistema:** SCEA — Sistema de Controle de Entrega de Água  
**Órgão:** Polícia Civil do Estado do Pará (PCPA)
**Status:** Em Desenvolvimento / Especificado  

---

## 1. Visão Geral
O Sistema de Controle de Entrega de Água tem como finalidade digitalizar a recepção de garrafões vazios (secos) e a entrega de garrafões cheios para os diversos setores e delegacias da Polícia Civil do Pará. O sistema substitui os antigos talões físicos de papel por comprovantes digitais únicos com QR Code, assegurando a rastreabilidade e impedindo retiradas duplicadas.

---

## 2. Atores do Sistema
* **Atendente do Almoxarifado:** Servidor/colaborador responsável por registrar a entrada dos garrafões secos, emitir o protocolo digital e realizar a baixa do garrafão cheio via leitura do QR Code.
* **Solicitante (Setor/Delegacia):** Servidor que entrega o garrafão seco e recebe o comprovante digital (PNG/WhatsApp) para posterior retirada do cheio.
* **Administrador do Sistema:** Acessa relatórios estatísticos mensais/anuais de consumo por setor e gerencia permissões de acesso.

---

## 3. Fluxos de Negócio & Histórias de Usuário

### US00 — Autenticação e Gestão de Usuários
* **Como:** Servidor da PCPA / Atendente / Administrador.
* **Quero:** Realizar login com credenciais funcionais e permitir o cadastro controlado de novos usuários.
* **Para:** Garantir autenticidade, auditoria e rastreabilidade nas operações de recebimento e entrega de garrafões.

#### Critérios de Aceitação:
1. **Cadastro de Usuário (`Register`):**
   * Campos obrigatórios: Nome completo (`name`), Matrícula/E-mail funcional (`email`), Cargo/Função (`role`: `atendente` ou `admin`), Setor/Lotação (`sector`), Senha (`password`) com confirmação.
   * Validação de complexidade de senha mínima (8 caracteres) e unicidade de matrícula/e-mail.
   * Novos cadastros podem ter status ativo imediato ou aprovação por Administrador conforme política interna.
   * Apenas usuários com perfil `admin` têm acesso à listagem e gerenciamento de permissões de outros usuários.
2. **Login no Sistema (`Login`):**
   * Autenticação via e-mail/matrícula funcional e senha.
   * Opção de "Lembrar-me" (`remember_me`) via cookies seguros de sessão Laravel.
   * Proteção contra ataques de força bruta (rate limiting com `throttle`).
   * Redirecionamento automático pós-login para o painel principal (`/dashboard` ou `/entregas`).
3. **Logout e Segurança de Sessão (`Logout`):**
   * Encerramento explícito de sessão invalidando tokens e cookies (`session()->invalidate()`, `regenerateToken()`).
   * Redirecionamento para a tela de login.
4. **Auditoria e Vínculo Automático:**
   * O usuário autenticado é identificado automaticamente nas operações, vinculando seu ID e nome aos registros de atendimento (`reception_attendant`, `delivery_attendant`).

---

### US01 — Lançamento no Fluxo Padrão (2 Etapas)
* **Como:** Atendente do Almoxarifado.
* **Quero:** Registrar a entrega de garrafões secos por parte de um setor.
* **Para:** Gerar um protocolo com número de ordem único, data prevista de retirada e QR Code para controle.

#### Critérios de Aceitação:
1. O sistema deve gerar automaticamente um código sequencial no formato `PCPA-YYYY-XXXX` (ex: `PCPA-2026-0001`).
2. O sistema deve calcular automaticamente a **data estimada de entrega** com base no dia do lançamento:
   * Lançamento na Segunda-feira a Quarta-feira ➔ Previsão para Quinta-feira da mesma semana.
   * Lançamento na Quinta a Domingo ➔ Previsão para a próxima Segunda-feira.
   * O botão da data estimada de entrega deve ser enditável caso o próximo dia seja um feriado. 
3. O registro é salvo com o status **`DISPONIVEL`**.
4. O sistema gera a visualização do card de protocolo em um modal impresso com opção de **Download em PNG** e **Envio via WhatsApp**.

---

### US02 — Lançamento no Fluxo "Bate Volta" (Troca Imediata)
* **Como:** Atendente do Almoxarifado.
* **Quero:** Registrar a troca simultânea de garrafões (seco por cheio) em uma única etapa.
* **Para:** Atender com agilidade setores distantes ou unidades móveis sem gerar pendências.

#### Critérios de Aceitação:
1. Ao selecionar a opção "Bate Volta", os campos de data de entrega, recebedor e atendente são preenchidos automaticamente com a data/hora atual e o servidor logado.
2. O registro é salvo diretamente com o status **`CONCLUIDO`**.
3. O protocolo emitido já exibe a marcação de baixado/concluído.

---

### US03 — Baixa do Garrafão Cheio (Validação Estrita Anti-Reuso)
* **Como:** Atendente do Almoxarifado.
* **Quero:** Escanear o QR Code ou digitar o número de ordem apresentado pelo servidor para entregar o garrafão cheio.
* **Para:** Confirmar a entrega e encerrar o protocolo com segurança.

#### Critérios de Aceitação:
1. A leitura do QR Code pode ser feita via câmera do dispositivo (`html5-qrcode`) ou digitação manual do número da ordem.
2. **Trava Anti-Reuso (Crítica):**
   * Se o protocolo consultado estiver com status `CONCLUIDO`, o sistema **deve bloquear imediatamente** a baixa.
   * Deve ser exibida uma mensagem em destaque:  
     `"ATENÇÃO: Este comprovante já foi baixado em DD/MM/AAAA HH:mm pelo atendente [NOME]."`
3. Se o protocolo estiver `DISPONIVEL`, o sistema atualiza o status para `CONCLUIDO`, registra a data/hora exata, o nome do recebedor e o atendente responsável.

---

### US04 — Relatórios Estatísticos & Histórico
* **Como:** Administrador / Atendente do Almoxarifado.
* **Quero:** Visualizar o histórico de entregas e os relatórios de consumo mensal e anual.
* **Para:** Acompanhar a demanda por setor/delegacia e auditar entregas passadas.

#### Critérios de Aceitação:
1. O histórico permite buscar registros por número da ordem, nome do setor ou nome do servidor.
2. Filtros por status (`DISPONIVEL` vs `CONCLUIDO`).
3. O relatório consolidado exibe:
   * Total de garrafões distribuídos no mês/ano selecionado.
   * Total de trocas em "Bate Volta" vs "Fluxo Padrão".
   * Ranking dos setores com maior consumo de água.
4. Exportação dos dados consolidados do relatório em formato **PDF**.

---

## 4. Modelo de Dados (Tabela: `water_deliveries`)

| Campo | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | BigInteger (PK) | Não | Identificador interno auto-incrementável. |
| `order_number` | String | Não | Código único do protocolo (ex: `PCPA-2026-0001`). Indexado/Unique. |
| `operation_type` | Enum | Não | `PADRAO` ou `BATE_VOLTA`. Default: `PADRAO`. |
| `sector` | String | Não | Nome do setor ou delegacia solicitante. |
| `empty_bottles_count` | Integer | Não | Quantidade de garrafões secos entregues. |
| `reception_date` | Timestamp | Não | Data/hora do recebimento do garrafão seco. |
| `estimated_date` | Timestamp | Sim | Data estimada de entrega do garrafão cheio (nulo em Bate Volta). |
| `received_from_person` | String | Não | Nome do servidor que entregou o garrafão seco. |
| `reception_attendant` | String | Não | Nome do atendente do Almoxarifado que recebeu. |
| `delivery_date` | Timestamp | Sim | Data/hora da entrega do garrafão cheio (2ª etapa). |
| `delivered_to_person` | String | Sim | Nome do servidor que retirou o garrafão cheio. |
| `delivery_attendant` | String | Sim | Nome do atendente do Almoxarifado que entregou o cheio. |
| `status` | Enum | Não | `DISPONIVEL` ou `CONCLUIDO`. Default: `DISPONIVEL`. |
| `notes` | Text | Sim | Observações adicionais sobre o atendimento. |
| `created_at` / `updated_at` | Timestamp | Não | Registros do sistema. |

---

## 5. Modelo de Dados de Usuários (Tabela: `users`)

| Campo | Tipo | Nulo? | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | BigInteger (PK) | Não | Identificador único do usuário. |
| `name` | String | Não | Nome completo do servidor/colaborador. |
| `email` | String | Não | E-mail funcional / identificador de login (Unique). |
| `password` | String | Não | Hash seguro da senha (bcrypt/argon2id). |
| `role` | Enum | Não | Perfil de acesso: `admin` ou `atendente`. Default: `atendente`. |
| `sector` | String | Sim | Setor/Lotação do usuário na PCPA. |
| `is_active` | Boolean | Não | Situação da conta (ativo/inativo). Default: `true`. |
| `remember_token` | String | Sim | Token para manutenção de sessão "lembrar-me". |
| `created_at` / `updated_at` | Timestamp | Não | Auditoria de criação e alteração do registro. |

---

## 6. Interface & Experiência de Usuário (UI/UX)
* **Tema Institucional:** Aderente ao padrão visual dos sistemas internos da PCPA (Top Bar Grafite `#212529`, Botões em Azul `#2b6cb0` e Cards Vuetify `#f8f9fa`).
* **Responsividade:** Interface otimizada para uso em celulares e computadores da bancada do Almoxarifado.