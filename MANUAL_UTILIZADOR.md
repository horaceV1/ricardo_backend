# Manual de Utilizador — Clínica do Empresário

## Plataforma de Administração (Backend + Frontend)

**Versão:** 1.0  
**Data:** Março 2026  
**URL Backend (Drupal):** `https://darkcyan-stork-408379.hostingersite.com`  
**URL Frontend (Website):** `https://www.clinicadoempresario.pt`

---

# PARTE 1 — BACKEND (Drupal) — Para Administradores

## 1. Acesso ao Painel de Administração

### 1.1 Login
1. Aceder a: `https://darkcyan-stork-408379.hostingersite.com/user/login`
2. Introduzir o **nome de utilizador** e a **password**.
3. Após login, será redirecionado para a página inicial do Drupal.

### 1.2 Painel de Administração (Dashboard)
- Aceder a: **`/admin/dashboard`**
- O dashboard apresenta painéis de acesso rápido para todas as funcionalidades:

| Painel | Descrição | Cor |
|--------|-----------|-----|
| 📝 Criar / Editar Artigos do Blog | Gerir publicações do blog | Azul |
| 📋 Criar / Editar Tipos de Formulários | Configurar formulários dinâmicos | Roxo |
| 📄 Criar / Editar Formulários de Incentivos | Gerir páginas de incentivos | Verde |
| 🎓 Criar / Editar Cursos | Gerir cursos da plataforma de formação | Âmbar |
| 👥 Gerir Perfis de Utilizadores | Administrar contas e permissões | Ciano |
| 📌 Gerir Atribuições | Atribuir submissões a funcionários/técnicos | Rosa |
| 🛒 Criar / Editar Produtos | Gerir catálogo de produtos e preços | Laranja |
| 🏠 Editar Página Inicial | Gerir conteúdo da homepage | Índigo |
| 📧 Editar Página de Contacto | Gerir página de contacto | Teal |

Cada painel tem um botão **"Ver Todos"** e um botão **"+ Novo"** para criar novos itens.

---

## 2. Artigos do Blog

Os artigos do blog utilizam o tipo de conteúdo **"curso"** (nome interno no Drupal).

### 2.1 Criar um Artigo de Blog
1. No Dashboard, clicar em **"Novo Artigo"** no painel "Criar / Editar Artigos do Blog".
   - Ou ir a: `/node/add/curso`
2. Preencher os campos:
   - **Título** — Título do artigo (obrigatório).
   - **Body** — Conteúdo do artigo. Usar o editor CKEditor5 para formatar texto, adicionar imagens, links, listas, etc.
   - **Imagem** (`field_image`) — Imagem de destaque do artigo. Fazer upload de uma imagem.
3. Clicar em **"Guardar"**.

### 2.2 Editar um Artigo Existente
1. No Dashboard, clicar em **"Ver Todos"** no painel do Blog.
   - Ou ir a: `/admin/content?type=curso`
2. Encontrar o artigo na lista.
3. Clicar em **"Editar"** nas operações do artigo.
4. Fazer as alterações necessárias.
5. Clicar em **"Guardar"**.

### 2.3 Usar o Editor CKEditor5
O editor suporta:
- **Negrito**, *Itálico*, ~~Riscado~~
- Cabeçalhos (H2, H3, H4)
- Listas ordenadas e não ordenadas
- Links e imagens incorporadas
- **Tipos de letra personalizados** (incluindo Nexa-Book)
- Tamanhos de letra personalizados
- Tabelas

> **Dica:** Para manter o estilo visual consistente com o resto do site, use a fonte "Nexa-Book, sans-serif" disponível no seletor de fontes.

---

## 3. Formulários de Incentivos

Os formulários de incentivos utilizam o tipo de conteúdo **"article"**.

### 3.1 Criar uma Página de Incentivo
1. No Dashboard, clicar em **"Novo Formulário"** no painel "Criar / Editar Formulários de Incentivos".
   - Ou ir a: `/node/add/article`
