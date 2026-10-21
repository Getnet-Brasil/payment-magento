# Estudo — Apple Pay (Getnet Global / Regional API)

> Fonte: https://docs.globalgetnet.com/pt/products/online-payments/regional-api?doc=apple-pay
> Base de comparação: implementação do Google Pay (`ESTUDO-GOOGLE-PAY.md`, já em produção no sandbox).
> Status: **estudo — nada implementado**.

## 1. O que a doc define

- Disponível só em **Brasil e Espanha**; frontend em **HTTPS**; bandeiras **Mastercard e Visa**.
- Wallet na Global: `wallet.id = 103`, `wallet.type = 10` (padrão). `type 55` + `tag AP` é **SDWO** (fund transfer) — fora do escopo de e-commerce.
- Modelos de processamento (não se misturam na mesma requisição):

| Modelo | Quem descriptografa | PCI | Envio |
|---|---|---|---|
| DAN card data | o lojista | escopo PCI-DSS | `card.number` (DAN) + validade |
| Cryptogram | o lojista | escopo PCI-DSS | DAN + validade + `tokenization.cryptogram/eci` |
| **Authorization Token** | **a Getnet** | **sem escopo** | `wallet.authorization_token` = Base64 do `payment.token` |

- **Recomendação: Authorization Token flow** (mesmo do Google Pay). O módulo nunca toca em DAN/criptograma; o token do Apple (`payment.token`) é serializado em JSON "exatamente como retornado" e codificado em Base64.
- Payload mínimo: `payment_method CREDIT`, `transaction_type`, `number_installments`, `wallet {id 103, type 10, authorization_token}` — idêntico ao do Google Pay, só mudam `wallet.id` (216→103) e a origem do token.
- Resposta: `APPROVED` / `DECLINED` / `PENDING` (3DS). Mesmos campos da resposta do Google Pay (`payment_id`, `transaction_id`, `authorization_code`, `brand`, `reason_code`…).
- Matriz: compras, estornos, estornos parciais e pré-autorizações suportados no BR. A página **não** traz endpoint, payload de estorno/cancelamento, lista de erros nem ambiente de teste.
- `merchantCapabilities: ["supports3DS"]` e `supportedNetworks: ["masterCard","visa"]` são parâmetros do **Apple Pay JS** (capacidade do dispositivo), **não** o 3DS da Getnet. `supports3DS` é o valor padrão exigido pela Apple e deve ser mantido.

## 2. O que é diferente do Google Pay (o ponto central)

O Google Pay é 100% client-side + token. O Apple Pay exige **infraestrutura própria da loja**:

| Item | Google Pay | Apple Pay |
|---|---|---|
| Cadastro | `gatewayMerchantId` da Getnet | **Apple Developer**: Merchant ID, Payment Processing Certificate (CSR pedido à Getnet), Merchant Identity Certificate |
| Domínio | whitelist/console Google | **arquivo** `/.well-known/apple-developer-merchantid-domain-association` servido no domínio do checkout |
| Validação do lojista | não existe | **Merchant Validation**: POST do servidor à Apple (`validationURL`) com TLS mútuo (certificado + chave privada) a cada sessão |
| Disponibilidade | Chrome/Android e outros | **Safari** (iOS/macOS) e, no iOS 18+/macOS, outros navegadores via QR/dispositivo; `ApplePaySession.canMakePayments()` |
| Backend novo | nada | **endpoint de merchant validation** (controller) + guarda de certificado/chave |
| Tipo do cartão | não informa débito/crédito | `token.paymentMethod.type` = `debit`/`credit`/`prepaid`/`store` (permite tratar débito + parcelas melhor) |

## 3. Aderência ao módulo atual

Reaproveita o que o Google Pay já consolidou (ordem de custo baixo → alto):

