# Arquitectura — Nzila Academy

## Visão Geral

O Nzila Academy é um sistema de gestão escolar para Angola, construído como um monorepo com frontend e backend separados.

## Stack Tecnológica

| Camada | Tecnologia | Versão |
|---|---|---|
| Frontend | React + Vite + TypeScript | React 18, Vite 5 |
| Backend | PHP + Laravel | PHP 8.1, Laravel 10 |
| Base de Dados | MySQL / MariaDB | MySQL 8.0+ |
| Autenticação | Laravel Sanctum | 3.x |
| Ambiente | Laragon (Windows) | — |

## Estrutura do Monorepo

```
nzila-academy-system/
├── frontend/        → SPA React (porta 5173)
├── backend/         → API REST Laravel (porta 8000)
├── docs/            → Documentação técnica
└── README.md
```

## Arquitectura da Aplicação

```
┌──────────────────────┐
│   React SPA          │
│   localhost:5173      │
│                      │
│   Authorization:     │
│   Bearer {token}     │
└──────────┬───────────┘
           │ HTTP/JSON
           ▼
┌──────────────────────────────────────────────┐
│   Laravel API  (localhost:8000/api/v1)       │
│                                              │
│  ┌─────────────┐  ┌──────────────────────┐  │
│  │ Middleware   │  │ Controllers (Api/V1) │  │
│  │ - Sanctum   │→ │ - AuthController     │  │
│  │ - School    │  │ - SchoolController   │  │
│  │ - Permission│  │ - StudentController  │  │
│  │ - Audit     │  │ - ...                │  │
│  └─────────────┘  └──────────┬───────────┘  │
│                              │               │
│                   ┌──────────▼───────────┐  │
│                   │ Services             │  │
│                   │ - AuthService        │  │
│                   │ - EnrollmentService  │  │
│                   │ - InvoiceService     │  │
│                   │ - PaymentService     │  │
│                   │ - GradeService       │  │
│                   └──────────┬───────────┘  │
│                              │               │
│                   ┌──────────▼───────────┐  │
│                   │ Models + Eloquent    │  │
│                   │ Traits:              │  │
│                   │ - BelongsToSchool    │  │
│                   │ - Auditable          │  │
│                   └──────────┬───────────┘  │
└──────────────────────────────┼───────────────┘
                               │
                    ┌──────────▼───────────┐
                    │    MySQL / MariaDB    │
                    │    nzila_academy      │
                    └──────────────────────┘
```

## Princípios de Arquitectura

1. **API-first**: Backend serve apenas JSON, sem templates ou views.
2. **Multi-tenancy leve**: Isolamento por `school_id` com global scope.
3. **RBAC próprio**: Sistema Role→Permission sem dependências externas.
4. **Auditoria automática**: Trait `Auditable` + Observer nos modelos sensíveis.
5. **Transacções**: Operações críticas (matrículas, pagamentos, notas) usam `DB::transaction`.
6. **Soft Delete**: Entidades como escolas, alunos, facturas usam soft delete.
7. **Separação de responsabilidades**: Controllers → Services → Models.

## Segurança

- Autenticação via tokens Sanctum (Bearer tokens)
- Passwords com bcrypt/Argon2
- CORS configurado para o frontend local
- Middleware de permissões por rota
- Middleware de isolamento por escola
- Validação de inputs com Form Requests
- Rate limiting na API (60 req/min)
- Nenhum dado sensível no repositório (.env.example sem chaves)
