# Fase 5: Fundação do Frontend React SPA

## Objetivo

Esta fase estabeleceu a estrutura fundamental da SPA (Single Page Application) baseada em React (Vite + TypeScript) para consumir a API Laravel, assegurando roteamento, estado global de autenticação e comunicação HTTP padrão.

## Arquitetura

O frontend segue uma organização por responsabilidades (`/src`):
- `api/`, `lib/`: Configurações de clientes (Axios).
- `auth/`, `stores/`: Estado global de autenticação usando Zustand.
- `layouts/`: Componentes contentores (`AppLayout`, `GuestLayout`).
- `pages/`: Vistas associadas às rotas.
- `routes/`: Definições do React Router (públicas e privadas).
- `types/`: Declarações TypeScript globais (`index.ts`, `auth.ts`).

## Implementação Técnica

### Cliente HTTP (Axios)
A configuração central em `src/lib/axios.ts` inclui:
1. **Base URL**: Configurável via `VITE_API_URL` no `.env`.
2. **Request Interceptor**: Adiciona o cabeçalho `Authorization: Bearer <token>` caso o utilizador esteja autenticado.
3. **Response Interceptor**: Invalida a sessão local e redireciona forçosamente para `/login` quando recebe um erro **401 Unauthorized** (token expirado/inválido).

### Estado Global (Zustand)
Utilizado para armazenar dados do utilizador de forma robusta e tipificada.
Persistido temporariamente em `localStorage` (via middleware `persist` do Zustand).

### Roteamento e Proteção
Implementou-se um componente genérico `<ProtectedRoute>` para envolver rotas que exijam autenticação ou uma permissão RBAC específica.

## Testes
Inclui-se suporte a testes via `Vitest`. Os testes abrangem as mutações lógicas de início de sessão, encerramento de sessão, estado inicial e verificação de permissões do store de autenticação.
