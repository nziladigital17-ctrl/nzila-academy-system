# Deploy em cPanel — Nzila Academy

## Requisitos do Servidor

- PHP 8.1+ com extensões: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- MySQL 8.0+ ou MariaDB 10.6+
- Composer 2.x (ou upload de vendor/)
- Node.js 20+ (para build do frontend)
- Acesso SSH (recomendado)

## Estrutura no Servidor

```
public_html/
├── api/                    → Backend (public/ do Laravel)
│   ├── index.php
│   ├── .htaccess
│   └── storage -> ../../backend/storage/app/public
├── index.html              → Frontend (build do Vite)
├── assets/                 → Assets do frontend
└── ...

backend/                    → Laravel (fora de public_html)
├── app/
├── config/
├── database/
├── storage/
├── vendor/
├── .env
└── ...
```

## Passo a Passo

### 1. Build do Frontend

No ambiente local:
```bash
cd frontend
npm run build
```

Upload do conteúdo de `frontend/dist/` para `public_html/`.

### 2. Upload do Backend

Upload de todo o conteúdo de `backend/` (excepto `node_modules/` e `.env`) para uma pasta `backend/` fora de `public_html/`.

Copiar `backend/public/` para `public_html/api/`.

### 3. Configurar index.php

Editar `public_html/api/index.php`:
```php
// Alterar caminhos para apontar para a pasta backend
require __DIR__.'/../../backend/vendor/autoload.php';
$app = require_once __DIR__.'/../../backend/bootstrap/app.php';
```

### 4. Configurar .env de Produção

Criar `backend/.env` com as credenciais de produção:
```env
APP_NAME="Nzila Academy"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GERE_UMA_NOVA_CHAVE_COM_artisan_key_generate
APP_URL=https://api.dominio.ao

FRONTEND_URL=https://app.dominio.ao

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=nome_da_base
DB_USERNAME=utilizador_mysql
DB_PASSWORD=password_segura

SESSION_DRIVER=file
SESSION_LIFETIME=120

# ── Sanctum (CRÍTICO para SPA) ──
SANCTUM_STATEFUL_DOMAINS=app.dominio.ao,api.dominio.ao
SANCTUM_TOKEN_EXPIRATION=480

# ── Sessão (partilha de cookies entre subdomínios) ──
SESSION_DOMAIN=.dominio.ao
SESSION_SECURE_COOKIE=true

# ── CORS (apenas o frontend de produção) ──
CORS_ALLOWED_ORIGINS=https://app.dominio.ao
```

> [!CAUTION]
> **Nunca** usar `APP_DEBUG=true` em produção. Nunca expor a chave `APP_KEY`.

### 5. Executar Migrations

Via SSH:
```bash
cd backend
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Permissões

```bash
chmod -R 775 backend/storage
chmod -R 775 backend/bootstrap/cache
```

## Checklist de Segurança em Produção

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS obrigatório em todos os endpoints
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_DOMAIN=.dominio.ao` (com ponto para subdomínios)
- [ ] `CORS_ALLOWED_ORIGINS` restrito ao frontend real
- [ ] `SANCTUM_STATEFUL_DOMAINS` sem `localhost`
- [ ] `.env` não acessível via HTTP
- [ ] `storage/` não acessível via HTTP
- [ ] Rate limiting activo (60 req/min default)
- [ ] Logs de erro monitorizados
- [ ] Backups automáticos da base de dados
- [ ] APP_KEY regenerada e segura

## Configuração HTTPS + Subdomínios

Para SPA com cookies de sessão (recomendado):

| Subdomínio | Conteúdo |
|-----------|----------|
| `app.dominio.ao` | Frontend React (SPA) |
| `api.dominio.ao` | Backend Laravel API |

Ambos devem ter certificados SSL válidos. O cookie de sessão é partilhado via `SESSION_DOMAIN=.dominio.ao`.
