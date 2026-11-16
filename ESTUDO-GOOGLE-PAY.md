# Estudo — Google Pay (Getnet Global / Regional API)

Fonte: https://docs.globalgetnet.com/pt/products/online-payments/regional-api?doc=google-pay

> **Ressalva:** a página foi lida por um extrator que resume (não reproduz JSON literal) e **não expõe o
> método/path do endpoint de pagamento**. Antes de codar, conferir os payloads exatos na página/Swagger e
> validar no sandbox (`api.pre.globalgetnet.com`). Itens marcados com ⚠️ são suposições a confirmar.

## 1. O que a doc define

- **Disponibilidade:** somente Brasil e Espanha. Para este módulo: **BR / BRL**.
- **Somente API Global** (não existe equivalente na V2).
- **Tokenização no Google Pay** (`tokenizationSpecification`):
  `type=PAYMENT_GATEWAY`, `gateway=getnetpayments`, `gatewayMerchantId=<ID atribuído no setup técnico Getnet>`.
- **Auth methods:** `PAN_ONLY` (3DS opcional no BR, obrigatório na Espanha) e `CRYPTOGRAM_3DS`.
  **Bandeiras:** VISA e MASTERCARD.
- **Pré-requisitos:** front em HTTPS; negócio cadastrado no *Google Pay & Wallet Console* com domínio na
  allowlist antes de produção; aceitar políticas/ToS do Google.
- **Objeto `wallet` no payload:** Google Pay padrão = `id 216`, `type 10`, sem `tag`.
  (SDWO/fund transfer usa `id BRL`, `type 55` — fora de escopo.)
- **Três fluxos de envio** (todos no mesmo payload `data.payment` de cartão, com `wallet`):

| Fluxo | O que o merchant envia | Observação |
|---|---|---|
| PAN | `card.number`, validade (2 dígitos), `wallet`, `eci` opcional | Merchant manipula PAN → escopo PCI maior |
| Tokenizado (DPAN) | DPAN + validade + `tokenization.cryptogram` + `tokenization.eci` | Exige decifrar o token Google (chave do merchant) |
| **Authorization Token** | só `wallet.authorization_token` (= `paymentMethodData.tokenizationData.token` **exato**, em Base64) | Não decifrar/alterar; sem campos de cartão |

- Campos gerais: `idempotency_key` e `order_id` obrigatórios; `request_id` recomendado; `additional_data.customer`
  e `.device` condicionais (antifraude).
- **Resposta:** `status` ∈ `APPROVED | DECLINED | PENDING`. `PENDING` = 3DS pendente, com
  `additional_data._links[]` contendo item `3ds_html` (`href`, `creq`). Em sandbox `brand` pode vir `UNKNOWN`.

## 2. Recomendação de fluxo