2. Preencher os campos:
   - **Título** — Nome do incentivo (ex: "PRR Investir no Digital").
   - **Body** — Descrição completa do incentivo com informações de elegibilidade, prazos, etc.
   - **Imagem** (`field_image`) — Imagem representativa do incentivo.
   - **Formulário Dinâmico** (`field_dynamic_form`) — Associar um ou mais formulários dinâmicos a esta página (ver secção 4). Este é o formulário de candidatura que o utilizador poderá preencher.
3. Clicar em **"Guardar"**.

### 3.2 Associar Formulários Dinâmicos a Incentivos
- No campo **"Formulário Dinâmico"** (`field_dynamic_form`), selecionar o(s) formulário(s) previamente criados.
- Quando um visitante acede à página do incentivo no frontend, o formulário de candidatura será exibido automaticamente no final da página.

### 3.3 Badges de Disponibilidade
As páginas de incentivos no frontend mostram automaticamente um badge de disponibilidade baseado nas datas de abertura/fecho do incentivo, se configuradas.

---

## 4. Formulários Dinâmicos

Os formulários dinâmicos permitem criar formulários personalizados para candidaturas, sem necessidade de programação.

### 4.1 Criar um Formulário Dinâmico
1. No Dashboard, clicar em **"Novo Formulário"** no painel "Criar / Editar Tipos de Formulários".
   - Ou ir a: `/admin/content/formularios-dinamicos/add`
2. Preencher:
   - **Nome do formulário** — Nome visível do formulário (ex: "Candidatura PRR Digital").
   - **ID** — Identificador único (gerado automaticamente a partir do nome, em minúsculas com hífens).
3. Clicar em **"Guardar"** para criar.

### 4.2 Adicionar Campos ao Formulário
Após criar o formulário, clicar em **"Editar"** para adicionar campos:

1. Cada campo tem as seguintes opções:
   - **Label** — Nome do campo (ex: "Certidão Comercial", "NIF da Empresa", "Plano de Investimento").
   - **Tipo** — Tipo do campo:
     - `Texto` — Campo de texto livre
     - `Imagem` — Upload de imagem (PNG, JPG, etc.)
     - `Documento` — Upload de documento (PDF, etc.)
   - **Obrigatório** — Marcar se o campo é de preenchimento obrigatório.
   - **Onde obter este documento (link)** — URL para ajudar o utilizador a obter o documento necessário (aparece apenas para campos do tipo "Documento").

2. Para adicionar mais campos, clicar no botão **"Adicionar campo"**.
3. Clicar em **"Guardar"** quando terminar.

### 4.3 Opções Avançadas do Formulário
- **Exigir autenticação** (`require_auth`) — Se ativado, o utilizador precisa estar registado e autenticado para submeter o formulário.
- **Integração Mailchimp** — Possibilidade de ativar a subscrição automática numa lista de email do Mailchimp.

### 4.4 Ver Submissões de um Formulário
1. Na lista de formulários (`/admin/content/formularios-dinamicos`), clicar em **"Ver submissões"** ao lado do formulário desejado.
2. Será apresentada uma lista com todas as submissões recebidas, incluindo:
   - ID da submissão
   - Email do utilizador
   - Data de submissão
   - Estado de aprovação

### 4.5 Gerir uma Submissão Individual (Backend)
Na página de detalhe de uma submissão, pode:
- Ver todos os campos submetidos e os ficheiros/documentos enviados.
- **Aprovar ou Recusar** campos individuais usando o formulário de aprovação.
- Adicionar **notas** de aprovação/recusa para cada campo.
- **Atribuir um técnico** responsável pela submissão.

> **Nota:** A gestão de submissões é mais prática e visual no **Painel Frontend** (ver Parte 2).

---

## 5. Cursos (Formação)

O sistema de cursos usa o tipo de conteúdo **"cursos"** com suporte a hierarquia pai-filho.

### 5.1 Criar um Curso (Pai)
1. No Dashboard, clicar em **"Novo Curso"** no painel "Criar / Editar Cursos".
   - Ou ir a: `/node/add/cursos`
2. Preencher:
   - **Título** — Nome do curso (ex: "Literacia Financeira para Iniciantes").
   - **Body Curso** (`body_curso`) — Conteúdo/descrição do curso. Este campo suporta rich text.
3. **Não** selecionar nenhum campo em `cursos_ref` (referência de hierarquia) — este curso será o curso pai (raiz).
4. Clicar em **"Guardar"**.

