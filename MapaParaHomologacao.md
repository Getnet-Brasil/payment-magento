## Teste compra

Processos recomendados para testes de homologação.

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
