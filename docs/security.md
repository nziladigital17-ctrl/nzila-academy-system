# Segurança — Nzila Academy

## Princípios Fundamentais

1. **Defense in Depth**: Múltiplas camadas de protecção (middleware, global scopes, validação em services).
2. **Least Privilege**: Cada perfil tem apenas as permissões estritamente necessárias.
3. **Audit Trail**: Todas as acções críticas são registadas em `audit_logs`.
4. **Dados Sensíveis**: Passwords, tokens e segredos nunca são gravados em logs.

## Autenticação

### Sanctum — Modo Token (Bearer)

O sistema usa Laravel Sanctum para autenticação via tokens. Cada login:

1. Revoga todos os tokens anteriores do utilizador (política de sessão única).
2. Cria um novo token com expiração configurável via `SANCTUM_TOKEN_EXPIRATION` (default: 1440 min = 24h).
3. Retorna o token no corpo da resposta JSON.

```
POST /api/v1/auth/login
→ { "token": "1|abc123...", "user": {...} }
```

**Ciclo de vida do token:**

| Evento | Acção |
|--------|-------|
| Login | Tokens antigos são revogados. Novo token criado. |
| Logout | Token actual é revogado. |
| Expiração | Token torna-se inválido após `SANCTUM_TOKEN_EXPIRATION` minutos. |
| Mudança de password | Apenas o token actual é revogado (requer novo login). |

### Armazenamento Seguro de Tokens

> [!WARNING]
> **NÃO guardar tokens em `localStorage`**. Use `sessionStorage` ou cookies `HttpOnly` quando o frontend for construído.

Para a futura SPA React, recomenda-se usar Sanctum no **modo SPA (cookie-based)**, que usa cookies de sessão em vez de tokens Bearer em `localStorage`:

```
GET /sanctum/csrf-cookie → define cookie XSRF-TOKEN
POST /api/v1/auth/login  → define cookie de sessão
```

### Configuração para Produção

```env
# Sanctum — domínios que usam cookies (SEM protocolo)
SANCTUM_STATEFUL_DOMAINS=app.dominio.ao,api.dominio.ao

# Sessão — partilha de cookies entre subdomínios
SESSION_DOMAIN=.dominio.ao
SESSION_SECURE_COOKIE=true

# CORS — apenas o frontend de produção
CORS_ALLOWED_ORIGINS=https://app.dominio.ao
```

## Autorização (RBAC)

### Fluxo de Verificação por Request

```
Request HTTP
  │
  ├─ auth:sanctum ─────── Utilizador autenticado?
  │                         └─ 401 se não
  │
  ├─ school (middleware) ── Route model pertence à escola do user?
  │                         └─ 403 se não (bypass para super admin)
  │
  ├─ permission:slug ──── User tem a permissão requerida?
  │                         └─ 403 se não (bypass para super admin)
  │
  └─ Controller/Service ── Lógica de negócio
```

### Isolamento Multi-escola

O isolamento é garantido em 3 camadas:

1. **Global Scope (`BelongsToSchool` trait)**: Todas as queries Eloquent são automaticamente filtradas por `school_id` do utilizador autenticado. Super admins (sem `school_id`) não são filtrados.

2. **Middleware (`EnsureBelongsToSchool`)**: Verifica se os route parameters (modelos resolvidos) pertencem à escola do utilizador.

3. **Controller-level validation**: Controllers validam explicitamente o `school_id` em operações de escrita (create, attach).

### Campos Nunca Gravados em Audit Logs

```
password, remember_token, two_factor_secret,
two_factor_recovery_codes, api_token
```

## Protecções Implementadas

| Protecção | Implementação |
|-----------|---------------|
| Password hashing | bcrypt/Argon2 via Laravel |
| CORS | `config/cors.php` — restrito ao frontend |
| CSRF | Sanctum SPA mode com `X-XSRF-TOKEN` |
| Rate Limiting | 60 req/min via `ThrottleRequests` middleware |
| SQL Injection | Eloquent ORM + parametrized queries |
| Mass Assignment | `$fillable` em todos os models |
| XSS | JSON-only API (sem HTML rendering) |
| Soft Delete | Dados nunca são permanentemente apagados |
| Audit Trail | Todas as mutações são registadas com IP + user agent |
| Input Validation | Validação em controllers antes de qualquer operação |

## Checklist para Produção

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS obrigatório (`SESSION_SECURE_COOKIE=true`)
- [ ] `CORS_ALLOWED_ORIGINS` restrito ao domínio real
- [ ] `SANCTUM_STATEFUL_DOMAINS` correcto para produção
- [ ] `SESSION_DOMAIN=.dominio.ao` (com ponto)
- [ ] APP_KEY gerada e guardada em segurança
- [ ] `.env` não incluído no repositório
- [ ] Rate limiting activo
- [ ] Backups automáticos da base de dados
- [ ] Monitorização de logs de erro