### 5.2 Criar Módulos/Capítulos (Filhos)
Os capítulos/módulos de um curso são também conteúdos do tipo "cursos", mas com uma referência ao curso pai.

1. Ir a: `/node/add/cursos`
2. Preencher:
   - **Título** — Nome do módulo/capítulo (ex: "Módulo 1: Orçamento Pessoal").
   - **Body Curso** — Conteúdo completo do capítulo. Este é o material que o aluno irá ler/estudar.
   - **Referência de Curso** (`cursos_ref`) — **Selecionar o curso pai** ao qual este módulo pertence. Este campo estabelece a hierarquia.
3. Clicar em **"Guardar"**.
4. Repetir para todos os módulos/capítulos.

### 5.3 Ordenar Módulos
A ordem dos módulos é definida pelo **peso (weight)** na referência de hierarquia (`cursos_ref`). Módulos com peso menor aparecem primeiro.
- Para reordenar, editar cada módulo filho e ajustar o peso na referência `cursos_ref`.

### 5.4 Como Funciona no Frontend
- O aluno que comprou o produto associado ao curso pode aceder à **Área do Aluno** (`/area-aluno`).
- Os módulos aparecem como um índice lateral, e o aluno navega entre capítulos.
- O progresso do aluno é guardado automaticamente.
- Após completar todos os módulos, o certificado fica disponível.

---

## 6. Produtos (Commerce)

Os produtos usam o sistema **Drupal Commerce** e representam as formações à venda.

### 6.1 Criar um Produto
1. No Dashboard, clicar em **"Novo Produto"** no painel "Criar / Editar Produtos".
   - Ou ir a: `/admin/commerce/products/add`
2. Selecionar o tipo de produto:
   - **Media** — Para produtos digitais (download de ficheiros, acesso a cursos).
   - **Physical** — Para produtos físicos.
3. Preencher:
   - **Título** — Nome do produto (ex: "Workshop: Literacia Financeira para Iniciantes").
   - **Body** — Descrição detalhada do produto. Usar CKEditor5 para formatar.
   - **Imagens** — Upload de imagens do produto.
   - **Categoria** (`field_categoria`) — Selecionar a categoria da formação (ex: "Gestão Financeira").
   - **Nível** (`field_nivel`) — Selecionar o nível (ex: "Iniciante", "Intermédio", "Avançado").

### 6.2 Criar Variações de Produto
Cada produto precisa de pelo menos uma **variação** (que define o preço):
1. Na secção "Variações" do produto, clicar em **"Adicionar variação"**.
2. Preencher:
   - **Título** — Nome da variação (ex: "Workshop").
   - **SKU** — Código único do produto (ex: "001").
   - **Preço** — Valor em EUR (ex: "300.00").
3. Para produtos digitais (Media), adicionar os ficheiros de download na secção correspondente.

### 6.3 ⭐ Associar um Produto a um Curso
**Este é um passo crucial** — é o que permite ao aluno aceder ao conteúdo do curso após a compra.

1. Editar o produto.
2. No campo **"Curso"** (`field_curso`), selecionar o curso (tipo de conteúdo "cursos") que corresponde a este produto.
3. Clicar em **"Guardar"**.

**Resultado:** Quando o utilizador compra este produto, o sistema reconhece automaticamente que o utilizador tem acesso ao curso associado, e o curso aparece na **Área do Aluno**.

### 6.4 Categorias e Níveis (Taxonomias)
As categorias e níveis são geridos como vocabulários de taxonomia:
- **Categorias de Formações** (`categorias_formacoes`) — Ex: "Gestão Financeira", "Marketing", "RH".
- **Nível de Formações** (`nivel_formacoes`) — Ex: "Iniciante", "Intermédio", "Avançado".

Para adicionar novos termos:
1. Ir a: `/admin/structure/taxonomy`
2. Selecionar o vocabulário desejado.
3. Clicar em **"Adicionar termo"**.
4. Introduzir o nome e guardar.

---

## 7. Página Inicial (Homepage)

### 7.1 Editar o Conteúdo da Página Inicial
1. No Dashboard, clicar em **"Ver Todos"** no painel "Editar Página Inicial".
   - Ou ir a: `/admin/content?type=homepage`
