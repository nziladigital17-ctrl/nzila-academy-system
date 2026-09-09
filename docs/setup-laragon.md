# Setup com Laragon — Nzila Academy

## Requisitos

- [Laragon](https://laragon.org/) instalado (versão Full recomendada)
- PHP 8.1+ (incluído no Laragon)
- MySQL 8.0+ ou MariaDB (incluído no Laragon)
- Composer 2.x (incluído no Laragon)
- Node.js 20+ e npm 10+ (instalar separadamente)

## Passo a Passo

### 1. Iniciar Laragon

Abra o Laragon e clique em **Start All** para iniciar Apache e MySQL.

> [!IMPORTANT]
> O MySQL deve estar a correr antes de executar qualquer comando. Verifique que o ícone MySQL no Laragon está verde.

### 2. Criar a Base de Dados

No Laragon, clique em **Database** → **Open** (HeidiSQL).
Execute:

```sql
CREATE DATABASE nzila_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configurar o Backend

```bash
cd C:\laragon\www\nzila-academy-system\backend

# Copiar ambiente
copy .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Verificar que .env tem estas variáveis correctas:
# DB_DATABASE=nzila_academy
# DB_USERNAME=root
# DB_PASSWORD=
# SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:8000,127.0.0.1:8000
# SESSION_DOMAIN=null
# SESSION_SECURE_COOKIE=false
# CORS_ALLOWED_ORIGINS=http://localhost:5173

# Executar migrations e seeders
php artisan migrate --seed
```

### 4. Verificar a Instalação

```bash
# Listar rotas da API
php artisan route:list --path=api/v1

# Verificar que as rotas começam com /api/v1/... (sem duplicação)
# Exemplos esperados:
#   POST   api/v1/auth/login
#   POST   api/v1/auth/logout
#   GET    api/v1/auth/me
#   GET    api/v1/schools
#   GET    api/v1/students

# Iniciar servidor de desenvolvimento
php artisan serve
```

A API estará em: `http://localhost:8000/api/v1`

### 5. Testar Login

```bash
curl -X POST http://localhost:8000/api/v1/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"superadmin@nzila-academy.ao\",\"password\":\"password\"}"
```

### 6. Configurar o Frontend

```bash
cd C:\laragon\www\nzila-academy-system\frontend

# Instalar dependências
npm install

# Copiar ambiente
copy .env.example .env

# Iniciar dev server
npm run dev
```

O frontend estará em: `http://localhost:5173`

O proxy Vite redireciona `/api/*` automaticamente para `http://localhost:8000`.

### 7. Executar Testes

```bash
cd C:\laragon\www\nzila-academy-system\backend

# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=LoginTest
php artisan test --filter=SchoolIsolationTest
php artisan test --filter=AuditTest
php artisan test --filter=ServiceTest
```

## Utilizadores de Demonstração

| Email | Password | Perfil |
|-------|----------|--------|
| superadmin@nzila-academy.ao | password | Super Admin |
| admin@demo.nzila.ao | password | Admin da Escola |
| director@demo.nzila.ao | password | Diretor |
| coordenador@demo.nzila.ao | password | Coordenador |
| financeiro@demo.nzila.ao | password | Financeiro |
| secretaria@demo.nzila.ao | password | Secretária |
| professor@demo.nzila.ao | password | Professor |
| aluno@demo.nzila.ao | password | Aluno |
| encarregado@demo.nzila.ao | password | Encarregado |

> [!WARNING]
> Estes são dados de demonstração com password `password`. **Nunca usar em produção.**

## Variáveis de Ambiente

### Backend (.env)

| Variável | Descrição | Dev | Produção |
|----------|-----------|-----|----------|
| `APP_DEBUG` | Debug mode | `true` | `false` |
| `APP_URL` | URL da API | `http://localhost:8000` | `https://api.dominio.ao` |
| `FRONTEND_URL` | URL do frontend | `http://localhost:5173` | `https://app.dominio.ao` |
| `SANCTUM_STATEFUL_DOMAINS` | Domínios SPA (sem protocolo) | `localhost:5173,localhost:8000` | `app.dominio.ao,api.dominio.ao` |
| `SESSION_DOMAIN` | Domínio do cookie | `null` | `.dominio.ao` |
| `SESSION_SECURE_COOKIE` | HTTPS only | `false` | `true` |
| `CORS_ALLOWED_ORIGINS` | Origens permitidas | `http://localhost:5173` | `https://app.dominio.ao` |

### Frontend (.env)

| Variável | Descrição | Dev | Produção |
|----------|-----------|-----|----------|
| `VITE_API_URL` | URL base da API | `http://localhost:8000` | `https://api.dominio.ao` |

## Resolução de Problemas

### "Could not connect to MySQL"
1. Verifique que o Laragon está a correr com MySQL activo.
2. Confirme `DB_HOST=127.0.0.1`, `DB_PORT=3306` no `.env`.

### "Route [login] not defined"
Isto acontece quando um request não autenticado tenta aceder a uma rota protegida. O middleware `Authenticate` foi configurado para retornar 401 JSON em vez de redirecionar.

### Migrations falham com foreign key errors
Execute `php artisan migrate:fresh --seed` para recomeçar do zero.