- **Backend de pagamento**: `Client\Card\CreateClient`, endpoint de cartão, `TxnIdHandler` (já tolerante a bandeira desconhecida), `ExtOrdIdHandler`, `TransactionCaptureHandler`, `DetailsHandler` (dados só admin), validators e error mapping.
- **Cancel/refund**: comandos próprios do Google Pay que enviam `payment_method CREDIT` (não há autorização separada) — replicar para Apple Pay.
- **Parcelamento/juros**: `ConfigCc`, `InterestManagement`, `set-interest.js`, `calculate-installment.js` (já retorna promise).
- **Padrão de config/UI**: ConfigProvider, observer de `additional_data`, bloco Info, renderer Knockout, template, i18n.
- **Não reaproveita**: carregamento do `pay.js` e botão do Google; precisa do SDK nativo `ApplePaySession` (built-in, sem script externo — apenas o CSS/botão `apple-pay-button`).

## 4. Desenho proposto

### 4.1 Método
`getnet_paymentmagento_applepay` (Global-only, BR/BRL, `payment_action authorize_capture`, venda imediata). Naming `applepay` em tudo; "Wallet" continua sendo o QR da V2.

### 4.2 Admin
Grupo `getnet_paymentmagento_applepay` (depends `*/getnet_base/api_type` = `api_global`): active, title, **Apple Merchant ID**, **display name** (≤ 64), **Merchant Identity Certificate** (PEM) e **chave privada** (backend `Encrypted`; ou caminho de arquivo fora do webroot), senha da chave (Encrypted), button style/type, min/max total, países, sort order.
- Segredos: **nunca** logar nem enviar ao front; certificado/chave só no servidor.
- Alternativa ao PEM no banco: caminhos de arquivo (`var/` ou fora de `pub`) com permissão restrita — decidir com o time de infra.

### 4.3 Frontend (`getnet_paymentmagento_applepay.js`)
1. Disponibilidade: `window.ApplePaySession && ApplePaySession.canMakePayments()` (em falha, esconder com mensagem como no Google Pay).
2. **Parcelamento antes da folha** (mesmo desenho do Google Pay: select de parcelas → `set-interest` → total final).
3. Clique do botão (gesto do usuário — obrigatório): `new ApplePaySession(version, request)` com `countryCode BR`, `currencyCode BRL`, `supportedNetworks ['masterCard','visa']`, `merchantCapabilities ['supports3DS']`, `total {label, amount: grand_total}`. Pedir contato só se necessário (o endereço de cobrança já vem do checkout).
4. `onvalidatemerchant` → `POST` ao controller próprio com `validationURL` → `completeMerchantValidation(merchantSession)`.
5. `onpaymentauthorized` → `event.payment.token` serializado com `JSON.stringify` **sem alterar**, enviado em `additional_data.applepay_token` (+ `cc_installments`, `cc_type` = `token.paymentMethod.network`, `cc_number` = `paymentMethod.displayName` mascarado); `placeOrder()`; `completePayment(STATUS_SUCCESS|STATUS_FAILURE)` conforme o resultado. Token de uso único: nunca guardado.
6. Botão: `<apple-pay-button>` / CSS `-webkit-appearance: -apple-pay-button`.

### 4.4 Backend
- **Controller de merchant validation** (`getnet/applepay/validate`, POST, CSRF/form key): valida `validationURL` (host precisa terminar em `apple.com` — evita SSRF), monta o corpo (`merchantIdentifier`, `displayName`, `initiative web`, `initiativeContext` = host da loja) e chama a Apple com cert+chave via `Gateway/Http/` (regra do módulo: nenhuma chamada externa fora dessa camada). Devolve o `merchantSession` intacto. Sem cache.
- `Gateway/Config/ConfigApplePay`, `Model/Ui/ConfigProviderApplePay`, `Observer/DataAssignObserverApplePay`, `Block/Sales/Info/ApplePay`.
- `Gateway/Request/ApplePay/PaymentDataRequest`: como o do Google Pay, com `wallet {id 103, type 10, authorization_token = base64(token)}`; `payment_method CREDIT` (ver pergunta 2).
- Reuso: `ResponseValidator` do Google Pay (generalizar ou subclasse) para `PENDING`/3DS → mensagem `APPLEPAY-004`; `DetailsHandler` generalizado.
- Error mapping: `APPLEPAY-001..004` (token inválido, parcelamento indisponível no débito, parcela inválida, autenticação adicional indisponível).

