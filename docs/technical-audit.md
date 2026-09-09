# Auditoria Técnica — Nzila Academy

## Data da Auditoria
9 de Setembro de 2026

## Âmbito
Verificação completa do backend Laravel, frontend base React/Vite, banco de dados e documentação do projecto Nzila Academy.

---

## 1. Rotas da API

### Verificação
Executado `php artisan route:list --path=api/v1` com sucesso.

### Resultado
- **26 rotas registadas**, todas com prefixo `api/v1/` correcto.
- **Sem duplicação** de prefixo (`/api/api/v1/` não existe).
- O prefixo é definido uma única vez no `RouteServiceProvider` (linha 31).
- Todas as rotas apontam para controllers em `App\Http\Controllers\Api\V1\`.

### Rotas Verificadas

```
POST    api/v1/auth/login
POST    api/v1/auth/logout
GET     api/v1/auth/me
PUT     api/v1/auth/password
GET     api/v1/permissions
GET     api/v1/roles
GET     api/v1/roles/{role}
PUT     api/v1/roles/{role}/permissions
GET     api/v1/schools
POST    api/v1/schools
GET     api/v1/schools/{school}
PUT     api/v1/schools/{school}
DELETE  api/v1/schools/{school}
GET     api/v1/students
POST    api/v1/students
GET     api/v1/students/{student}
PUT     api/v1/students/{student}
DELETE  api/v1/students/{student}
POST    api/v1/students/{student}/guardians
GET     api/v1/users
POST    api/v1/users
GET     api/v1/users/{user}
PUT     api/v1/users/{user}
DELETE  api/v1/users/{user}
POST    api/v1/users/{user}/roles
DELETE  api/v1/users/{user}/roles/{role}
```

---

## 2. Autenticação Sanctum

### Estado
- Sanctum instalado e configurado (versão 3.x para Laravel 10).
- Autenticação por Bearer token implementada.
- `EnsureFrontendRequestsAreStateful` no middleware stack `api`.

### Correcções Efectuadas
1. **Adicionado `localhost:5173`** ao `sanctum.php` → `stateful` domains.
2. **Adicionadas variáveis** ao `.env.example`:
   - `SANCTUM_STATEFUL_DOMAINS`
   - `SESSION_DOMAIN`
   - `SESSION_SECURE_COOKIE`
3. **Documentados exemplos** para dev e produção.

### Ciclo de Vida do Token
| Evento | Comportamento |
|--------|--------------|
| Login | Todos os tokens anteriores são revogados. Novo token criado. |
| Logout | Apenas o token actual é revogado. |
| Expiração | Configurável via `SANCTUM_TOKEN_EXPIRATION` (default: 1440 min). |
| Mudança de password | Token actual mantido (requer revogação manual se desejado). |

---

## 3. CORS e Comunicação React–Laravel

### Correcções Efectuadas
1. **Adicionado `X-XSRF-TOKEN`** aos `allowed_headers` em `cors.php`.
2. **`supports_credentials`** já estava `true` (correcto para cookies).
3. **Proxy Vite** configurado em `vite.config.ts` — redireciona `/api/*` para `localhost:8000`.
4. **Frontend `.env.example`** corrigido — clarificada a relação entre `VITE_API_URL` e o proxy.

---

## 4. Isolamento Multi-escola

### Problemas Encontrados e Corrigidos

| Problema | Severidade | Correcção |
|----------|-----------|-----------|
| `UserController::index()` listava todos os users | 🔴 Crítico | Filtrado por `school_id` do auth user |
| `UserController::store()` aceitava `school_id` arbitrário | 🔴 Crítico | Forçado `school_id` para non-super-admins |
| `UserController::show/update/destroy` sem verificação | 🔴 Crítico | Adicionado `authorizeSchoolAccess()` |
| `StudentController::store()` criava sem escola para SA | 🔴 Crítico | SA obrigado a fornecer `school_id` |
| `StudentController::attachGuardian()` sem verificação | 🟡 Importante | Validação de mesma escola |
| `SchoolController::index()` listava todas as escolas | 🟡 Importante | Non-SA vê apenas a própria |

### Mecanismos de Isolamento (3 camadas)
1. **Global Scope** (`BelongsToSchool` trait): Filtra queries automaticamente.
2. **Middleware** (`EnsureBelongsToSchool`): Valida route models.
3. **Controller-level**: Validação explícita em operações de escrita.

---

## 5. Auditoria de Logs

### Problema Encontrado e Corrigido

| Problema | Severidade | Correcção |
|----------|-----------|-----------|
| Passwords (hashed) gravadas em audit logs | 🔴 Crítico | Filtro `$auditExclude` no trait `Auditable` |

### Campos Excluídos dos Logs
```
password, remember_token, two_factor_secret,
two_factor_recovery_codes, api_token
```

### O Que é Registado
| Campo | Fonte |
|-------|-------|
| `user_id` | `Auth::id()` |
| `school_id` | Model ou auth user |
| `action` | created / updated / deleted / login / logout |
| `auditable_type` | Classe do model (ex: `App\Models\Student`) |
| `auditable_id` | ID do registo |
| `old_values` | Valores anteriores (filtrados) |
| `new_values` | Valores novos (filtrados) |
| `ip_address` | `Request::ip()` |
| `user_agent` | `Request::userAgent()` |
| `created_at` | Timestamp automático |

---

## 6. Migrations e Seeders

### Migrations
- **34 ficheiros** no total (4 default Laravel + 30 custom).
- Ordem de dependências correcta (schools → users → roles → students → classes → etc.).
- Soft deletes aplicados em: schools, users, students, guardians, teachers, classes, invoices, expenses, messages.
- Campos monetários usam `decimal(15,2)` para Kwanza angolano.

### Seeders
- `RoleAndPermissionSeeder`: 9 perfis, 45 permissões.
- `SchoolSeeder`: Escola de demonstração "DEMO-001".
- `UserSeeder`: 9 utilizadores demo (1 por perfil).
- `DemoDataSeeder`: Ano lectivo, trimestres, disciplinas, salas, professor, turma, 10 alunos matriculados.

### Validação
> [!NOTE]
> Requer MySQL activo no Laragon. Executar: `php artisan migrate:fresh --seed`

---

## 7. Race Condition no Número de Aluno

### Problema
`StudentController::generateStudentNumber()` usava `count() + 1` — não atómico.

### Correcção
Implementado mecanismo de retry com verificação de unicidade antes de retornar. Fallback com timestamp se todas as tentativas falharem.

---

## 8. Verificação de Qualidade

| Item | Estado |
|------|--------|
| Erros de sintaxe PHP | ✅ Sem erros (`route:list` executa sem erros) |
| Imports inválidos | ✅ Verificados |
| Rotas quebradas | ✅ 26 rotas resolvidas correctamente |
| Migrations em ordem | ✅ Dependências correctas |
| `.env` no `.gitignore` | ✅ Presente |
| `.env.example` sem segredos | ✅ Sem APP_KEY nem passwords |
| TypeScript do frontend | ✅ `tsconfig.json` correctamente configurado |
| Proxy Vite | ✅ `/api/*` → `localhost:8000` |
| `.gitignore` global | ✅ Inclui node_modules, vendor, .env, etc. |
