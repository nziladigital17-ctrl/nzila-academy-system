# Perfis e Permissões — Nzila Academy

## Sistema de Autorização

O sistema usa um modelo RBAC (Role-Based Access Control) próprio, sem dependências externas.

```
User ──belongsToMany──► Role ──belongsToMany──► Permission
```

## Perfis (9)

| Perfil | Slug | Descrição |
|--------|------|-----------|
| Super Administrador | `super_admin` | Acesso total ao sistema, gestão de todas as escolas |
| Administrador da Escola | `school_admin` | Gestão completa de uma escola específica |
| Diretor | `director` | Supervisão académica e administrativa da escola |
| Coordenador Pedagógico | `pedagogic_coordinator` | Coordenação pedagógica, turmas e avaliações |
| Financeiro | `financial` | Gestão financeira, propinas e despesas |
| Secretária | `secretary` | Gestão de matrículas, alunos e documentação |
| Professor | `teacher` | Gestão de turmas, notas e presenças atribuídas |
| Aluno | `student` | Acesso aos próprios dados académicos |
| Encarregado de Educação | `guardian` | Acompanhamento dos educandos |

## Grupos de Permissões

| Grupo | Permissões |
|-------|-----------|
| schools | view, create, update, delete |
| users | view, create, update, delete, assign_roles |
| students | view, create, update, delete |
| guardians | view, create, update, delete |
| teachers | view, create, update, delete |
| classes | view, create, update, delete, enroll, assign_teacher |
| subjects | view, create, update, delete |
| rooms | view, create, update, delete |
| academic_years | view, create, update, delete |
| grades | view, create, update |
| attendance | view, create, update |
| finance | view, create, update, approve, delete |
| messages | send, view |
| audit | view |
| reports | view, export |

## Mapeamento Perfil → Permissões

| Perfil | Permissões |
|--------|-----------|
| Super Administrador | **TODAS** |
| Administrador da Escola | Todas excepto `schools.create` e `schools.delete` |
| Diretor | Escolas (view), utilizadores, alunos, professores, turmas, disciplinas, salas, anos, notas, presenças, finanças, mensagens, relatórios, auditoria |
| Coordenador Pedagógico | Alunos, encarregados, professores, turmas, disciplinas, salas, anos, notas, presenças, mensagens, relatórios |
| Financeiro | Finanças (todas), alunos (view), mensagens, relatórios |
| Secretária | Alunos (CRUD), encarregados (CRUD), utilizadores (view), turmas (view, enroll), anos (view), salas (view), mensagens |
| Professor | Turmas (view), alunos (view), notas (CRUD), presenças (CRUD), disciplinas (view), anos (view), mensagens |
| Aluno | Notas (view), presenças (view), mensagens |
| Encarregado | Notas (view), presenças (view), finanças (view), mensagens |

## Fluxo de Verificação

1. **`auth:sanctum`** — Utilizador está autenticado?
2. **`school`** (EnsureBelongsToSchool) — O recurso pertence à escola do utilizador?
3. **`permission:slug`** (CheckPermission) — O utilizador tem a permissão necessária?
4. **Policy** (opcional) — Regras de negócio granulares

Super Admins ultrapassam os passos 2 e 3 automaticamente.
