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

### US01 — Lançamento no Fluxo Padrão (2 Etapas)
* **Como:** Atendente do Almoxarifado.
* **Quero:** Registrar a entrega de garrafões secos por parte de um setor.
* **Para:** Gerar um protocolo com número de ordem único, data prevista de retirada e QR Code para controle.

#### Critérios de Aceitação:
1. O sistema deve gerar automaticamente um código sequencial no formato `PCPA-YYYY-XXXX` (ex: `PCPA-2026-0001`).
2. O sistema deve calcular automaticamente a **data estimada de entrega** com base no dia do lançamento:
   * Lançamento na Segunda-feira a a Quarta-feira ➔ Previsão para Quinta-feira da mesma semana.
   * Lançamento na Sexta, Sábado ou Domingo ➔ Previsão para a próxima Quinta-feira.
   * Se o proximo dia de lançamento for um feriado ou ponto fativo (no estado do pará) ➔ Previsão para o proximo dia útil. 
3. O registro é salvo com o status **`DISPONIVEL`**.
4. O sistema gera a visualização do card de protocolo impresso com opção de **Download em PNG** e **Envio via WhatsApp**.

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

## 5. Interface & Experiência de Usuário (UI/UX)
* **Tema Institucional:** Aderente ao padrão visual dos sistemas internos da PCPA (Top Bar Grafite `#212529`, Botões em Azul `#2b6cb0` e Cards Vuetify `#f8f9fa`).
* **Responsividade:** Interface otimizada para uso em celulares e computadores da bancada do Almoxarifado.