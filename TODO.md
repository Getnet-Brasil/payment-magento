## Simular uso da idepotencia...

Gerar erro no salve lado magento e analisar o comportamento.

Detectado Problema, posso usar o QuoteId ele é único até o request aprovado (ao contrário do increment id), no entanto o response de uma idepotencia é divergente da response original, impossibilitando o fluxo de pagamento, talvez possamos montar o consult idepotencia para verificar se o body é viavel...

## Adminhtml

[x] Refazer sessão de developers
[x] remover info sobre o deburar
[x] remover sandbox?
[x] arrumar a parte de webhook informar url admin correta

## Erro MAP

[x] Refazer mapa de erros


## Testar multistore

[x] Usar duas contas e ver se autenticação persiste por store ou não (tomará que sim), persiste a solução é desativar o cache se for uma multistore!


## Tradução

bin/magento i18n:collect-phrases -o "app/code/Getnet/PaymentMagento/i18n/en_US.csv" app/code/Getnet/PaymentMagento

## Fazer o Fetch

Implementar o fetch nos métodos offline.


## IMPORTANTE

Getnet já aceita acentos nas transações por boleto??????????????