# Formações Presenciais - Implementação

## Resumo

Nova funcionalidade de **venda de bilhetes para formações presenciais** na plataforma Clínica do Empresário. Funciona como um sistema de "compra de bilhete" para eventos presenciais com data, local, horário e número máximo de vagas.

---

## O que foi implementado

### 1. Drupal Backend — Novo Tipo de Produto Commerce

**Tipo de Produto:** `formacao_presencial` (Formação Presencial)  
**Tipo de Variação:** `formacao_presencial` (Bilhete Formação Presencial)  
**Tipo de Item de Encomenda:** Utiliza o tipo `default` existente

#### Campos do Produto

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `title` | Texto | Sim | Nome da formação |
| `body` | Texto com resumo | Não | Descrição detalhada da formação |
| `images` | Imagem (múltiplas) | Não | Imagens da formação |
| `field_training_date` | Data/Hora | **Sim** | Data e hora de início |
| `field_training_end_date` | Data/Hora | Não | Data e hora de fim |
| `field_location` | Texto | **Sim** | Nome do local (ex: "Hotel Altis") |
| `field_location_address` | Texto longo | Não | Morada completa |
| `field_max_participants` | Inteiro | **Sim** | Número máximo de vagas (padrão: 30) |
| `field_current_participants` | Inteiro | Não | Inscrições atuais (padrão: 0, atualizado automaticamente) |
| `field_instructor` | Texto | Não | Nome do formador |

#### Variação do Produto
- Cada formação tem **uma variação** com o preço do bilhete
- O preço é definido na variação (ex: 149,00 €)

### 2. Frontend Next.js — Novas Páginas

#### Página de Listagem: `/formacoes-presenciais`
- **Arquivo:** `ricardo/app/formacoes-presenciais/page.tsx`
- Componente servidor que busca todos os produtos `commerce_product--formacao_presencial` via JSON:API
- Exibe cards com data, local, vagas disponíveis, preço e botão "Comprar Bilhete"
- Filtros: Próximas / Anteriores / Todas, pesquisa por texto, filtro por local
- Ordenação por data (próximas: ascendente, anteriores: descendente)

#### Página de Detalhe: `/formacoes-presenciais/[slug]`
- **Arquivo:** `ricardo/app/formacoes-presenciais/[slug]/page.tsx`
- Exibe toda a informação da formação
- Card lateral com preço, seletor de quantidade de bilhetes, barra de ocupação
- Botão de compra que adiciona ao carrinho existente
- Indicadores de confiança (confirmação por email, pagamento seguro, etc.)

### 3. Novos Componentes

| Componente | Caminho | Descrição |
|-----------|---------|-----------|
| `TrainingCard` | `components/trainings/TrainingCard.tsx` | Card para listagem (data, local, vagas, preço, comprar) |
| `TrainingsClient` | `components/trainings/TrainingsClient.tsx` | Listagem com filtros, pesquisa e tabs |
| `TrainingDetail` | `components/trainings/TrainingDetail.tsx` | Página de detalhe completa |

### 4. Navegação Atualizada

- **HeaderNav**: Link "Presenciais" adicionado no menu desktop e "Formações Presenciais" no mobile
- **Footer**: Link "Formações Presenciais" adicionado no menu do rodapé

---

## Como Configurar no Drupal

### Passo 1: Importar Configuração

```bash
# Na raiz do projeto Drupal
drush cr
drush config:import --partial --source=config/sync
```

Ou aplicar a receita:
```bash
cd web
php core/scripts/drupal recipe ../recipes/commerce_formacao_presencial
```

### Passo 2: Configurar Permissões JSON:API

No painel admin do Drupal, ir a **Configuração > Web Services > JSON:API** e verificar que o novo tipo de produto `formacao_presencial` está acessível via a API.

### Passo 3: Criar a Primeira Formação

1. Ir a **Commerce > Products > Add Product > Formação Presencial**
2. Preencher:
   - **Título**: Nome da formação (ex: "Workshop de Gestão Financeira")
   - **Descrição**: Texto detalhado sobre o conteúdo
   - **Data e Hora de Início**: Data e hora do evento
   - **Data e Hora de Fim**: (opcional) Hora de fim do evento
   - **Local**: Nome do local (ex: "Hotel Altis, Lisboa")
   - **Morada Completa**: (opcional) Endereço completo
   - **Número Máximo de Vagas**: Ex: 30
   - **Formador**: (opcional) Nome do formador
   - **Imagens**: Imagem(ns) da formação