2. Editar o conteúdo da homepage.
3. Campos disponíveis:
   - **Título do Hero** — Texto principal do banner (suporta rich text e fontes).
   - **Subtítulo do Hero** — Texto descritivo abaixo do título.
   - **Badge do Hero** — Texto curto no badge acima do título (ex: "A Clínica do Empresário").
   - **Imagem do Hero** — Imagem principal do banner.
   - **Botões CTA** — Links dos botões de ação do hero.
   - **Estatísticas** — Números exibidos na homepage (experiência, candidaturas, horas de formação).
   - **Secção de Funcionalidades** — Título e descrição dos 4 blocos de funcionalidades.
   - **Secção de Testemunhos** — Testemunhos de clientes.
   - **Secção CTA Final** — Bloco de chamada à ação no final da página.

### 7.2 Dicas para a Homepage
- Use rich text nos campos de título para personalizar fontes e estilos.
- As formações em destaque são automaticamente puxadas dos produtos mais recentes.
- As secções animam automaticamente quando o visitante faz scroll na página.

---

## 8. Página de Contacto

### 8.1 Editar a Página de Contacto
1. No Dashboard, clicar no painel "Editar Página de Contacto".
   - Ou ir a: `/admin/content?type=contact_page`
2. Editar os campos:
   - Informações de contacto (email, telefone, morada).
   - FAQ (perguntas frequentes).
   - Mapa.

---

## 9. Gerir Utilizadores

### 9.1 Criar um Utilizador
1. No Dashboard, clicar em **"Novo Utilizador"** no painel "Gerir Perfis de Utilizadores".
   - Ou ir a: `/admin/people/create`
2. Preencher nome de utilizador, email e password.
3. **Atribuir papéis (roles):**
   - **Administrator** — Acesso total ao sistema.
   - **Tecnico** — Pode gerir submissões e ser atribuído como responsável por processos.
   - **Authenticated** — Utilizador normal registado (cliente).

### 9.2 Papéis e Permissões

| Papel | Acesso Backend | Acesso Frontend | Gerir Submissões | Atribuir Técnicos |
|-------|---------------|-----------------|------------------|--------------------|
| Administrator | ✅ Total | ✅ Total | ✅ | ✅ |
| Tecnico | ⚠️ Limitado | ✅ Painel de Gestão | ✅ (apenas atribuídas) | ❌ |
| Authenticated | ❌ | ✅ (conta pessoal) | ❌ (só ver as suas) | ❌ |

---

## 10. Gerir Atribuições (Backend)

### 10.1 Ver Todas as Atribuições
1. No Dashboard, clicar no painel "Gerir Atribuições".
   - Ou ir a: rota `submission_assignment.admin_overview`
2. Será apresentada uma tabela com todas as submissões, mostrando:
   - ID da submissão
   - Formulário
   - Email do submissor
   - Técnico atribuído
   - Quem atribuiu
   - Data de atribuição
   - Número de mensagens
   - Data de criação

### 10.2 Atribuir um Técnico (Backend)
1. Na lista de atribuições, clicar em **"Atribuir"** na submissão desejada.
2. No formulário de atribuição:
   - Selecionar o funcionário/técnico na lista dropdown.
   - Opcionalmente, marcar **"Notificar funcionário por email"** para enviar um email automático.
3. Clicar em **"Atribuir"**.

> **Nota:** A atribuição também pode ser feita (e é mais prática) no **Painel Frontend** (ver Parte 2).

---

## 11. Configurações Importantes

### 11.1 SMTP (Email)
As configurações de email são geridas via Drupal State:
- **Servidor:** `smtp.hostinger.com`
- **Porta:** `465`
- **Remetente:** `noreply@clinicadoempresario.pt`

### 11.2 Pagamentos (Eupago)
As configurações de pagamento são geridas no módulo `eupago_payments`:
- Métodos aceites: **Multibanco**, **MB WAY**, **Cartão de Crédito** (Visa/Mastercard)
- URL de retorno: `https://www.clinicadoempresario.pt`

### 11.3 Limpar Cache
Após fazer alterações significativas no backend, é recomendado limpar a cache:
- Ir a: `/admin/config/development/performance`
- Clicar em **"Limpar todas as caches"**

