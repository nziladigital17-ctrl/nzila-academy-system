# Nzila Academy System

Sistema de gestão escolar para Angola.

## Estrutura

```
nzila-academy-system/
├── frontend/    # React + Vite + TypeScript (SPA)
├── backend/     # Laravel 11 API REST
├── docs/        # Documentação técnica
└── README.md
```

## Stack Tecnológica

| Camada   | Tecnologia                    |
|----------|-------------------------------|
| Frontend | React 18, Vite, TypeScript    |
| Backend  | PHP 8.2+, Laravel 11          |
| Base de Dados | MySQL 8 / MariaDB 10.6+ |
| Autenticação | Laravel Sanctum (tokens) |
| Ambiente | Laragon (Windows)             |

## Requisitos

- PHP 8.2+
- Composer 2.x
- Node.js 20+ e npm 10+
- MySQL 8.0+ ou MariaDB 10.6+
- Laragon (recomendado)

## Início Rápido

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

A API estará disponível em `http://localhost:8000/api/v1`.

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

O frontend estará disponível em `http://localhost:5173`.

## Fases de Desenvolvimento

- [x] Fase 1: Fundação Técnica (Laravel + React)
- [x] Fase 2: Estruturação Completa da API REST
- [x] Fase 3: Núcleo Pedagógico (avaliações, notas, presenças e resultados)
- [x] Fase 4: Módulo Financeiro
- [x] Fase 5: Fundação do Frontend React SPA (Autenticação, RBAC, Vite, Zustand, Vitest)
- [x] Fase 6: Frontend - Estrutura Académica, Alunos e Matrículas (Fase 6A & 6B)
- [ ] Fase 7: Frontend - Pedagógico e Financeiro
- [ ] Fase 8: Portal do Aluno/Encarregado
- [ ] Fase 9: Produção

## Documentação

Consulte a pasta `docs/` para documentação completa:

- [Arquitectura](docs/architecture.md)
- [Base de Dados](docs/database.md)
- [API](docs/api.md)
- [Perfis e Permissões](docs/roles-and-permissions.md)
- [Setup com Laragon](docs/setup-laragon.md)
- [Deploy em cPanel](docs/deployment-cpanel.md)
- [Roadmap](docs/roadmap.md)

## Licença

Proprietário — Todos os direitos reservados.
