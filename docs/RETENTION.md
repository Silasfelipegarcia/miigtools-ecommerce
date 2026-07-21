# Retenção e abandono de carrinho — MIIGTOOLS

## Contexto

E-mail transacional de abandono só faz sentido com SMTP de produção estável.
Enquanto isso, o caminho operacional é **WhatsApp + GA4**.

## Playbook (operacional)

1. No GA4, acompanhar eventos `begin_checkout` sem `purchase` (já instrumentados na loja).
2. Clientes que iniciam checkout e param: contatar no WhatsApp comercial (`551122360122`) com mensagem do tipo:
   > Olá! Vi que você começou um pedido na MIIGTOOLS. Posso ajudar com frete, medida ou pagamento?
3. Pedidos recorrentes: orientar o cliente a usar **Minha conta → Histórico → Comprar todos novamente**.

## Quando ligar e-mail automático

- SMTP de produção validado (sem bounce).
- Preferência: extensão/marketing OpenCart ou job que leia carrinhos com e-mail capturado no checkout guest.
- Não disparar para visitantes anônimos sem e-mail.

## Métricas

- Taxa begin_checkout → purchase
- Reorders pela conta
- Conversas WA originadas de abandono
