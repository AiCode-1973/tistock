# POP — Controle de Estoque de TI no Sistema TIStock

**Documento:** POP-TI-001  
**Versão:** 1.0  
**Data de emissão:** Abril/2026  
**Setor responsável:** Tecnologia da Informação  
**Aplicável a:** Técnicos e Analistas de TI  

---

## 1. Objetivo

Estabelecer o procedimento padrão para registro de **entrada**, **saída** e **empréstimo** de equipamentos e materiais do setor de TI no sistema **TIStock**, garantindo o controle atualizado do inventário e a rastreabilidade das movimentações.

---

## 2. Abrangência

Este procedimento se aplica a todos os colaboradores do setor de TI que realizam manuseio, distribuição, recebimento ou empréstimo de equipamentos e materiais de informática no hospital.

---

## 3. Responsabilidades

| Perfil | Responsabilidade |
|---|---|
| **Administrador** | Gerenciar usuários, categorias, configurações e zerar dados do sistema |
| **Técnico** | Registrar entradas, saídas e empréstimos; devolver equipamentos |
| **Consultor** | Consultar estoque, histórico e relatórios (somente leitura) |

---

## 4. Acesso ao Sistema

1. Abra o navegador e acesse: **https://tistock.aicode.dev.br**
2. Informe seu **e-mail** e **senha** cadastrados
3. Clique em **Entrar**

> ⚠️ Em caso de esquecimento de senha, solicite a redefinição ao Administrador do sistema.

---

## 5. Registro de Entrada de Equipamentos/Materiais

**Quando usar:** Ao receber equipamentos por compra, doação ou devolução de empréstimo.

### Passo a passo

1. No menu lateral, acesse **Movimentações → Registrar Entrada**
2. No campo **Buscar item**, digite o nome do equipamento, número de patrimônio ou número de série
3. Selecione o item correto na lista exibida
4. Preencha os campos obrigatórios:

   | Campo | Orientação |
   |---|---|
   | **Motivo** | Selecione: Compra, Doação ou Devolução |
   | **Quantidade** | Informe o número de unidades recebidas |
   | **Data/Hora** | Preencher com a data e hora reais do recebimento |
   | **Responsável** | Nome do técnico que está registrando a entrada |
   | **Observações** | Número da NF, fornecedor, número do chamado (se aplicável) |

5. Clique em **Registrar Entrada**
6. Verifique a mensagem de confirmação na tela

> 📌 O estoque do item será atualizado automaticamente após a confirmação.

---

## 6. Registro de Saída de Equipamentos/Materiais

**Quando usar:** Ao retirar equipamentos do estoque para descarte, manutenção, alocação permanente em setor ou empréstimo avulso.

> ⚠️ Para empréstimos com previsão de devolução, utilize o módulo **Empréstimos** (seção 7).

### Passo a passo

1. No menu lateral, acesse **Movimentações → Registrar Saída**
2. No campo **Buscar item**, localize o equipamento pelo nome, patrimônio ou número de série
3. Selecione o item na lista — verifique o **estoque atual** exibido
4. Preencha os campos obrigatórios:

   | Campo | Orientação |
   |---|---|
   | **Motivo** | Selecione: Empréstimo, Manutenção, Descarte ou Alocação em Setor |
   | **Quantidade** | Informe o número de unidades que estão saindo |
   | **Data/Hora** | Preencher com a data e hora reais da saída |
   | **Responsável** | Nome do técnico que está registrando a saída |
   | **Observações** | Setor destino, número do chamado, justificativa |

5. Clique em **Registrar Saída**
6. Confirme a mensagem de sucesso

> ⚠️ Não é possível registrar saída com quantidade superior ao estoque disponível.

---

## 7. Registro de Empréstimo de Equipamentos

**Quando usar:** Ao emprestar equipamentos para colaboradores ou setores do hospital com previsão de devolução.

### 7.1 Registrar Novo Empréstimo

1. No menu lateral, acesse **Empréstimos → Novo Empréstimo**
2. Selecione o item desejado
3. Preencha obrigatoriamente:

   | Campo | Orientação |
   |---|---|
   | **Solicitante** | Nome completo do colaborador que está retirando |
   | **Setor de Destino** | Setor do hospital onde o item será utilizado |
   | **Quantidade** | Número de unidades emprestadas |
   | **Data de Saída** | Data e hora da retirada |
   | **Previsão de Devolução** | Data combinada para retorno do equipamento |
   | **Observações** | Finalidade do empréstimo, número do chamado |

4. Clique em **Registrar Empréstimo**
5. Imprima o **recibo** clicando no ícone 🖨 na listagem de empréstimos
6. Solicite a **assinatura do solicitante** no campo "Retirou o Equipamento"

> 📌 Guarde o recibo impresso no arquivo físico do setor.

### 7.2 Registrar Devolução

1. No menu lateral, acesse **Empréstimos**
2. Localize o empréstimo desejado (use o filtro de status **Ativo** ou **Atrasado**)
3. Clique no botão **↩ Devolver** (ícone verde) na linha do empréstimo
4. Confirme a devolução
5. Imprima novo recibo se necessário e colha a assinatura de devolução

> ⚠️ Empréstimos com data vencida são sinalizados em **vermelho** automaticamente pelo sistema.

---

## 8. Impressão do Recibo de Empréstimo

1. Acesse **Empréstimos** no menu lateral
2. Clique no ícone 🖨 na linha do empréstimo desejado
3. O recibo abrirá em nova aba em formato A4
4. Clique em **Imprimir** ou utilize `Ctrl + P`
5. Solicite as assinaturas:
   - **Responsável pelo Empréstimo (TI)** — assinado pelo técnico
   - **Retirou o Equipamento** — assinado pelo solicitante na saída
   - **Devolveu o Equipamento** — assinado pelo solicitante na devolução

---

## 9. Consulta de Estoque e Histórico

- **Estoque atual:** Menu → **Itens de Estoque**
  - Use os filtros de busca por nome, patrimônio ou nº de série
  - Itens com estoque crítico aparecem destacados em vermelho
- **Histórico de movimentações:** Menu → **Movimentações → Histórico**
- **Dashboard:** Exibe resumo geral, alertas críticos e empréstimos atrasados

---

## 10. Alertas e Situações de Atenção

| Situação | O que fazer |
|---|---|
| Item com estoque abaixo do mínimo | Verificar necessidade de compra e registrar entrada assim que recebido |
| Empréstimo atrasado (marcado em vermelho) | Contatar o solicitante e registrar a devolução ou acionar a chefia |
| Item não encontrado no sistema | Acionar o Administrador para cadastro |
| Erro ao registrar movimentação | Anotar os dados e acionar o Administrador imediatamente |

---

## 11. Regras Gerais

- **Todo** recebimento, distribuição ou empréstimo de equipamento **deve ser registrado** no sistema no mesmo dia da ocorrência
- Não realizar movimentações retroativas sem autorização do Administrador
- O recibo de empréstimo **deve ser impresso em duas vias**: uma para o arquivo do TI e uma para o solicitante
- Movimentações incorretas devem ser comunicadas ao Administrador para correção
- É **vedado** compartilhar credenciais de acesso ao sistema

---

## 12. Histórico de Revisões

| Versão | Data | Descrição | Responsável |
|---|---|---|---|
| 1.0 | Abril/2026 | Emissão inicial | Setor de TI |

---

*Este documento deve ser revisado anualmente ou sempre que houver alteração significativa no sistema ou nos processos do setor.*
