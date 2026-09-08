# Documentação técnica

## Componente

- Tipo de plugin: `local`
- Componente: `local_activitysettingstemplates`
- Diretório: `local/activitysettingstemplates`
- Versão pública: `1.0.0`
- Moodle mínimo: `4.5` (`2024100700`)
- Faixa declarada: Moodle `4.5` a `5.2`

## Arquitetura

O Activity Settings Templates estende os formulários nativos de configuração dos módulos de curso. Ele não substitui o formulário da atividade.

Os principais callbacks ficam em `lib.php`:

- `local_activitysettingstemplates_extend_navigation_course()` adiciona acesso ao gerenciamento dos modelos pessoais.
- `local_activitysettingstemplates_coursemodule_standard_elements()` insere seleção de modelo, ações, prévia de compatibilidade e aplicação nos formulários de atividades/recursos.

Aplicar um modelo altera somente valores editáveis no formulário atual do navegador. A validação e o salvamento continuam sob responsabilidade do Moodle quando o professor envia o formulário nativo.

## Modelo de dados

Os modelos pessoais são armazenados em `local_activitysettingstemplates` com:

- `userid`: proprietário;
- `name`: nome;
- `description`: descrição opcional;
- `moduletype`: tipo de atividade/recurso;
- `configjson`: configurações selecionadas serializadas em JSON;
- `timecreated` e `timemodified`: registros de tempo.

O índice `userid, moduletype` otimiza a filtragem por usuário e tipo de atividade.

## Permissão

Capability: `local/activitysettingstemplates:manage`

- contexto de curso;
- capacidade de escrita;
- risco de configuração;
- permitida por padrão para professor editor e gerente;
- baseada em `moodle/course:manageactivities`.

As páginas de gerenciamento também exigem a permissão nativa para gerenciar atividades.

## Registro de configurações

`classes/local/field_registry.php` centraliza as regras do que pode ser transformado em modelo.

Entre suas responsabilidades estão:

- detectar módulos instalados;
- fornecer listas curadas para módulos principais do Moodle;
- incluir configurações comuns seguras;
- normalizar modelos de versões de desenvolvimento;
- apresentar valores com rótulos compreensíveis;
- definir controles de edição;
- tratar durações;
- tratar opções serializadas de exibição de determinados recursos;
- realizar descoberta conservadora em módulos de terceiros.

A lista curada é intencional: configurações internas, calculadas, relacionais, de conteúdo ou credenciais não devem aparecer simplesmente porque existem no banco de dados.

## Ciclo de vida dos modelos

- `create.php` + `create_template_form.php`: criação a partir de uma atividade salva.
- `index.php`: gerenciamento dos modelos pessoais.
- `edit.php` + `edit_template_form.php`: alteração de nome, descrição, itens e valores.
- `delete.php`: exclusão confirmada com `sesskey()`.

O tipo de atividade do modelo não é convertido para outro tipo durante a edição.

## Camada JavaScript

`amd/src/applytemplate.js` controla:

- seleção do modelo;
- prévia de compatibilidade;
- aplicação somente em controles editáveis;
- eventos `input` e `change` para permitir reação das dependências do Moodle;
- segunda tentativa dos campos dependentes;
- mensagem de status contextual e acessível.

O JavaScript não envia automaticamente o formulário da atividade.

## Privacidade

`classes/privacy/provider.php` implementa a API de Privacidade do Moodle para metadados, descoberta de contextos, exportação e exclusão dos modelos pessoais.

Nenhum dado é enviado para serviços externos.

## Segurança

A implementação utiliza autenticação, contexto de curso, verificações de capability, limpeza de parâmetros, Moodle Forms, confirmação por `sesskey()` em exclusão, verificação de propriedade do modelo e listas seguras de configurações.

## Acessibilidade e responsividade

- label associado ao seletor;
- botões nativos;
- operação por teclado;
- layout 50/50 em telas grandes e empilhamento em telas menores;
- cor + ícone + texto nos estados de compatibilidade;
- mensagens com `role="status"` e `aria-live="polite"`;
- nenhum salvamento automático após aplicação.

Consulte [TESTING.md](TESTING.md) para o roteiro de validação da versão.