---

---

# PARTE 2 — FRONTEND (Website) — Painel de Gestão de Submissões

## Para Administradores e Técnicos

O painel de gestão de submissões está disponível no **frontend** (website) para utilizadores com papel de **Administrator** ou **Tecnico**.

---

## 1. Acesso ao Painel de Gestão

### 1.1 Login no Frontend
1. Aceder a: `https://www.clinicadoempresario.pt/entrar`
2. Introduzir email e password.
3. Após login, será redirecionado para a página da conta.

### 1.2 Aceder ao Painel de Gestão
1. Após login, ir à página **Conta** (`/conta`).
2. Utilizadores com papel de **Administrator** ou **Tecnico** verão o link: **"Gestão de Submissões"**.
3. Clicar para aceder a: `/conta/gestao-submissoes`

---

## 2. Lista de Submissões

### 2.1 Visão Geral
A página principal do painel mostra:
- **Estatísticas** no topo:
  - Total de submissões
  - Pendentes (amarelo)
  - Aprovadas (verde)
  - Recusadas (vermelho)
- **Lista de todas as submissões** recebidas de todos os formulários dinâmicos.

### 2.2 Filtrar Submissões
Na barra de filtros no topo da lista:

1. **Pesquisa por email** — Digitar no campo de pesquisa para encontrar submissões de um utilizador específico.
2. **Filtro por estado** — Clicar no dropdown "Estado" para filtrar por:
   - Todos
   - Pendente
   - Aprovado
   - Recusado
   - Parcial
3. **Filtro por formulário** — Clicar no dropdown "Formulário" para filtrar por tipo de formulário.
4. **Botão Atualizar** — Clicar no ícone 🔄 para recarregar a lista.

### 2.3 Informações na Lista
Cada submissão na lista mostra:
- **Nome do formulário** e ID da submissão
- **Email** do utilizador que submeteu
- **Data** de submissão
- **Estado geral** (Pendente / Aprovado / Recusado / Parcial)
- **Progresso dos campos** — Barra visual mostrando quantos campos estão aprovados (verde), recusados (vermelho) e pendentes
- **Técnico atribuído** — Nome do técnico responsável (se atribuído)
- **Mensagens** — Ícone de chat se existirem mensagens

---

## 3. Detalhe de uma Submissão

### 3.1 Aceder ao Detalhe
Clicar em qualquer submissão na lista para abrir a página de detalhe: `/conta/gestao-submissoes/[id]`

### 3.2 Layout da Página
A página de detalhe está dividida em **3 secções**:

#### Coluna Principal (Esquerda)
- **Informações da Submissão** — Email, formulário, data, estado geral
- **Campos Submetidos** — Lista de todos os campos com os dados/ficheiros enviados pelo utilizador

#### Coluna Lateral (Direita Superior)
- **Técnico Atribuído** — Mostra o técnico atual e permite reatribuir

#### Coluna Lateral (Direita Inferior)
- **Mensagens / Chat** — Sistema de mensagens com o utilizador

---

## 4. Aprovar ou Recusar Campos

### 4.1 Aprovar um Campo
Esta é a funcionalidade principal do painel. Cada campo submetido pelo utilizador pode ser aprovado ou recusado individualmente.

1. Na secção **"Campos Submetidos"**, localizar o campo desejado.
2. Cada campo mostra:
   - **Nome do campo** (label)
   - **Tipo** (texto, documento ou imagem)
   - **Valor submetido** — texto digitado ou ficheiro enviado
   - **Estado atual** — ícone de cor indicando: ⏳ Pendente, ✅ Aprovado, ❌ Recusado
3. Clicar no botão **"✅ Aprovar"** ao lado do campo.
4. Opcionalmente, adicionar uma **nota de aprovação**.
5. Confirmar.

### 4.2 Recusar um Campo
1. Clicar no botão **"❌ Recusar"** ao lado do campo.
2. **Adicionar uma nota explicativa** — É altamente recomendado explicar o motivo da recusa para que o utilizador saiba o que corrigir.
3. Confirmar.

