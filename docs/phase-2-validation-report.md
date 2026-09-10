# Phase 2 Validation Report

## Resumo
A Fase 2 — Implementação da API Completa foi concluída com sucesso. Todos os 11 módulos (Professores, Disciplinas, Salas, Anos Lectivos, Trimestres, Turmas, Associações de Professores, Encarregados, Alunos e Matrículas) estão agora suportados com as respectivas regras de negócio, Form Requests, Resources, isolamento multi-escola e cobertura de testes.

## Alterações de Schema (Migrations)
- Tabela `enrollments`: adicionado `school_id` para resolver o escopo `BelongsToSchool` directamente sem joins desnecessários.
- Tabelas `teachers` e `students`: a restrição `unique` global no `employee_number` e `student_number` foi ajustada para `unique(['school_id', 'column'])`, permitindo números iguais em escolas diferentes.
- Tabela `student_guardians` (pivot): adicionada a coluna `relationship` com valores predefinidos, possibilitando que o mesmo encarregado tenha papéis diferentes dependendo do aluno.
- Tabelas `subjects` e `rooms`: adicionado `softDeletes`.

## Regras de Negócio e Controllers
- **Isolamento de Escolas (`BelongsToSchool`)**: Aplicado a todos os modelos relevantes (`Enrollment`, `TeacherAssignment`, etc.). Todas as chamadas para associar relações validam se os intervenientes pertencem à escola do utilizador autenticado (ou fornecido pelo Super Admin).
- **Service Layer (`EnrollmentService`)**: Garante lock pessimista, valida capacidade da turma, verifica duplicação no mesmo ano lectivo e se o ano lectivo está activo na criação da matrícula.
- **Validações (`FormRequests`)**: Todas as regras e lógicas complexas foram extraídas dos controllers para form requests dedicados, incluindo regras complexas como impedir sobreposição de datas de trimestres dentro do mesmo ano letivo.
- **JSON Resources**: Padronização da API de saída, incluindo o carregamento condicional de relacionamentos (`whenLoaded`).

## Permissões (RBAC)
- Adicionados os grupos de permissões `enrollments.*` e `teaching_assignments.*`.
- Atualizado o mapeamento `RoleEnum::forRole()` de modo a atribuir estes novos grupos aos Diretores, Coordenadores Pedagógicos, Secretárias e Professores (consoante o nível de acesso).

## Validação Técnica

### Artefatos TypeScript Removidos
A limpeza de artefatos sugerida no Frontend foi concluída com sucesso: `vite.config.js`, `vite.config.d.ts`, `tsconfig.node.tsbuildinfo` removidos do Tracking do Git e eliminados.

### Testes Implementados
1. `GuardianTest`: Escrito do zero, testando os CRUDs e isolamento.
2. `StudentGuardianTest`: Novo teste para o pivot (Adicionar/Remover, encarregado principal único, etc.).
3. `EnrollmentTest`: Testes de CRUD.
4. `EnrollmentBusinessRuleTest`: Regras complexas (capacidade de turmas, ano fechado, escolas distintas, etc).
5. `TeacherTest`: Aperfeiçoado com isolamento de permissões e chaves compostas (unique).

### Conclusão
O sistema está pronto para a **Fase 3 — Frontend Base**, tendo o backend completo da API, documentado, estruturado e testado para os módulos centrais da gestão académica.
