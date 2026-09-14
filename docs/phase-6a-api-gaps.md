# Relatório de Divergências da API — Fase 6A

Durante a implementação do módulo de Estrutura Académica (Fase 6A), o frontend foi adaptado para consumir os contratos exactos expostos pelo backend.

Foram identificadas as seguintes divergências/ausências face a um design system ideal, mas foram contornadas no frontend **sem alterar o backend**:

## 1. `TermResource` não retorna estado (Status)
- **O que falta:** O backend não indica se o trimestre está "Planeado", "Em curso" ou "Encerrado".
- **Solução implementada:** O frontend avalia dinamicamente as datas `start_date` e `end_date` do trimestre contra a data actual (`Date.now()`) no ficheiro `TermsTab.tsx`.

## 2. `ClassResource` não inclui contagem de alunos
- **O que falta:** A tabela de turmas precisaria exibir quantos alunos estão matriculados (ex: 20/35). O backend retorna apenas a capacidade máxima (`max_students`).
- **Solução implementada:** A coluna de alunos não foi incluída, mantendo-se apenas o indicador de `max_students` disponível. Esta contagem poderá vir a ser adicionada na Fase 6B (Matrículas) via relação `enrollments_count`.

## 3. `ClassResource` e a relação Turma ↔ Disciplina
- **O que foi observado:** O backend possui a coluna genérica `subject_id` no modelo `SchoolClass`. Contudo, em Angola, uma turma tem múltiplas disciplinas.
- **Solução implementada:** O frontend respeita a listagem, mas o foco real foi dado à aba "Atribuições" (`teaching-assignments`), onde o `TeacherAssignmentResource` relaciona `class_id` + `subject_id` + `teacher_id` de forma adequada, suportando o modelo 1-N.
