# Redesign visual do fluxo de assinatura de contrato IZI

**Data:** 2026-07-01
**Escopo:** Todo o fluxo de assinatura (5 telas Twig)
**Direção:** Fintech clean · paleta laranja + amarelo + branco (marca IZI)

## 1. Objetivo

Dar ao fluxo de assinatura de contrato uma aparência profissional e moderna,
consistente entre todas as telas, ancorada na identidade IZI (laranja, amarelo,
branco). Hoje as telas usam Bootstrap 5 cru com estilos inline, o template de
contrato injetado (`contract.template | raw`) vem sem estilo, e o bloco de
assinatura digital aparece dentro de um `<pre>` com borda tracejada preta.

## 2. Telas envolvidas

| Template | Papel | Situação hoje |
|---|---|---|
| `templates/base.html.twig` | Casca (head, Bootstrap CDN) | Sem tema próprio |
| `main/index.html.twig` | Login (CPF/chave/nascimento) | Card estreito básico |
| `main/accept-term.html.twig` | Termo de aceite | Texto corrido + `<pre>` assinatura |
| `main/accept-contract.html.twig` | Renderiza `contract.template \| raw` | Mais cru; tpl sem estilo |
| `main/granting-benefits.html.twig` | Termo de concessão de benefícios | Seções + tabela de multa |
| `main/success.html.twig` | Confirmação | Já tem logo/marca parcial |

## 3. Arquitetura (reduzir duplicação)

O bloco de assinatura e o CTA "Aceitar e continuar" estão duplicados em 3
templates (`accept-term`, `accept-contract`, `granting-benefits`). Centralizar:

- **Tema em `base.html.twig`** — `{% block stylesheets %}` com CSS de design
  tokens IZI (variáveis de cor, fonte Inter, sombras, radius), header de marca,
  footer de contato e estilos base para `.contract-document`. Todas as telas
  herdam.
- **`main/_signature.html.twig`** (partial) — bloco de assinatura digital,
  incluído via `{% include %}` nas 3 telas de aceite (condicional a `signature`).
- **`main/_accept-button.html.twig`** (partial) — CTA de aceite, incluído via
  `{% include %}` (condicional a `enable_btn`). Preserva o link atual que faz
  merge de `accept-key: 1` na rota corrente.
- **`main/_stepper.html.twig`** (partial) — indicador de progresso; recebe o
  passo atual por variável.

Cada partial tem uma responsabilidade única e é incluído por interface simples
(variáveis Twig do contexto). Nenhuma mudança em controllers/serviços PHP.

## 4. Design tokens (fintech clean)

```
--izi-orange:      #F97316   /* primário: botões, links, passo ativo */
--izi-orange-dark: #EA580C   /* hover/pressed */
--izi-yellow:      #FBBF24   /* realce: badges, faixa gradiente, fundo do hash */
--izi-bg:          #FAF9F7   /* fundo morno da página */
--izi-surface:     #FFFFFF   /* cards */
--izi-ink:         #1F2937   /* texto principal */
--izi-muted:       #6B7280   /* texto secundário */
--izi-border:      #E7E5E4   /* bordas sutis */
--izi-radius:      16px
--izi-shadow:      0 4px 24px rgba(17,24,39,.08)
Fonte: Inter (Google Fonts), fallback system-ui
```

## 5. Casca compartilhada

- **Header** branco fino: wordmark **izi** à esquerda + faixa fina com gradiente
  laranja→amarelo abaixo do header.
- **Stepper** (topo do conteúdo nas telas de aceite): `Termo → Contrato →
  Benefícios → Concluído`. Passo atual em laranja preenchido; passos concluídos
  com check; futuros em cinza. Cada tela passa seu índice ao partial.
- **Footer** slim reusando o contato de `success` (email, telefone, WhatsApp).
- Conteúdo centralizado em coluna com largura máxima confortável de leitura.

## 6. Tratamento por tela

- **index (login):** card branco central com sombra, título forte, inputs
  maiores com estado de foco laranja, botão primário laranja full-width. Mantém
  as máscaras JS de CPF/CNPJ e data existentes.
- **accept-term / granting-benefits / accept-contract:** conteúdo dentro de um
  wrapper `.contract-document` (papel branco, padding generoso). O CSS base
  estiliza `h3/h4/p/table` dentro desse wrapper, então até o
  `{{ contract.template | raw }}` cru herda tipografia, espaçamento e tabelas
  consistentes sem tocar no conteúdo injetado.
- **success:** restyle para casar com o tema (check de sucesso, card, footer
  padrão).

## 7. Bloco de assinatura digital (partial)

Substitui o `<pre>` tracejado por um painel **"Assinatura Digital Verificada"**:

- Card com borda sutil e ícone de cadeado/impressão digital.
- O hash da assinatura em fonte monoespaçada, com `word-break`, dentro de caixa
  de fundo levemente amarelo (`--izi-yellow` a baixa opacidade) e borda suave.
- Data de assinatura como campo rotulado ("Assinado em").
- Ar de selo/certificado (badge verde de "verificado").

## 8. CTA de aceite (partial)

- Botão laranja destacado (substitui `btn-success`).
- Em telas de documento longo, **barra fixa no rodapé** no mobile para o CTA
  ficar sempre alcançável; inline no desktop.
- Mantém exatamente o comportamento de rota atual (merge `accept-key: 1`).

## 9. Não-objetivos (YAGNI)

- Sem mudança de lógica PHP, rotas ou fluxo de dados.
- Sem framework de build/assets novo (mantém Bootstrap CDN + CSS próprio no
  `{% block stylesheets %}`).
- Sem alterar o conteúdo textual/jurídico dos termos.
- Sem internacionalização.

## 10. Verificação

- Subir stack local (`docker compose up`, app em `http://localhost:9004`) e
  percorrer as 5 telas visualmente.
- Conferir que o template injetado cru em `accept-contract` fica estilizado.
- Conferir bloco de assinatura e stepper nas 3 telas de aceite.
- Conferir responsividade (mobile: CTA fixo, stepper compacto).