3. Na variação, definir o **preço do bilhete** (ex: 149,00 €)
4. Definir um **path alias** como `/formacoes-presenciais/workshop-gestao-financeira`
5. Publicar

### Passo 4: Configurar Email de Confirmação (Opcional)

Para envio automático de email após a compra:

1. Ir a **Commerce > Configuration > Orders > Order types > Default**
2. Em **Emails**, adicionar ou editar a regra de email para o evento "Encomenda Paga"
3. Configurar o template com as informações do bilhete

Alternativamente, instalar o módulo **Commerce Email** (`commerce_email`) para templates mais avançados.

---

## Funcionalidades

### ✅ Implementadas
- [x] Listagem de formações presenciais com cards visuais
- [x] Data, horário e localização visíveis
- [x] Número máximo de vagas definível
- [x] Indicador de vagas disponíveis (barra de progresso)
- [x] Alerta "Últimas Vagas!" quando restam ≤20% das vagas
- [x] Estado "Esgotado" quando sem vagas
- [x] Estado "Encerrada" para formações passadas
- [x] Compra de bilhete via carrinho existente
- [x] Seletor de quantidade de bilhetes na página de detalhe
- [x] Filtros por estado (Próximas/Anteriores/Todas)
- [x] Pesquisa por nome, local ou formador
- [x] Filtro por localização
- [x] Botão de partilha (Web Share API / copiar link)
- [x] Página de detalhe com toda a informação do evento
- [x] Design responsivo (mobile + desktop)
- [x] Integração com sistema de carrinho/checkout existente
- [x] Navegação atualizada (header + footer)

### 🔧 A Configurar no Drupal Admin
- [ ] Importar configuração / aplicar receita
- [ ] Criar primeiras formações presenciais de teste
- [ ] Configurar template de email de confirmação
- [ ] (Opcional) Configurar regra para atualizar `field_current_participants` automaticamente após compra

### 💡 Melhorias Futuras
- Envio de email de confirmação personalizado com dados do bilhete (QR code, etc.)
- Módulo Drupal customizado para decrementar vagas automaticamente quando uma encomenda é paga
- Integração com Google Calendar (botão "Adicionar ao calendário")
- Página de gestão de participantes para admin
- Exportação de lista de participantes (CSV/PDF)
- Sistema de lista de espera quando esgotado
- Funcionalidade de "preço sob orçamento" (contactar para preço)

---

## Ficheiros Alterados/Criados

### Novos Ficheiros - Drupal Config
- `config/sync/commerce_product.commerce_product_type.formacao_presencial.yml`
- `config/sync/commerce_product.commerce_product_variation_type.formacao_presencial.yml`
- `config/sync/field.storage.commerce_product.field_training_date.yml`
- `config/sync/field.storage.commerce_product.field_training_end_date.yml`
- `config/sync/field.storage.commerce_product.field_location.yml`
- `config/sync/field.storage.commerce_product.field_location_address.yml`
- `config/sync/field.storage.commerce_product.field_max_participants.yml`
- `config/sync/field.storage.commerce_product.field_current_participants.yml`
- `config/sync/field.storage.commerce_product.field_instructor.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.body.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.images.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_training_date.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_training_end_date.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_location.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_location_address.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_max_participants.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_current_participants.yml`
- `config/sync/field.field.commerce_product.formacao_presencial.field_instructor.yml`

### Novos Ficheiros - Drupal Recipe
- `recipes/commerce_formacao_presencial/recipe.yml`

### Novos Ficheiros - Next.js
- `ricardo/app/formacoes-presenciais/page.tsx` — Página de listagem
- `ricardo/app/formacoes-presenciais/[slug]/page.tsx` — Página de detalhe
- `ricardo/components/trainings/TrainingCard.tsx` — Componente card
- `ricardo/components/trainings/TrainingsClient.tsx` — Componente listagem com filtros
- `ricardo/components/trainings/TrainingDetail.tsx` — Componente detalhe

### Ficheiros Alterados
- `ricardo/components/navigation/HeaderNav.tsx` — Adicionado link "Presenciais" / "Formações Presenciais"
- `ricardo/app/layout.tsx` — Adicionado link no footer
