<div align="center">

# Sistema de Controle de Entrega de Água

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-v1.0-9553E9?style=for-the-badge&logo=inertia&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Vuetify](https://img.shields.io/badge/Vuetify-3.x-1867C0?style=for-the-badge&logo=vuetify&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

<p align="center">
  <b>Aplicação Web corporativa para modernização e gestão do fluxo de distribuição de garrafões de água do Almoxarifado da Polícia Civil do Estado do Pará (PCPA).</b>
</p>

</div>

---

## 📌 Sobre o Projeto

O **SCEA (Sistema de Controle de Entrega de Água)** foi desenvolvido para substituir processos manuais em papel e planilhas por uma solução digital auditável, ágil e segura. O sistema automatiza o recebimento de garrafões vazios, o fornecimento de garrafões cheios, a emissão de protocolos com QR Code e o controle estatístico do consumo por setor e delegacia.

### 🎯 Principais Objetivos
* **Eliminação do Papel:** Emissão de comprovantes digitais (PNG/WhatsApp) com código QR único.
* **Segurança Anti-Reuso:** Trava estrita no banco de dados que impede a dupla retirada com o mesmo comprovante.
* **Agilidade no "Bate Volta":** Registro em etapa única para trocas imediatas por setores distantes.
* **Rastreabilidade e Gestão:** Histórico completo de atendimentos e geração de relatórios de consumo mensal e anual.

---

## 🚀 Stack Tecnológica

O projeto adota a arquitetura de **Monólito Moderno (SPA)**, combinando a robustez do ecossistema Laravel no backend com a reatividade do Vue 3 no frontend através do Inertia.js.

* **Backend:** PHP 8.2+ / [Laravel 11](https://laravel.com/)
* **Bridge (SPA):** [Inertia.js](https://inertiajs.com/)
* **Frontend:** [Vue.js 3](https://vuejs.org/) (Composition API / `<script setup>`)
* **UI Framework:** [Vuetify 3](https://vuetifyjs.com/) (Material Design adaptado à identidade visual da PCPA)
* **Banco de Dados:** MySQL 8.0+ (Desenvolvimento local) / PostgreSQL (Produção)
* **Bibliotecas Auxiliares:** `html5-qrcode` (leitura de QR Code via câmera) e `html2canvas` (exportação de comprovantes para imagem).

---

## 🛠️ Regras de Negócio & Funcionalidades

### 1. Fluxo Padrão (2 Etapas)
* **Entrada:** Registro do garrafão seco ➔ Geração do protocolo sequencial (`PCPA-YYYY-XXXX`) ➔ Cálculo automático da data estimada de devolução (próxima segunda ou quinta-feira) ➔ Status: `DISPONIVEL`.
* **Saída:** Leitura do QR Code ➔ Registro do recebedor e atendente ➔ Atualização para o status: `CONCLUIDO`.

### 2. Fluxo Bate Volta (Troca Imediata)
* Registro unificado de entrada e saída em um único passo para entregas diretas ➔ Status gerado diretamente como `CONCLUIDO`.

### 3. Trava de Segurança Anti-Reuso
* Se um protocolo com status `CONCLUIDO` for lido novamente, o sistema bloqueia a operação e exibe a data, o horário exato e o nome do servidor que realizou a baixa anterior.

### 4. Relatórios e Indicadores
* Painel consolidado com estatísticas de consumo mensal/anual por setor, filtros avançados e exportação de dados em formato **PDF**.

---

## 🎨 Identidade Visual (PCPA)

A interface segue o Padrão Visual Institucional dos Sistemas da Polícia Civil do Pará:
* **Azul Institucional (`#2b6cb0`):** Botões de ação e destaques principais.
* **Grafite Escuro (`#212529`):** Barra de navegação superior (Top Bar SRH).
* **Cinza Neutro (`#f8f9fa`):** Fundo limpo e cartões em Material Design.

---

## 💻 Como Executar o Projeto Localmente

### Pré-requisitos
* PHP >= 8.2
* Composer
* Node.js >= 18.x e npm
* Servidor MySQL (ex: [Laragon](https://laragon.org/), XAMPP ou Docker)

### Passo a Passo

1. **Clonar o Repositório:**
   ```bash
   git clone [https://github.com/SEU-USUARIO/scea-controle-agua-pcpa.git](https://github.com/SEU-USUARIO/controle-agua-pcpa.git)
   cd scea-controle-agua-pcpa
