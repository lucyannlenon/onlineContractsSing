# Spec: Encadear assinatura de múltiplos contratos por CPF

**Data:** 2026-06-30
**Status:** proposta
**Repos envolvidos:** `contrato-online` (este — única mudança), `suporte-symfony` (nenhuma mudança)

---

## Problema

Uma subscription do combo pode gerar **N contratos** (ex.: Termo de Contratação + Termo de
Permanência). Hoje, ao gerar, o `suporte-symfony` faz uma chamada por documento a
`POST /api/contract/add-template`. Cada chamada cria uma linha `Contracts` independente com um
`accessKey` aleatório próprio.

No portal de assinatura, o login (`MainController::checkCredentials`) usa:

```php
$item = $repository->findOneBy(['cpf' => ..., 'accessKey' => $key, 'birthday' => ...]);
return $this->redirect("/accept-contract/{$item->getId()}");
```

Ou seja: o cliente entra com **uma** chave, assina **aquele** contrato e cai em `/finish`. Os
demais contratos (com outras `accessKey`) nunca são apresentados. Resultado real observado
(subscription 140):

| id | template | accessKey | status |
|----|----------|-----------|--------|
| 5  | 19       | 43343     | assinado |
| 6  | 25       | 79669     | **aguardando_aceite (nunca apareceu)** |

## Causa-raiz

O modelo é **1 accessKey = 1 documento**, e o portal apresenta exatamente um contrato por login.
Não há encadeamento entre os contratos do mesmo cliente/lote.

## Decisão de design

**Encadear por CPF no portal**, sem alterar a entidade `Contracts` nem o `suporte-symfony`.

Por quê não mexer no `suporte-symfony`:
- O webhook `SingRemoteContract::sing` já casa por `contractKey` e marca **um** contrato como
  `ACTIVE` por notificação FINISH (cada um chega com seu `accessKey` e 1 link → passa a validação
  `count($links) === 1`).
- `AllContractsSignedChecker::allSigned` só ativa a subscription quando **nenhum** contrato está
  em `WAITING_ACCEPTANCE`. Ele já espera todos serem assinados, em momentos distintos.

Logo, basta o portal levar o cliente do contrato recém-assinado para o **próximo pendente** antes
de mostrar a tela de sucesso.

## Escopo do "próximo contrato pendente" (CRÍTICO)

A tabela `Contracts` **não tem agrupamento** (sem subscription, sem lote). Campos disponíveis:
`cpf`, `accessKey`, `birthday`, `contractType`, `finish`, `notified`, `createdAt`.

Um `findBy(['cpf' => x, 'finish' => false])` **puro é perigoso**: puxaria qualquer contrato
pendente daquele CPF, inclusive tentativas antigas/abandonadas ou de subscriptions anteriores,
levando o cliente a assinar lixo velho.

Por isso o critério de encadeamento deve ser **escopado**:

- mesmo `cpf`
- mesmo `birthday`
- `finish = false`
- `createdAt` dentro de uma janela próxima ao contrato que acabou de ser assinado
  (ex.: ±10 minutos — contratos do mesmo lote são criados no mesmo request, com `createdAt`
  praticamente idêntico)
- `id != contrato atual`
- ordenar por `id ASC` (ordem estável de assinatura)

> A janela de tempo é a salvaguarda contra lixo antigo. Como o lote inteiro é persistido no mesmo
> `flush()` do `suporte-symfony`, a diferença de `createdAt` entre irmãos é de segundos.

## Mudança no código

### 1. `ContractsRepository` — novo método de busca escopada

```php
/**
 * Próximo contrato pendente do mesmo cliente/lote, para encadear a assinatura.
 *
 * Escopo: mesmo CPF + mesmo birthday + não finalizado + criado na mesma janela
 * do contrato de referência. Evita arrastar contratos antigos/abandonados do CPF.
 */
public function findNextPendingForBatch(Contracts $reference): ?Contracts
{
    $window = 600; // segundos (±10 min)
    $createdAt = $reference->getCreatedAt();

    return $this->createQueryBuilder('c')
        ->andWhere('c.cpf = :cpf')
        ->andWhere('c.birthday = :birthday')
        ->andWhere('c.finish = :finish')
        ->andWhere('c.id != :id')
        ->andWhere('c.createdAt BETWEEN :from AND :to')
        ->setParameter('cpf', $reference->getCpf())
        ->setParameter('birthday', $reference->getBirthday())
        ->setParameter('finish', false)
        ->setParameter('id', $reference->getId())
        ->setParameter('from', $createdAt->modify("-{$window} seconds"))
        ->setParameter('to', $createdAt->modify("+{$window} seconds"))
        ->orderBy('c.id', 'ASC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
```