### 4.5 Domínio (`.well-known`)
Servir `/.well-known/apple-developer-merchantid-domain-association` (arquivo da Apple, por domínio/ambiente). O Magento não serve `.well-known` por padrão: opções (a) arquivo estático em `pub/.well-known/` + regra no nginx; (b) controller + rewrite que lê o conteúdo de config. **Recomendado (a)** — é infra, documentar no README; a (b) só se o deploy não permitir arquivo.

### 4.6 Parcelamento (essencial no BR)
Igual ao Google Pay: parcelas calculadas pelo servidor (`InterestManagement`), validadas em `PaymentDataRequest`, `transaction_type` por `getTypeInterestByInstallment`, total final da folha = grand total com juros.
Melhoria possível: como o Apple informa `paymentMethod.type`, em `onpaymentmethodselected` dá para **detectar cartão de débito** e zerar o parcelamento/atualizar o total para o valor à vista (`completePaymentMethodSelection`) antes da autorização, em vez de depender do erro `GOOGLEPAY-002` da Getnet. Avaliar após confirmar o comportamento real da API.

### 4.7 3DS
Não há desafio 3DS no projeto. `PENDING` com `3ds_html` é recusado (`APPLEPAY-004`), como no Google Pay. No BR o 3DS é opcional (depende de emissor/risco).

### 4.8 Webhooks
Descartados (fluxo síncrono, venda imediata) — mesma decisão do Google Pay (`Notification/All` já devolve 412 para pedido fora de `new`).

### 4.9 CSP
Sem script externo do Apple Pay JS (nativo). Avaliar `connect-src`/`img-src` para o botão/ícone; a chamada de merchant validation é feita pelo servidor (não precisa CSP).

## 5. Arquivos a criar/alterar

| Camada | Arquivo |
|---|---|
| Config/DI/Events | `etc/config.xml`, `etc/adminhtml/system.xml`, `etc/di.xml`, `etc/events.xml`, `etc/frontend/di.xml`, `etc/frontend/routes.xml` (se necessário) |
| Config PHP | `Gateway/Config/ConfigApplePay.php`, `Model/Ui/ConfigProviderApplePay.php`, source models de botão |
| Request/Validator/Response | `Gateway/Request/ApplePay/PaymentDataRequest.php`, `Gateway/Request/ApplePay/Operations/PaymentMethodDataRequest.php` (CREDIT), validator e handlers (generalizar os do Google Pay) |
| Merchant validation | `Controller/ApplePay/Validate.php` + client em `Gateway/Http/Client/ApplePay/MerchantValidationClient.php` (TLS mútuo) |
| Observer/Block | `Observer/DataAssignObserverApplePay.php`, `Block/Sales/Info/ApplePay.php` |
| Front | `getnet_paymentmagento_applepay.js`, `template/payment/applepay.html`, `getnet.js`, `checkout_index_index.xml` |
| i18n/Docs | `pt_BR.csv`, `en_US.csv`, `getnet_error_mapping.xml`, `CLAUDE.md`/README |
| Infra | arquivo `.well-known` + regra nginx |

## 6. Fases e esforço (estimativa)

| Fase | Entrega | Esforço |
|---|---|---|
| 0 | Getnet: CSR do Payment Processing Certificate, respostas da seção 7; Apple Developer: Merchant ID + Identity Certificate + verificação do domínio (**dependência externa, a mais longa**) | externo |
| 1 | Backend: config, request, validator, handlers, cancel/refund CREDIT, error mapping, admin | ~1 dia |
| 2 | Merchant validation (controller + client TLS mútuo + segurança do `validationURL`) | ~1 dia |
| 3 | Frontend: renderer, template, parcelamento, botão, tratamento de cancelamento/erro | ~1–1,5 dia |
| 4 | Sandbox E2E em dispositivo Apple real (Safari/iPhone), à vista e parcelado, recusa, cancel/refund | ~1 dia |

Total ~4–5 dias de desenvolvimento, **bloqueado pela fase 0** (certificados/domínio; sem eles não abre nem a folha do Apple Pay). Teste exige **dispositivo/Safari reais e domínio HTTPS público verificado** — `localhost`/domínio de dev local não funciona.

