# API REST — Nzila Academy

## URL Base

| Ambiente | URL |
|----------|-----|
| Desenvolvimento | `http://localhost:8000/api/v1` |
| Produção | `https://api.dominio.ao/api/v1` |

> [!NOTE]
> O prefixo `/api/v1` é configurado no `RouteServiceProvider`. As rotas em `routes/api.php` não incluem este prefixo — é adicionado automaticamente.

## Autenticação

Todas as rotas (excepto login) requerem autenticação via Bearer token:

```
Authorization: Bearer {token}
```

### Obter Token

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "admin@demo.nzila.ao",
  "password": "password"
}
```

**Resposta (200):**
```json
{
  "message": "Login realizado com sucesso.",
  "data": {
    "user": { "id": 1, "name": "...", "email": "..." },
    "token": "1|abc123...",
    "permissions": ["schools.view", "users.view", "..."]
  }
}
```

## Formato de Respostas

**Sucesso:**
```json
{
  "message": "Descrição da acção",
  "data": { ... }
}
```

**Erro de validação (422):**
```json
{
  "message": "Os dados fornecidos são inválidos.",
  "errors": {
    "campo": ["Mensagem de erro"]
  }
}
```

**Erro de permissão (403):**
```json
{
  "message": "Não tem permissão para realizar esta acção."
}
```

**Não autenticado (401):**
```json
{
  "message": "Unauthenticated."
}
```

## Endpoints Implementados

### Autenticação

| Método | Endpoint | Descrição | Auth | Permissão |
|--------|----------|-----------|:----:|-----------|
| POST | `/auth/login` | Login → retorna token | — | — |
| POST | `/auth/logout` | Revogar token | ✓ | — |
| GET | `/auth/me` | Perfil do utilizador | ✓ | — |
| PUT | `/auth/password` | Alterar password | ✓ | — |

### Escolas

| Método | Endpoint | Descrição | Permissão | Nota |
|--------|----------|-----------|-----------|------|
| GET | `/schools` | Listar escolas | `schools.view` | Non-super-admin vê apenas a própria |
| POST | `/schools` | Criar escola | `schools.create` | Apenas super admin |
| GET | `/schools/{id}` | Ver escola | `schools.view` | Verificação de pertencimento |
| PUT | `/schools/{id}` | Actualizar | `schools.update` | Verificação de pertencimento |
| DELETE | `/schools/{id}` | Soft delete | `schools.delete` | Apenas super admin |

### Utilizadores

| Método | Endpoint | Descrição | Permissão | Nota |
|--------|----------|-----------|-----------|------|
| GET | `/users` | Listar | `users.view` | Filtrado por school_id |
| POST | `/users` | Criar | `users.create` | school_id forçado (non-SA) |
| GET | `/users/{id}` | Ver | `users.view` | Verificação de escola |
| PUT | `/users/{id}` | Actualizar | `users.update` | Verificação de escola |
| DELETE | `/users/{id}` | Soft delete | `users.delete` | Verificação de escola |
| POST | `/users/{id}/roles` | Atribuir perfil | `users.assign_roles` | Verificação de escola |
| DELETE | `/users/{id}/roles/{roleId}` | Remover perfil | `users.assign_roles` | Verificação de escola |

### Perfis e Permissões

| Método | Endpoint | Descrição | Permissão |
|--------|----------|-----------|-----------|
| GET | `/roles` | Listar perfis | `users.view` |
| GET | `/roles/{id}` | Ver com permissões | `users.view` |
| PUT | `/roles/{id}/permissions` | Sincronizar permissões | `users.assign_roles` |
| GET | `/permissions` | Listar permissões (agrupadas) | `users.view` |

### Alunos

| Método | Endpoint | Descrição | Permissão | Nota |
|--------|----------|-----------|-----------|------|
| GET | `/students` | Listar (filtros: search, status) | `students.view` | Filtrado por global scope |
| POST | `/students` | Registar | `students.create` | SA deve fornecer school_id |
| GET | `/students/{id}` | Ver com encarregados | `students.view` | Global scope |
| PUT | `/students/{id}` | Actualizar | `students.update` | Global scope |
| DELETE | `/students/{id}` | Soft delete | `students.delete` | Global scope |
| POST | `/students/{id}/guardians` | Associar encarregado | `students.update` | Validação de mesma escola |

### Finanças (Fase 4/6)

| Método | Endpoint | Descrição | Permissão |
|--------|----------|-----------|-----------|
| GET | `/tuition-plans` | Listar planos de propinas | `finance.view` |
| POST | `/invoices` | Criar factura | `finance.create` |
| POST | `/invoices/{id}/void` | Anular factura | `finance.update` |
| POST | `/invoices/{id}/adjustments` | Aplicar ajuste | `finance.create` |
| POST | `/payments` | Registar pagamento | `finance.create` |
| POST | `/payments/{id}/void` | Anular pagamento | `finance.update` |
| POST | `/payments/{id}/refund` | Estornar pagamento | `finance.update` |
| GET | `/receipts/{id}/download` | Descarregar recibo | `finance.view` |
| GET | `/debtors` | Listar devedores | `finance.view` |
| POST | `/expenses` | Criar despesa | `finance.create` |
| POST | `/expenses/{id}/confirm` | Confirmar despesa | `finance.update` |
| POST | `/expenses/{id}/void` | Anular despesa | `finance.update` |
| GET/POST | `/financial-settings` | Ver/Actualizar configurações | `finance.view`/`.update` |

## Endpoints Futuros

Documentados em `routes/api.php` como placeholders:

- `GET/POST` `/guardians` — CRUD de encarregados
- `GET/POST` `/teachers` — CRUD de professores
- `GET/POST` `/subjects` — CRUD de disciplinas
- `GET/POST` `/rooms` — CRUD de salas
- `GET/POST` `/academic-years` — CRUD de anos lectivos
- `GET/POST` `/academic-years/{id}/terms` — Trimestres
- `GET/POST` `/classes` — CRUD de turmas
- `POST` `/classes/{id}/enrollments` — Matrículas
- `POST` `/classes/{id}/teacher-assignments` — Atribuição de professores
- `GET/POST` `/classes/{id}/assessments` — Avaliações
- `POST` `/assessments/{id}/grades` — Lançamento de notas
- `GET/POST` `/classes/{id}/attendance` — Presenças
- `GET/POST` `/messages` — Mensagens internas
- `GET` `/notifications` — Notificações
- `GET` `/audit-logs` — Logs de auditoria

## Isolamento Multi-escola

Todas as queries são automaticamente filtradas pelo `school_id` do utilizador autenticado. Super admins vêem dados de todas as escolas.

Um utilizador da Escola A **nunca** consegue:
- Listar alunos/utilizadores da Escola B
- Ver/editar/eliminar registos da Escola B
- Associar encarregados de outra escola
- Criar utilizadores noutra escola
