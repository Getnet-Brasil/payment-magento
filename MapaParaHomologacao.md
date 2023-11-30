## Captura de logs das Transações

Processos recomendados para testes de homologação.

### Captura de log de transações na V2

Para transações por Cartão, Cofre, Pix e Boleto use:

```bash
grep -C 4 -m 1 '"idempotency_key":"{{NUMERO_DO_PEDIDO}}"' var/log/payment.log | grep -C 5 'url'
```
onde {{NUMERO_DO_PEDIDO}} deve ser substituido pelo pedido corrente para coletar o log apropriado.

Exemplo:
```bash
grep -C 3 -m 1 '"idempotency_key":"000000520"' var/log/payment.log | grep -C 5 'url'
```

Saida:
```bash
[2023-11-29T19:30:26.505748+00:00] main.DEBUG: array (
  'url' => 'https://api-homologacao.getnet.com.br/v2/payments/qrcode/pix',
  'header' => '{"Authorization":"Bearer d89535d9-7ea3-4381-ab25-3d0387b71b7f","Content-Type":"application\\/json","x-transaction-channel-entry":"MG","x-qrcode-expiration-time":30}',
  'payload' => '{"idempotency_key":"000000520","amount":5900,"currency":"BRL","order_id":"000000520","customer_id":"aceitei@getnet.com.br"}',
  'response' => '{"payment_id":"cb3a13ac-bf28-4504-bd44-77284a3750f5","status":"WAITING","description":"O QR Code foi gerado com sucesso e aguardando o pagamento. (81).","additional_data":{"transaction_id":"05001000000052195901000171942","qr_code":"00020101021226870014br.gov.bcb.pix2565pix-h.santander.com.br\\/qr\\/v2\\/eef4b96a-eed8-4ab1-92f3-4950289608bd520458125303986540559.005802BR5925EMPRESAS CLIENTE LTDA PIX6015SAO JOSE DO VAL62070503***63040DD3","creation_date_qrcode":"2023-11-29T16:30:26Z","expiration_date_qrcode":"2023-11-29T16:30:56Z","psp_code":"0033"},"idempotency_key":"000000520"}',
  'error_msg' => NULL,
) [] []
```

### Captura de log de transações na V1

Para transações por 2 Cartões e Wallet, use:

```bash
grep -C 4 -m 1 '"order_id":"{{NUMERO_DO_PEDIDO}}"' var/log/payment.log | grep -C 5 'url'
```

Para Getpay, use:

```bash
 grep -C 3 -m 1 '"order_prefix":"000000528"' var/log/payment.log | grep -C 5 'url'
```

## Cenários para teste de compra

Verificar métodos presentes em seu contrato e então realizar os testes.


### Cartão de Crédito

Há 2 fluxos distintos com a Captura e Autorização (default no módulo) e somente a Autorização.

#### Com fluxo de apenas autorização

-  [ ] Pgto fazendo a criação do cofre (vault)
-  [ ] Pgto SEM a criação do cofre (vault)
-  [ ] Pgto via Cofre

#### Com fluxo de autorização e captura

-  [ ] Pgto fazendo a criação do cofre (vault)
-  [ ] Pgto SEM a criação do cofre (vault)
-  [ ] Pgto via Cofre

### Boleto

-  [ ] Compra

### Pix

-  [ ] Compra

### Wallet

-  [ ] Compra

### Getpay

-  [ ] Compra

### TwoCc

-  [ ] Compra

## Callback

Em Homolog
  Soliticar cadastro e liberação da url para recebimento do Callback no ambiente de homologação.

Em Produção
  Cadastrar no painel da Getnet a url e solicitar a liberação no firewall.

## Teste de operação

Ápos a compra é necessário homologar alguns processo de gestão do pedido.

### CC e Cofre
-  [ ] Capturar
-  [ ] Negar
-  [ ] Reembolsar

### Two cc
-  [ ] Reembolsar
-  [ ] Capturar (server de homolog indisponível)
-  [ ] Negar  (server de homolog indisponível)

### Pix
-  [ ] Webhook - Aprovado
-  [ ] Webhook - Cancelado

### Pix
-  [ ] Webhook - Boleto
-  [ ] Webhook - Boleto

### Getpay
-  [ ] consultar a expiração
-  [ ] Webhook - Aprovado
-  [ ] Webhook - Cancelado

### Wallet
-  [ ] consultar o pagamento
-  [ ] Webhook - Aprovado
-  [ ] Webhook - Cancelado