## 7. Riscos e perguntas em aberto (para a Getnet)

1. Endpoint de pagamento é o mesmo de cartão (`dpm/payments-gwproxy/v2/payments`) como no Google Pay?
2. `payment_method` no Authorization Token: fixo `CREDIT`? Cartão de débito do Apple Pay deve ir como `DEBIT`? (A doc mostra `DEBIT` só no fluxo DAN.) Débito + parcelas: qual código de erro?
3. Fluxo do CSR/certificado: quem gera o CSR, prazo, um certificado por merchant/seller? Há sandbox do Payment Processing Certificate?
4. Token de teste do Apple (sandbox tester) funciona em `api.pre`? Retorna `brand UNKNOWN`?
5. Cancelamento/estorno total e parcial: endpoint/payload (`CREDIT` vs `CREDIT_AUTHORIZATION`), como no Google Pay?
6. 3DS no BR: quando vem `PENDING`? Dá para evitar?
7. Obrigatoriedade de `additional_data.device` / `finger_print` (a doc cita `finger_print` no exemplo).
8. Um Merchant ID da Apple por loja Magento ou por seller Getnet? Impacta config por escopo.
9. Domínio/store views: o arquivo de verificação é por domínio — lojas multi-domínio precisam de um por host.

Riscos técnicos: (a) certificado/chave no servidor = ativo sensível (rotação anual do Identity Certificate); (b) SSRF no `validationURL` (restringir a `*.apple.com`); (c) cobertura de navegadores menor que a do Google Pay; (d) não há como testar sem dispositivo Apple e domínio verificado.

## 8. Decisão sugerida

Implementar **Authorization Token flow**, método `getnet_paymentmagento_applepay`, Global-only, venda imediata, sem 3DS e sem webhook, parcelamento reaproveitando a infra do Cc/Google Pay. Antes de codar: abrir a **fase 0** (Getnet + Apple Developer + domínio), porque é o caminho crítico. Ao codar, generalizar o que o Google Pay duplicaria (`ResponseValidator`, `DetailsHandler`, comandos de cancel/refund `CREDIT`) em vez de copiar.

## 9. Status da implementação

Decisões: certificados **no banco, criptografados** (backend `Encrypted`), entregues ao cURL como blobs em memória (`CURLOPT_SSLCERT_BLOB`/`SSLKEY_BLOB`, sem arquivo em disco).

Feito (sem teste E2E — **não há conta Apple Developer nem domínio HTTPS verificado ainda**):
- Backend: `ConfigApplePay`, `ConfigProviderApplePay`, `DataAssignObserverApplePay`, `Block/Sales/Info/ApplePay`, `Gateway/Request/ApplePay/PaymentDataRequest` (wallet 103/10, `authorization_token` Base64 do `payment.token` em JSON), `Gateway/Validator/ApplePay/ResponseValidator` (códigos `APPLEPAY-002/004`), cancel/refund com `CREDIT` e `DetailsHandler` reaproveitados do Google Pay, error mapping `APPLEPAY-001..004`.
- Merchant validation: `Controller/ApplePay/Validate` (POST `getnet/applepay/validate`, form key) + `Gateway/Http/Client/ApplePay/MerchantValidationClient` (só `https://*.apple.com`, sem porta/usuário).
- Admin: grupo `getnet_paymentmagento_applepay` (Merchant ID, nome, certificado, chave, senha, botão).
- Frontend: renderer `getnet_paymentmagento_applepay.js` (sessão v3, parcelas antes da folha, débito + parcelas barrado no cliente), template e botão nativo CSS (`-apple-pay-button`), sem script externo (sem CSP novo).
- `di:compile`, PHPCS, PHPCompatibility, PHPStan nível 1 OK.

Pendente (externo): CSR/Payment Processing Certificate com a Getnet, Merchant ID + Merchant Identity Certificate na Apple, arquivo `/.well-known/apple-developer-merchantid-domain-association` no domínio (nginx), e teste em Safari/iPhone. Perguntas da seção 7 continuam abertas (`payment_method` para débito, códigos de erro, estorno).