**Authorization Token flow.** O módulo nunca vê PAN/DPAN/criptograma (alinhado à regra "nunca armazenar/logar
PAN"), não precisa gerenciar chaves de decifragem do Google, e o backend só faz Base64 + repasse.

## 3. Aderência ao módulo atual

O que já existe e **reaproveita**:

- `Gateway/Http/Client/Card/CreateClient` + `EndpointResolver::CARD_AUTHORIZE`
  (`dpm/payments-gwproxy/v2/payments` na Global) — ⚠️ confirmar que o Google Pay usa este mesmo path
  (a doc fala em "payload de cartão"; a Global compartilha `v2/payments*`).
- Builders de `Card/Data/*`: `AmountDataRequest`, `CurrencyDataRequest`, `CustomerIdDataRequest`,
  `Additional/*` (device, customer, endereço, itens), `StoreIdDataRequest`, `CardInitSchemaDataRequest`
  (idempotency_key/order_id).
- Handlers de resposta de Cc (`TransactionAuthorizationHandler`, `TxnIdHandler`, `ExtOrdIdHandler`),
  validators (`PaymentMagentoTransactionValidator`) e mapeamento de erros.
- Operações pós-venda do Cc (`capture`, `cancel/void`, `refund` — `CARD_CAPTURE`, `CARD_CANCEL`).
- Padrão de gating por API: `Config::getApiType()` / `isAvailable()` do `EndpointResolver`.

O que **não existe** e precisa ser criado:

- Método de pagamento novo (nenhum renderer/config/DataAssign).
- Integração com a **Google Pay JS API** no checkout.
- **Tratamento de 3DS/`PENDING` com desafio**: hoje `TransactionAuthorizationHandler` trata `PENDING` como
  aprovado+pendente e **não há nenhum código de 3DS** (`_links`/`creq`/`3ds_html`) em `Gateway`, `Model` ou JS.
- Cuidado de nomenclatura: **"Wallet" no módulo é o QR Code V2** (`getnet_paymentmagento_wallet`).
  Usar `googlepay` em tudo (código, classes, virtual types) para não colidir.

## 4. Desenho proposto

### 4.1 Método

- Código: `getnet_paymentmagento_googlepay`; virtual types `PaymentMagentoGooglePay*`.
- `payment_action`: `authorize` (ou `authorize_capture` conforme config do Cc); `can_capture/refund/void/cancel`
  espelhando o Cc — ⚠️ validar no sandbox capture/cancel/refund de transação Google Pay.
- Disponível só com `api_type = api_global`, moeda BRL, país BR (`ConfigProviderGooglePay::isActive`
  deve retornar `false` quando a API for V2, e o config provider **sempre** deve devolver a chave —
  `getnet.js` lê `config[method].isActive` sem checar existência).

### 4.2 Admin (`etc/adminhtml/system.xml`, `etc/config.xml`)

Grupo `getnet_paymentmagento_googlepay` (padrão do grupo Pix): `active`, `title`, `gateway_merchant_id`
(valor Getnet — pode ser tratado como config simples, não é segredo), `environment` (TEST/PRODUCTION do Google,
ou derivar do ambiente Getnet), `button_color`, `button_type`, `button_size_mode`, `payment_action`,
`min/max_order_total`, `allowspecific/specificcountry`, `sort_order`, e parcelamento (ver 4.5).
`depends` deve usar `*/getnet_base/credentials/api_type` (seção `payment` é clonada em abas — ver memória
`system-xml-depends-api-type`), nunca path absoluto.

### 4.3 Frontend

- `Model/Ui/ConfigProviderGooglePay`: expõe `isActive`, `gatewayMerchantId`, `environment`, `merchantName`,
  `allowedCardNetworks`, `allowedAuthMethods`, estilo do botão, total/moeda.
- `view/frontend/web/js/view/payment/method-renderer/getnet_paymentmagento_googlepay.js` + template `.html`:
  1. carrega `https://pay.google.com/gp/p/js/pay.js` sob demanda (`requirejs`/script dinâmico);
  2. `new google.payments.api.PaymentsClient({environment})` → `isReadyToPay` (esconde o método se `false`);
  3. `createButton` + `loadPaymentData` com `transactionInfo` (BRL, `totalPrice` do quote, `FINAL`) e
     `tokenizationSpecification` da seção 1;
  4. no sucesso, coloca `paymentMethodData.tokenizationData.token` **como string crua** em
     `additional_data.googlepay_token` e dispara `placeOrder`. Não fazer `JSON.parse/stringify`
     (a doc exige o token exatamente como recebido).
- Registrar no `view/frontend/web/js/view/payment/getnet.js` e em `checkout_index_index.xml`.
- **CSP** (`etc/csp_whitelist.xml`): `script-src https://pay.google.com`, `frame-src https://pay.google.com`
  (+ `https://payments.google.com` conforme exigência), `connect-src https://google.com/pay https://pay.google.com`
  e `img-src` do botão. Modo restrito do CSP no Magento 2.4.7+ precisa ser testado.

### 4.4 Backend

- `Observer/DataAssignObserverGooglePay` (+ `etc/events.xml`): grava `googlepay_token` em
  `additional_information`; rejeitar token vazio. O token é de uso único e sensível → **não logar**, incluir em
  `privateInfoKeys` e remover do `additional_information` logo após a autorização (mesma prática do `cc_cid`).
- `Gateway/Request/GooglePay/PaymentDataRequest`: monta `data.payment`:
  `payment_method`, `transaction_type`, `number_installments`, `soft_descriptor`, `dynamic_mcc` e
  `wallet: { id: 216, type: 10, authorization_token: base64($rawToken) }` — **sem** bloco `card`.
  Constantes `WALLET_ID=216`/`WALLET_TYPE=10` na própria classe.
- Command pool próprio (`PaymentMagentoGooglePayCommandPool`) reutilizando os builders do
  `PaymentMagentoCcAuthorizationRequest` (trocando `PaymentDataRequest` e removendo `VaultDataBuilder`),
  `Client\Card\CreateClient`, validators e handlers do Cc. Sem Vault (token Google não é salvável).
- `Gateway/Request/...` de `idempotency_key`/`order_id`: reaproveitar os do Cc. Atenção: `PaymentDataRequest`
  do Cc gera `payment_id` por hash do increment_id; conferir se o Google Pay aceita/exige esse campo.
- Info block (`Block/Sales/Info/GooglePay`) mostrando bandeira/final se vierem na resposta; i18n em
  `pt_BR.csv` e `en_US.csv` para toda string nova.

### 4.5 Parcelamento (requisito essencial — BR)

**Parcelamento é obrigatório no escopo.** O Google Pay não tem noção de parcelas: o usuário escolhe no
checkout do Magento e o módulo envia `number_installments` no `data.payment` (campo previsto na doc).
Reaproveitar integralmente a infraestrutura de juros do Cc:

- **Back:** `ConfigCc::getMinInstallment/getMaxInstallment/getTypeInterestByInstallment/getInterestToAmount`,
  `Model/Api/InterestManagement` (+ `GuestInterestManagement`, `Api/` de juros), total
  `Model/Quote/Address/Total/GetnetInterest` e totais de invoice/creditmemo, `CovertInterestToOrderObserver`.
  O `PaymentDataRequest` do Google Pay lê `cc_installments` e calcula `transaction_type`
  (`FULL`/`INSTALL_NO_INTEREST`/`INSTALL_WITH_INTEREST`) exatamente como o do Cc.
  O grupo de config de parcelas do Google Pay pode **herdar** do Cc (sem duplicar admin) ou ter o próprio
  (decisão: herdar por padrão, com override opcional).
- **Front (ponto crítico):** a folha do Google Pay exibe um `totalPrice`, e o juros altera o total do pedido.
  Portanto a **ordem do fluxo** é:
  1. cliente escolhe as parcelas num `<select>` no renderer (mesmo `calculate-installment` /
     `set-interest` do Cc) → o total do quote é recalculado com juros;
  2. o botão do Google Pay fica **desabilitado até haver parcela selecionada**;
  3. ao clicar, `transactionInfo.totalPrice` = grand total **já com juros** (ler dos totals do quote);
  4. se o cliente trocar a parcela depois do pagamento autorizado no Google (antes do `placeOrder`), descartar
     o token e exigir novo clique, para o valor da folha nunca divergir do valor cobrado.
  Opcional: `displayItems` com subtotal/juros/“N× de R$ X” para transparência na folha.
- **Validação no servidor:** o observer/validator rejeita `cc_installments` fora de [min, max] e confere
  que o total do pedido bate com o valor + juros calculado no servidor (nunca confiar no front).

### 4.7 Webhooks e conciliação

**Decisão: o webhook do Google Pay é descartado, não há tratamento.** O fluxo é síncrono: a venda é imediata
(`CREDIT`) e o resultado (aprovado/recusado) já vem na resposta da criação, que fecha o pedido (invoice paga,
estado `processing`) na própria requisição. Não existe estado intermediário (`PENDING`/3DS é recusado com
`GOOGLEPAY-004`), então não há o que conciliar depois.

Nenhum código novo: a assinatura de webhook da Global é única para a conta e as notificações dos pedidos Google Pay
caem em `Controller/Notification/All`, que já os ignora — o pedido não está em `STATE_NEW`, então a resposta é
`412 Not available` e nada é alterado (idempotente). O `payment_id` fica gravado no pedido (`ext_order_id` e
`additional_information.payment_id`) para consulta manual/conciliação financeira, se necessário.

## 5. Arquivos a criar/alterar

| Camada | Arquivo |
|---|---|
| Config | `etc/payment.xml`, `etc/config.xml`, `etc/adminhtml/system.xml`, `etc/adminhtml/di.xml` |
| DI | `etc/di.xml` (facade, config, value handlers, command pool/manager, request composite, handlers), `etc/frontend/di.xml` |
| Eventos | `etc/events.xml` + `Observer/DataAssignObserverGooglePay.php` |
| Config PHP | `Gateway/Config/ConfigGooglePay.php`, `Model/Ui/ConfigProviderGooglePay.php` |
| Request | `Gateway/Request/GooglePay/PaymentDataRequest.php` |
| Block | `Block/Sales/Form/GooglePay.php`, `Block/Sales/Info/GooglePay.php` (+ templates adminhtml/base) |
| Front | `getnet_paymentmagento_googlepay.js`, `template/payment/googlepay.html`, `getnet.js`, `checkout_index_index.xml` |
| Segurança | `etc/csp_whitelist.xml` |
| i18n | `i18n/pt_BR.csv`, `i18n/en_US.csv` |
| Docs | atualizar `CLAUDE.md` (tabela de métodos, "V2-only"/Global), `README.md`, `TODO.md` |

Nenhum client novo é necessário se o endpoint for o mesmo do Cc; se não for, adicionar operação
`GOOGLEPAY_CREATE` no `EndpointResolver` (nunca hardcode).

## 6. Fases e esforço (estimativa)

| Fase | Entrega | Esforço |
|---|---|---|
| 0 | Obter com a Getnet: `gatewayMerchantId` de sandbox, path/payload exato, resposta às perguntas da seção 7; cadastro no Google Pay & Wallet Console | dependência externa |
| 1 | Backend: método, observer, request builder (com `number_installments`/`transaction_type`), command pool, admin, validação de parcelas/juros, i18n | ~2 dias |
| 2 | Frontend: renderer, select de parcelas + juros nos totais, botão condicionado à parcela, CSP, `isReadyToPay` | ~2–3 dias |
| 3 | Sandbox E2E: à vista e parcelado (com/sem juros), recusado, cancel/refund de pedido parcelado (Google TEST env + `api.pre`) | ~1 dia |
| 4 | 3DS com desafio (se exigido) | ~2 dias |
| 5 | Produção: domínio na allowlist, revisão do Google (screenshots do fluxo) | externo |

Validação estática: PHPCS Magento2, PHPCompatibility 8.1–8.5, PHPStan lvl 1 (não há PHPUnit no módulo; PHPMD
está quebrado no ambiente — ver memória `ambiente-quirks-getnet`).

## 7. Riscos e perguntas em aberto (para a Getnet)

1. Path/método do endpoint de pagamento Google Pay na Global é o mesmo `…/v2/payments`? ⚠️
2. `payment_method` com Authorization Token: fixo `CREDIT`? Cartão de débito com `number_installments` > 1 é recusado como?
3. Cartões de teste/token do ambiente `TEST` do Google funcionam no `api.pre`? (doc cita `brand=UNKNOWN`.)
4. 3DS é exigido no BR para Authorization Token? Em quais casos vem `PENDING`?
5. Capture posterior, cancelamento e estorno são suportados para transações Google Pay (e `CREDIT_AUTHORIZATION`)?
6. ~~Há webhook específico ou o evento de cartão cobre?~~ Irrelevante: webhook descartado (ver 4.7).
7. `gatewayMerchantId`: é por merchant/seller? Muda entre sandbox e produção? (impacta config por escopo/store.)
8. Obrigatoriedade de `additional_data.device`/antifraude para este fluxo (o módulo já envia no Cc).

Riscos técnicos: token modificado por serialização (usar string crua); CSP restrita bloqueando `pay.js`;
Google exige HTTPS e domínio aprovado (não funciona em `localhost` sem `TEST`); botão exibido só se
`isReadyToPay` (usuário sem cartão no Google Wallet não vê o método); a Global não tem Wallet V2, então o
método precisa ficar oculto em `api_v2`.

## 8. Decisão sugerida

Implementar o **Authorization Token flow, com parcelamento (com e sem juros) reaproveitando a infraestrutura do Cc, apenas Global/BR**, reaproveitando ao máximo o
pipeline do Cc (Fases 0–3). Tratar 3DS com desafio (Fase 4) conforme resposta da Getnet.

## 9. Status da implementação

**Fase 1 (backend) — feita, não commitada.** `setup:di:compile`, PHPCS (Magento2), PHPCompatibility 8.1–8.5 e PHPStan lvl 1 limpos nos arquivos novos.

- Método `getnet_paymentmagento_googlepay` (BR/BRL, só `api_global`): `ConfigGooglePay` (força `active=false` em `api_v2`), `ConfigProviderGooglePay`, `DataAssignObserverGooglePay`, `Gateway/Request/GooglePay/PaymentDataRequest`, `Gateway/Validator/GooglePay/ResponseValidator`, `Block/Sales/Info/GooglePay`, source models do admin, grupo no `system.xml`, defaults, DI, eventos e i18n.
- **Venda imediata (`CREDIT`), sem autorização+captura posterior:** o token do Google é de uso único e não pode ser reenviado numa captura tardia. Reembolso, cancelamento e void reutilizam as operações do Cc.
- **Parcelamento:** herda as configurações, o cálculo e os totais de juros do Cc; o servidor valida `1 ≤ parcela ≤ máximo`.
- **Mapping de erros** (`etc/getnet_error_mapping.xml`): `GOOGLEPAY-001` (token inválido), `GOOGLEPAY-002` (parcelamento indisponível para cartão de débito), `GOOGLEPAY-003` (parcela inválida). O `ResponseValidator` converte `PAYMENTS-083`/`PAYMENTS-013` em `GOOGLEPAY-002` quando o pedido tem parcelas > 1. ⚠️ Esses dois códigos de origem são hipótese: ajustar a lista `INSTALLMENT_REFUSED_CODES` quando a Getnet confirmar o código real.

**Fase 2 (frontend) — feita, não commitada, não testada em navegador.** Renderer `getnet_paymentmagento_googlepay.js` + `googlepay.html`, registro em `getnet.js` e no layout, CSP (`pay.google.com`, `gstatic.com`, `google.com`).
- Select de parcelas (mesmas regras do Cc) → `set-interest` → botão do Google Pay só aparece com parcela escolhida e total final já com juros. `set-interest.js` agora devolve uma promise (resolve após atualizar os totais; chamadores existentes ignoram o retorno).
- Trocar a parcela descarta o token; o token é limpo logo após `placeOrder`. Se `isReadyToPay` for falso, o método mostra aviso e não oferece o botão.
- Fingerprint antifraude reutiliza a sessão do Cc.

Pendente: Fase 3 (sandbox E2E, depende do `gatewayMerchantId`).

**Decisão: sem desafio 3DS neste projeto (Fase 4 cancelada).** Se a Getnet responder `PENDING` com link `3ds_html`, o `ResponseValidator` recusa o pagamento com `GOOGLEPAY-004` (mapping) em vez de deixar um pedido preso aguardando autenticação. Verificar no navegador se a CSP precisa de mais hosts do Google.
