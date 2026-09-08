<p align="center">
  <img src="images/activity-settings-templates-logo.png" alt="Logotipo do Activity Settings Templates" width="280">
</p>

# Modelos de Configuração de Atividades

**Activity Settings Templates** (`local_activitysettingstemplates`), em português **Modelos de Configuração de Atividades**, é um plugin local para Moodle centrado no professor. Ele permite salvar configurações selecionadas de atividades como modelos pessoais reutilizáveis e aplicá-las posteriormente a atividades do mesmo tipo.

> Versão pública: **1.0.0**  
> Moodle: **4.5–5.2**  
> Licença: **GNU GPL v3 ou posterior**

## Objetivo

O plugin foi desenvolvido para reduzir trabalho repetitivo, risco de inconsistências e carga cognitiva na configuração de atividades. O professor pode reutilizar decisões de configuração sem duplicar conteúdo e sem substituir o fluxo nativo do Moodle.

Um modelo representa **configurações**, não uma cópia da atividade.

## Principais recursos

- Criação de modelos pessoais a partir de atividades já configuradas.
- Escolha explícita das configurações que farão parte do modelo.
- Aplicação do modelo diretamente no formulário nativo de configuração da atividade.
- Exibição somente dos modelos compatíveis com o tipo de atividade atual.
- Prévia das configurações antes da aplicação.
- Análise prévia de compatibilidade com três estados: **Aplicável**, **Atenção** e **Não aplicável**.
- Uso conjunto de cor, ícone e texto para comunicar status sem depender somente da cor.
- Aplicação segura, sem forçar campos bloqueados ou indisponíveis.
- Nova tentativa automática de campos dependentes depois que as regras do formulário do Moodle reagem.
- Edição de nome, descrição, itens incluídos e valores armazenados.
- Gerenciamento dos modelos pessoais com identificação do tipo de atividade.
- Interface responsiva baseada em Bootstrap e integrada ao formulário do Moodle.
- Feedback contextual imediatamente após a aplicação do modelo.
- Interface em inglês, português do Brasil e espanhol.
- Nenhum serviço externo ou dependência de execução externa.

## Fluxo de uso

1. Configure uma atividade normalmente e salve-a.
2. Reabra as configurações da atividade.
3. Na seção **Modelos de Configuração de Atividades**, escolha **Criar modelo das configurações salvas**.
4. Informe nome e, opcionalmente, descrição.
5. Selecione apenas as configurações que deseja reutilizar.
6. Salve o modelo.
7. Abra outra atividade do mesmo tipo.
8. Selecione o modelo desejado.
9. Confira a prévia de compatibilidade.
10. Clique em **Aplicar modelo**.
11. Revise o formulário nativo do Moodle e salve a atividade normalmente.

A aplicação do modelo **não salva a atividade automaticamente**.

## Tipos de atividade

A seção do plugin está disponível nos formulários dos módulos de atividade/recurso instalados (`mod_*`). Para os módulos principais do Moodle, as configurações são selecionadas por uma lista curada e centrada no professor, evitando campos internos, calculados ou relacionais.

Há tratamento curado para configurações principais de:

- Tarefa;
- Livro;
- Escolha;
- Banco de dados;
- Feedback;
- Arquivo;
- Pasta;
- Fórum;
- Glossário;
- H5P;
- Pacote IMS;
- Lição;
- Página;
- Questionário;
- SCORM;
- URL;
- Wiki;
- Workshop.

Para módulos com configuração específica baseada principalmente em conteúdo, credenciais ou relações complexas, o plugin mantém uma abordagem conservadora e apresenta somente configurações comuns seguras quando aplicável.

Plugins de atividade de terceiros podem ser reconhecidos por um fallback conservador, sem expor automaticamente nomes crus de campos do banco de dados.

## Segurança da aplicação

O plugin não foi projetado para copiar:

- conteúdo e descrições da atividade;
- questões ou conteúdo autoral;
- arquivos e pacotes enviados;
- envios ou dados produzidos pelos estudantes;
- datas e prazos;
- senhas, tokens, segredos ou credenciais;
- identificadores relacionais específicos do curso, como agrupamentos;
- valores internos/calculados;
- campos legados ocultos.

Se uma configuração não existir, estiver bloqueada ou for incompatível com o formulário de destino, o plugin não a força.

## Prévia de compatibilidade

Ao selecionar um modelo, o professor recebe uma análise antes de aplicar qualquer valor:

- **Aplicável** — pode ser aplicada no formulário atual;
- **Atenção** — existe, mas está indisponível no estado atual e pode depender de outra configuração;
- **Não aplicável** — o campo ou o valor salvo não está disponível no formulário atual.

As configurações que exigem atenção aparecem primeiro, reduzindo a necessidade de percorrer toda a lista em busca de exceções.

## Privacidade

Os modelos são pessoais e vinculados à conta que os criou. O plugin armazena no banco do Moodle:

- usuário proprietário;
- nome do modelo;
- descrição;
- tipo de atividade;
- configurações selecionadas e seus valores;
- datas de criação e alteração.

O plugin implementa a API de Privacidade do Moodle para exportação e exclusão desses dados. Nenhuma informação é enviada a serviços externos.

## Instalação

### Por ZIP

1. Acesse **Administração do site → Plugins → Instalar plugins**.
2. Envie o arquivo ZIP da versão.
3. Conclua a validação.
4. Acesse **Administração do site → Notificações** para finalizar a instalação.
5. Limpe os caches do Moodle se necessário.

### Por Git

Clone o repositório em `local/activitysettingstemplates`:

```bash
git clone https://github.com/isaiasmendesoliveira/moodle-local_activitysettingstemplates.git local/activitysettingstemplates
```

Depois acesse **Administração do site → Notificações**.

## Requisitos

- Moodle 4.5 ou superior.
- Faixa declarada nesta versão: Moodle 4.5–5.2.
- Permissão para gerenciar atividades no curso.
- JavaScript habilitado no navegador para prévia e aplicação.

## Documentação adicional

- [Documentação técnica em português](TECHNICAL.pt-BR.md)
- [Guia de testes](TESTING.md)
- [Texto para o Moodle Marketplace](MARKETPLACE.md)

## Licença

GNU GPL v3 ou posterior.
