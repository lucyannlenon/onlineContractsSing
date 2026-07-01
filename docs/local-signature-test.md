# Teste local de assinatura com Chromium

Este ambiente sobe banco, API e webhook isolados para validar o fluxo de contrato por template antes de producao.

## Subir ambiente

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test up --build
```

Em outro terminal:

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test php bin/console app:generate-keys
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test php tests/chromium_pdf_smoke.php
```

## Criar contrato

Use `restTest/local-signature-template.http` ou:

```bash
curl -sS -X POST 'http://localhost:9005/api/contract/add-template?token=local-signature-token' \
  -H 'Content-Type: application/json' \
  -d '{"cpf":"12345678901","birthday":"1990-01-15","contractTemplate":"<!doctype html><html><body><h1>Contrato local</h1><p>Teste de assinatura.</p></body></html>"}'
```

Guarde o `accessKey` retornado.

## Assinar pela tela

Abra `http://localhost:9005`, informe:

- CPF: `12345678901`
- Data de nascimento: `1990-01-15`
- Chave: `accessKey` retornado pela API

Aceite o contrato na tela. O aceite cria a assinatura digital e marca o contrato como finalizado.

## Gerar PDF e validar saida

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test php bin/console app:test
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test find var/local-storage -maxdepth 1 -type f -name '*.pdf' -print
```

Para validar tambem a notificacao final ao webhook local:

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test exec php-test php bin/console app:notify:finish
```

As notificacoes enviadas ao webhook local ficam em `/tmp/contrato-online-webhook.log` dentro do container `webhook-test`.

## Derrubar ambiente

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test down
```

Para apagar tambem o banco local:

```bash
docker compose -f docker-compose.signature-test.yml --env-file .env.signature-test down -v
```
