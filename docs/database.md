# Base de Dados — Nzila Academy

## Motor

- MySQL 8.0+ ou MariaDB 10.6+
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Nome da base: `nzila_academy`

## Convenções

- Nomes de tabelas em inglês, plural, snake_case
- Chaves primárias: `id` (bigint auto-increment)
- Chaves estrangeiras: `{tabela_singular}_id`
- Timestamps: `created_at`, `updated_at`
- Soft deletes: `deleted_at` (onde aplicável)
- Valores monetários: `DECIMAL(15,2)` — Kwanza angolano (AOA)

## Diagrama Relacional

### Núcleo

```
schools (1) ──── (N) users ──── (N:M) roles ──── (N:M) permissions
```

### Académico

```
schools (1) ──── (N) academic_years (1) ──── (N) terms
schools (1) ──── (N) students ──── (N:M) guardians
schools (1) ──── (N) teachers
schools (1) ──── (N) subjects
schools (1) ──── (N) rooms
schools (1) ──── (N) classes ──── (N) enrollments ──── (1) student
                                 ──── (N) teacher_assignments ──── (1) teacher
```

### Avaliação

```
classes (1) ──── (N) assessments (1) ──── (N) grades ──── (1) enrollment
classes (1) ──── (N) attendance_records (1) ──── (N) attendance_items ──── (1) enrollment
```

### Financeiro

```
schools (1) ──── (N) tuition_plans
schools (1) ──── (N) invoices (1) ──── (N) invoice_items
                               ──── (N) payments (1) ──── (1) receipt
schools (1) ──── (N) expenses
```

### Comunicação / Auditoria

```
users (1) ──── (N) messages (sender/receiver)
users (1) ──── (N) notifications
users (1) ──── (N) audit_logs (morphTo: auditable)
```

## Tabelas (30 total)

| # | Tabela | Soft Delete | Auditável | school_id |
|---|--------|:-----------:|:---------:|:---------:|
| 1 | schools | ✓ | ✓ | — |
| 2 | users | ✓ | ✓ | ✓ (nullable) |
| 3 | roles | — | — | — |
| 4 | permissions | — | — | — |
| 5 | role_user | — | — | ✓ |
| 6 | permission_role | — | — | — |
| 7 | academic_years | — | ✓ | ✓ |
| 8 | terms | — | — | — |
| 9 | students | ✓ | ✓ | ✓ |
| 10 | guardians | ✓ | ✓ | ✓ |
| 11 | student_guardians | — | — | — |
| 12 | teachers | ✓ | ✓ | ✓ |
| 13 | subjects | — | — | ✓ |
| 14 | rooms | — | — | ✓ |
| 15 | classes | ✓ | ✓ | ✓ |
| 16 | enrollments | — | ✓ | ✓ |
| 17 | teacher_assignments | — | ✓ | — |
| 18 | assessments | — | — | — |
| 19 | grades | — | ✓ | — |
| 20 | attendance_records | — | — | — |
| 21 | attendance_items | — | — | — |
| 22 | tuition_plans | — | — | ✓ |
| 23 | invoices | ✓ | ✓ | ✓ |
| 24 | invoice_items | — | — | — |
| 25 | payments | — | ✓ | — |
| 26 | receipts | — | — | — |
| 27 | expenses | ✓ | ✓ | ✓ |
| 28 | messages | ✓ | — | ✓ |
| 29 | notifications | — | — | — |
| 30 | audit_logs | — | — | ✓ |