> Verificado na entidade `Contracts`: `getCreatedAt(): ?\DateTimeImmutable` (logo `->modify()`
> retorna nova instância — seguro), `getCpf()`, `getBirthday()`, `getId()` existem; `createdAt` é
> setado no construtor (toda linha tem valor). O getter de finish é `isFinish()`, mas o query
> builder usa o campo mapeado `c.finish`, então não interfere.

### 2. `MainController::saveAll` (`/finish/{contract}`) — ponto único de encadeamento

`/finish` é o funil comum: tanto o fluxo TEMPLATE (`acceptContractTemplate`) quanto o DEFAULT
(`acceptTerm` / `grantingBenefits`) terminam redirecionando para `/finish/{id}`. Colocar o
encadeamento aqui cobre todos os tipos com uma única alteração.

Estado no momento do `/finish`: o contrato recém-assinado **já** terá `finish = true` (setado por
`finishContract`), portanto sai naturalmente da busca de pendentes.

```php
#[Route('/finish/{contract}', name: 'app_finish')]
public function saveAll(
    Contracts $contract,
    CreateContractService $contractService,
    ContractsRepository $contractsRepository
): Response {
    $this->logger->info('Finalizing contract', ['contract_id' => $contract->getId()]);

    // marca o atual como finalizado (finish = true, notified = false)
    $contractService->finishContract($contract);

    // encadeia: há outro contrato pendente do mesmo lote/CPF?
    $next = $contractsRepository->findNextPendingForBatch($contract);
    if ($next !== null) {
        $this->logger->info('Chaining to next pending contract', [
            'from_contract_id' => $contract->getId(),
            'next_contract_id' => $next->getId(),
        ]);
        return $this->redirect('/accept-contract/' . $next->getId());
    }

    // nenhum pendente → encerra
    return $this->render('main/success.html.twig', []);
}
```

> `/accept-contract/{id}` já roteia internamente para o fluxo certo conforme `contractType`
> (TEMPLATE renderiza direto; DEFAULT/null redireciona para `/accept-term`). Encadear para ele
> mantém a lógica de tipo centralizada.

## Fluxo resultante

1. Cliente entra com CPF + **qualquer** uma das chaves do lote + birthday.
2. Assina o contrato apresentado → `/finish/{id}`.
3. `/finish` finaliza esse contrato e procura o próximo pendente do **mesmo lote** (CPF + birthday +
   janela de criação).
4. Se houver, redireciona para `/accept-contract/{próximo}`; o cliente assina o próximo.
5. Quando não houver mais pendentes → tela de sucesso.
6. Cada contrato dispara FINISH para o `suporte-symfony` de forma independente (scheduler
   `NotificationFinishContractTask`, 80s). O `suporte` marca cada um `ACTIVE` e só ativa a
   subscription quando todos saem de `WAITING_ACCEPTANCE`.

## Casos de borda

- **Lote de 1 contrato:** `findNextPendingForBatch` retorna `null` → comportamento atual (sucesso
  direto). Sem regressão.
- **Cliente abandona no meio:** os já assinados ficam `finish = true` e são notificados; os
  pendentes permanecem para uma próxima sessão. Ao reentrar com qualquer chave válida do lote, o
  encadeamento retoma a partir do que falta (a janela de `createdAt` ainda os agrupa).
- **Contratos antigos do mesmo CPF:** ficam fora da janela de `createdAt` → não são arrastados.
- **Mesma janela, lotes distintos do mesmo CPF/birthday no mesmo intervalo:** cenário improvável
  (exigiria duas subscriptions geradas em minutos). Se virar requisito real, o caminho correto é
  adicionar um campo de lote/grupo em `Contracts` — fora do escopo desta mudança.

## O que NÃO muda

- Entidade `Contracts` (sem migration).
- `suporte-symfony` (geração, webhook, checker já suportam N contratos independentes).
- Endpoints `/api/contract/add` e `/api/contract/add-template`.
- Geração de assinatura, notificação FINISH e scheduler.