### 4.3 Reverter para Pendente
Se precisar alterar uma decisão anterior:
1. Clicar no botão de estado do campo.
2. Selecionar **"Pendente"** para reverter o campo ao estado inicial.

### 4.4 Estados de Aprovação

| Estado | Significado | Ícone |
|--------|-------------|-------|
| Pendente | Ainda não analisado | ⏳ Amarelo |
| Aprovado | Campo validado | ✅ Verde |
| Recusado | Campo não aceite (necessita correção) | ❌ Vermelho |
| Parcial | Alguns campos aprovados, outros pendentes/recusados | 🔵 Azul |

O **estado geral** da submissão é calculado automaticamente:
- Se todos os campos estão aprovados → **Aprovado**
- Se algum campo está recusado → **Recusado**
- Se há uma mistura de estados → **Parcial**
- Se nenhum campo foi analisado → **Pendente**

### 4.5 Descarregar Ficheiros
Para campos do tipo "documento" ou "imagem":
- Clicar no **nome do ficheiro** ou no botão de download para descarregar o documento submetido.
- Os ficheiros abrem numa nova aba do navegador.

---

## 5. Atribuir Técnico (Frontend)

### 5.1 Atribuir um Técnico a uma Submissão
1. Na página de detalhe da submissão, localizar a secção **"Técnico Atribuído"** na coluna direita.
2. Se nenhum técnico estiver atribuído, mostra "Nenhum técnico atribuído".
3. Clicar no botão **"Atribuir técnico"** (ou **"Reatribuir"** se já houver um técnico).
4. No dropdown, selecionar o técnico desejado da lista.
5. O técnico será atribuído imediatamente.

### 5.2 Remover Atribuição
1. Clicar em **"Reatribuir"**.
2. No dropdown, selecionar **"Remover atribuição"** (opção a vermelho no topo da lista).

### 5.3 Quem Aparece na Lista de Técnicos?
A lista mostra todos os utilizadores que:
- Têm o papel **Tecnico**, ou
- Têm o papel **Administrator**, ou
- Têm a permissão "manage assigned submissions"

---

## 6. Sistema de Mensagens

### 6.1 Enviar uma Mensagem
O sistema de mensagens permite comunicar diretamente com o utilizador que submeteu o formulário.

1. Na página de detalhe da submissão, localizar a secção **"Mensagens"** na coluna direita (parte inferior).
2. As mensagens aparecem como **chat**, com bolhas de conversa:
   - **Mensagens do admin/técnico** — Aparecem à direita (fundo verde/teal)
   - **Mensagens do utilizador** — Aparecem à esquerda (fundo cinza)
3. Escrever a mensagem no campo de texto na parte inferior.
4. Clicar no botão **"Enviar"** (ícone ✈️) ou pressionar Enter.

### 6.2 Anexar Ficheiros
1. Clicar no ícone de **📎 clipe** (anexo) ao lado do campo de mensagem.
2. Selecionar o ficheiro a anexar.
3. O ficheiro será enviado junto com a mensagem.
4. Ficheiros anexados aparecem como links descarregáveis na conversa.

### 6.3 Atualizações em Tempo Real
- As mensagens são carregadas automaticamente quando se abre o detalhe de uma submissão.
- Para ver novas mensagens, recarregar a página ou navegar para outra submissão e voltar.

### 6.4 Notificações de Mensagens
Na lista de submissões, o ícone 💬 e um contador indicam quantas mensagens existem em cada submissão.

---

## 7. Fluxo de Trabalho Recomendado

### Para o Administrador:
1. **Verificar novas submissões** — Aceder ao painel e verificar submissões com estado "Pendente".
2. **Atribuir técnico** — Atribuir cada submissão a um técnico responsável.
3. **Supervisionar** — Verificar periodicamente o progresso das aprovações.

### Para o Técnico:
1. **Verificar submissões atribuídas** — Aceder ao painel e filtrar por submissões atribuídas.
2. **Analisar documentos** — Abrir cada submissão, descarregar e verificar os documentos enviados.
3. **Aprovar/Recusar campos** — Validar cada campo individualmente.
4. **Comunicar com o cliente** — Usar o sistema de mensagens para pedir esclarecimentos ou documentos adicionais.
5. **Fechar processo** — Quando todos os campos estiverem aprovados, o processo está completo.

