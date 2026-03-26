# NTO Logística - Instruções de Deploy no cPanel v5

## Novidades da Versão 5

### Correções
- **Bug P0 Corrigido**: Criação de chamados por unidades de emergência
- **Bug P1 Corrigido**: Página de relatórios não carregava (faltava `by_priority` no backend)
- **Bug P2**: Melhoria no tratamento de erros de criação de chamados com mensagens detalhadas

### Novas Funcionalidades
- **Nova Identidade Visual**: 
  - Nome: NTO Logística - Laboratórios Medclin
  - Cores: #0faeaa (primária), #007a82 (secundária), #ffffff (branco)
- **Relatórios Completos** com 5 abas:
  - Visão Geral
  - Chamados (por unidade e motorista)
  - Envios/Retiradas (por motorista)
  - Despesas (por setor, categoria e setor+categoria)
  - Tempo Real (status de motoristas e pendências)
- **Removido**: Badge "Made with Emergent"
- **Removido**: Seções de motoristas e setores do menu lateral admin

---

## Estrutura de Arquivos

```
public_html/
├── index.html          (do frontend/)
├── asset-manifest.json (do frontend/)
├── static/             (do frontend/)
│   ├── css/
│   └── js/
├── .htaccess           (do frontend/)
└── api/                (pasta api/ completa)
    ├── .htaccess
    ├── config.php      (EDITAR CREDENCIAIS!)
    ├── index.php
    ├── jwt.php
    ├── auth.php
    ├── calls.php
    ├── shipments.php
    ├── locations.php
    ├── users.php
    ├── reports.php
    ├── expenses.php
    ├── seed.php
    └── gerar_senha.php
```

---

## Passo a Passo de Instalação

### 1. Banco de Dados
1. Acesse phpMyAdmin no cPanel
2. Crie um novo banco de dados (ex: `nto_logistics`)
3. Importe o arquivo `database.sql`
4. Anote: nome do banco, usuário e senha

### 2. Configuração do Backend
Edite o arquivo `api/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'SEU_BANCO_AQUI');      // nome do banco criado
define('DB_USER', 'SEU_USUARIO_AQUI');    // usuário do banco
define('DB_PASS', 'SUA_SENHA_AQUI');      // senha do banco
```

### 3. Upload dos Arquivos
1. Extraia o ZIP `nto_logistics_cpanel_v5.zip`
2. Upload da pasta `deploy_cpanel/frontend/*` para `public_html/`
3. Upload da pasta `deploy_cpanel/api/` para `public_html/api/`

### 4. Configurar .htaccess do Frontend
Certifique-se que o arquivo `.htaccess` na raiz (`public_html/`) contém:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^api/(.*)$ /api/$1 [L,PT]
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-l
    RewriteRule . /index.html [L]
</IfModule>
```

### 5. Configurar .htaccess da API
Certifique-se que o arquivo `api/.htaccess` contém:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /api/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

<IfModule mod_headers.c>
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

### 6. Criar Primeiro Usuário Admin

**Opção A - Via Interface:**
1. Acesse seu site
2. Clique em "Criar Dados de Teste"
3. Use: admin@nto.com / admin123

**Opção B - Via SQL:**
1. Gere um hash de senha usando `gerar_senha.php`:
   - Acesse: https://seusite.com/api/gerar_senha.php?senha=suasenha123
   - Copie o hash retornado

2. Execute no phpMyAdmin:
```sql
INSERT INTO users (id, name, email, password_hash, role, is_active, created_at) 
VALUES (
    UUID(), 
    'Administrador', 
    'admin@seudominio.com',
    'COLE_O_HASH_AQUI',
    'admin',
    1,
    NOW()
);
```

---

## Perfis de Usuário

| Perfil | Acesso |
|--------|--------|
| `admin` | Dashboard, Relatórios, Usuários, Locais, TV Panel |
| `driver` | Painel do Motorista (aceitar e completar tarefas) |
| `emergency` | Portal de Chamados (criar chamados) |
| `sector` | Portal de Setor (envios/retiradas e despesas) |
| `approval` | Painel de Aprovação (aprovar despesas) |
| `financial` | Painel Financeiro (liberar despesas aprovadas) |

---

## URLs do Sistema

- `/login` - Página de login
- `/dashboard` - Redireciona automaticamente baseado no perfil
- `/tv` - Painel TV (público, sem login)
- `/relatorios` - Relatórios completos (admin)
- `/usuarios` - Gerenciamento de usuários (admin)
- `/locais` - Gerenciamento de locais (admin)
- `/chamados` - Portal de chamados (emergency)
- `/setor` - Portal do setor (sector)
- `/despesas` - Solicitação de despesas (sector)
- `/aprovacao` - Aprovação de despesas (approval)
- `/financeiro` - Liberação de despesas (financial)
- `/motorista` - Painel do motorista (driver)

---

## Troubleshooting

### Erro 500 Internal Server Error
- Verifique se `config.php` tem as credenciais corretas
- Verifique se as tabelas foram criadas corretamente

### Login "Credenciais Inválidas"
- Use o `gerar_senha.php` para criar um novo hash
- Verifique se o usuário está com `is_active = 1`

### Página não encontrada / 404
- Verifique se os arquivos `.htaccess` estão configurados
- Habilite mod_rewrite no cPanel

### API retorna erro de CORS
- Verifique o `.htaccess` da pasta `api/`

---

## Suporte

Este sistema foi desenvolvido para NTO Logística - Laboratórios Medclin.
