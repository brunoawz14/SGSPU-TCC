# SGSPU — Sistema de Gestão de Serviços Públicos Urbanos

Aplicação simples em PHP e MySQL para um TCC. As telas usam [W3.CSS 5](https://www.w3schools.com/w3css/5/w3.css) pelo CDN oficial.

## Requisitos

- PHP 8 ou superior, com as extensões PDO MySQL e Fileinfo.
- MySQL ou MariaDB.
- Servidor web com PHP, como Apache no XAMPP.
- Acesso à internet no navegador para carregar W3.CSS pelo CDN.

## Preparação

1. Importe `database/banco_gestao_servicos_publicos.sql` no phpMyAdmin **em um banco novo**. O script cria o banco `gestao_servicos_publicos` e suas tabelas.
2. A conexão está em `config/conexao.php`. Por padrão usa host `127.0.0.1`, banco `gestao_servicos_publicos`, usuário `root` e senha vazia. Se necessário, configure as variáveis de ambiente `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASSWORD` antes de iniciar o servidor PHP.
3. No XAMPP, copie a versão atualizada do projeto para `C:\xampp\htdocs\SGSPU-TCC` e acesse `http://localhost/SGSPU-TCC/auth/login.php`. O cidadão pode usar **Criar conta de cidadão**.
4. Para a configuração inicial, use o link discreto **Configuração inicial da administração** na mesma página. Ele deixa de aparecer após criar o primeiro administrador.
5. Entre como administrador e cadastre os trabalhadores em **Trabalhadores**. Atribua cada solicitação a um deles no painel administrativo.

Administradores e trabalhadores entram pelo mesmo formulário de login. No banco, ambos usam a tabela `administrador`: o campo `nivel_acesso` distingue `GERAL` de `OPERADOR`. A coluna `solicitacao.administrador_id` identifica o trabalhador atribuído.

Se o banco já tiver sido criado com a versão anterior do SQL, execute **uma vez** no banco existente:

```sql
ALTER TABLE solicitacao ADD COLUMN categoria VARCHAR(50) NOT NULL DEFAULT 'Outro' AFTER descricao;
```

Não reimporte o script completo sobre tabelas já existentes.

## Fluxo disponível

- Cidadão: criar a própria conta, abrir uma solicitação, anexar uma imagem opcional e acompanhar o atendimento.
- Administrador: cadastrar trabalhadores, consultar cidadãos e solicitações, atribuir um trabalhador e definir a prioridade.
- Trabalhador: ver as solicitações atribuídas e atualizar o status do atendimento.
- Sistema: guardar o histórico e um aviso quando o status muda.

Os arquivos enviados ficam em `uploads/`. A tabela `imagem` guarda somente o caminho. O upload aceita JPG, PNG e WebP de até 5 MB. A pasta precisa permitir escrita pelo processo do servidor web. Com Apache, o `.htaccess` impede acesso direto aos arquivos; a visualização ocorre por `solicitacoes/imagem.php`, após conferir a sessão e a solicitação.

## Pastas

- `auth/`: login, cadastro de cidadão, configuração inicial do administrador e saída.
- `admin/`: cadastro de trabalhadores e consulta de cidadãos.
- `solicitacoes/`: abertura, detalhes e exibição de imagem.
- `config/`: conexão com o banco e funções comuns das páginas.
- `database/`: script de criação do banco.
- `classes/`: classes PHP do modelo inicial do TCC.
- `uploads/`: arquivos de imagem enviados pelos cidadãos.