---

## 8. Visão do Utilizador (Cliente)

### 8.1 Submeter um Formulário
1. O utilizador acede a uma página de incentivos (ex: `/incentivos/prr-investir-no-digital`).
2. Na parte inferior da página, encontra o formulário de candidatura.
3. Preenche os campos e faz upload dos documentos solicitados.
4. Introduz o email e clica em **"Submeter"**.

### 8.2 Acompanhar Submissões
1. Após login, na página **Conta** (`/conta`), o utilizador vê a secção **"Atividade Recente"**.
2. Cada submissão mostra:
   - Nome do formulário
   - Data de submissão
   - **Estado de cada campo** — O utilizador pode ver se os documentos foram aprovados (✅), recusados (❌) ou estão pendentes (⏳).
   - **Notas** de aprovação/recusa escritas pelo admin/técnico.

### 8.3 Enviar Mensagens (Cliente)
1. Na secção de atividade recente, clicar em **expandir (▼)** numa submissão.
2. Na parte inferior, aparece o **chat** com mensagens trocadas.
3. O utilizador pode escrever mensagens e enviar para o técnico/admin responsável.

### 8.4 Eliminar Submissões
- O utilizador pode eliminar as suas próprias submissões clicando no ícone de **🗑️ lixo** na lista de atividade recente.
- Também existe a opção de **"Limpar tudo"** para apagar todas as submissões.

---

## 9. Resumo de URLs Importantes

### Backend (Drupal)
| Funcionalidade | URL |
|----------------|-----|
| Login Admin | `/user/login` |
| Dashboard | `/admin/dashboard` |
| Artigos do Blog | `/admin/content?type=curso` |
| Formulários de Incentivos | `/admin/content?type=article` |
| Formulários Dinâmicos | `/admin/content/formularios-dinamicos` |
| Adicionar Formulário | `/admin/content/formularios-dinamicos/add` |
| Cursos | `/admin/content?type=cursos` |
| Produtos | `/admin/commerce/products` |
| Utilizadores | `/admin/people` |
| Homepage | `/admin/content?type=homepage` |
| Página de Contacto | `/admin/content?type=contact_page` |
| Taxonomias | `/admin/structure/taxonomy` |
| Limpar Cache | `/admin/config/development/performance` |

### Frontend (Website)
| Funcionalidade | URL |
|----------------|-----|
| Página Inicial | `/` |
| Formações | `/courses` |
| Blog | `/blog` |
| Incentivos | `/incentivos` |
| Contacto | `/contact` |
| Login | `/entrar` |
| Registar | `/cadastrar` |
| Minha Conta | `/conta` |
| Área do Aluno | `/area-aluno` |
| **Gestão de Submissões** | `/conta/gestao-submissoes` |
| Detalhe de Submissão | `/conta/gestao-submissoes/[id]` |
| Carrinho | `/cart` |
| Checkout | `/checkout` |

---

## 10. Resolução de Problemas Comuns

### O produto não aparece no frontend
- Verificar se o produto está **publicado** (status ativo).
- Verificar se tem pelo menos uma **variação** com preço definido.
- Limpar a cache do Drupal.

### O curso não aparece na área do aluno
- Verificar se o **produto está associado ao curso** (campo `field_curso`).
- Verificar se o utilizador **completou a compra** (ordem confirmada).

### As submissões não aparecem no painel
- Verificar se está autenticado com um utilizador que tem papel **Administrator** ou **Tecnico**.
- Verificar se os filtros não estão a esconder as submissões.

### Os módulos do curso aparecem na ordem errada
- Editar cada módulo filho e ajustar o **peso** no campo `cursos_ref`.

### O formulário dinâmico não aparece na página de incentivos
- Verificar se o formulário dinâmico está **associado** à página de incentivos (campo `field_dynamic_form`).
- Verificar se o formulário tem **campos definidos**.

### Os emails não estão a ser enviados
- Verificar as configurações SMTP em `/admin/config`.
- O servidor SMTP é `smtp.hostinger.com`, porta `465`.
- Credenciais: `noreply@clinicadoempresario.pt`.

---

*Manual elaborado em Março de 2026 para a plataforma Clínica do Empresário.*
