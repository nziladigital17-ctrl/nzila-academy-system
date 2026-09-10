# Regras Académicas — Nzila Academy System

> Versão: 3.0 — Fase 3 (Backend Core Pedagógico)

---

## 1. Estrutura de Trimestres

O sistema suporta exclusivamente **três trimestres** por ano lectivo:

| Trimestre | Denominação   |
|-----------|---------------|
| 1         | 1.º Trimestre |
| 2         | 2.º Trimestre |
| 3         | 3.º Trimestre |

---

## 2. Escala de Classificação

| Parâmetro      | Valor padrão |
|----------------|-------------|
| Nota mínima    | 0 valores   |
| Nota máxima    | 20 valores  |
| Nota de aprovação | 10 valores |

Notas são armazenadas internamente com precisão de 2 casas decimais.  
Na apresentação e publicação, são **arredondadas para a unidade inteira** usando a regra **"meio para cima" (half-up)**:

```
9,49 → 9
9,50 → 10
14,50 → 15
```

Implementação PHP:
```php
round($value, 0, PHP_ROUND_HALF_UP)
```

---

## 3. Componentes de Avaliação

| Código | Nome                    | Unicidade            |
|--------|-------------------------|----------------------|
| `AC`   | Avaliação Contínua      | Ilimitada por aluno/disciplina/trimestre |
| `PP`   | Prova do Professor      | **Uma** por aluno/disciplina/trimestre |
| `PT`   | Prova Trimestral        | **Uma** por aluno/disciplina/trimestre |

### Regras de negócio
- As avaliações `PP` e `PT` são únicas: o sistema rejeita uma segunda nota activa do mesmo tipo para o mesmo aluno/disciplina/trimestre.
- Para substituir uma `PP` ou `PT`, a nota anterior deve ser **anulada** primeiro.
- A anulação requer um motivo obrigatório (mínimo 10 caracteres) e fica registada no log de auditoria.

---

## 4. Fórmulas de Cálculo

### 4.1 MAC — Média de Avaliação Contínua

```
MAC = média aritmética de todas as notas AC activas do aluno na disciplina e trimestre
```

Configurável via `assessment_settings.nf_formula`.

### 4.2 NF — Nota Final Trimestral

Fórmula padrão:
```
NF = (MAC + PP + PT) / 3
```

Caso o componente PP esteja inactivo (`pp_active = false`):
```
NF = (MAC + PT) / 2
```

Se qualquer componente obrigatório estiver em falta, a NF não é calculada (fica `null`).

### 4.3 MFA — Média Final Anual

Fórmula padrão:
```
MFA = (NF1 + NF2 + NF3) / 3
```

Calculada após a publicação dos três trimestres.

---

## 5. Situação Académica

| Código                  | Descrição                                                   |
|-------------------------|-------------------------------------------------------------|
| `sem_notas`             | Ainda não existem notas suficientes para calcular a situação |
| `em_risco_academico`    | Projecção de NF está abaixo da nota de aprovação            |
| `aprovado`              | NF (arredondada) ≥ nota de aprovação                        |
| `reprovado_por_nota`    | NF (arredondada) < nota de aprovação                        |
| `em_risco_por_faltas`   | Número de faltas está a aproximar-se do limite              |
| `retido_por_faltas`     | Número de faltas atingiu ou ultrapassou o limite            |

---

## 6. Política de Frequência

### 6.1 Modo Padrão Angola (`angola_por_disciplina`)

Limites de faltas **não justificadas** por disciplina, baseados na carga horária semanal:

| Tempos/semana | Limite de faltas |
|---------------|-----------------|
| 1             | 3 faltas        |
| 2             | 4 faltas        |
| ≥ 3           | 5 faltas        |

- Apenas faltas **não justificadas** contam para o limite.
- Faltas **justificadas** (código `J`) são registadas mas não penalizam.

### 6.2 Modo Personalizado (`escola_propria`)

Permite configurar:
- Limite global de faltas por trimestre (por disciplina ou total)
- Incluir ou excluir faltas justificadas da contagem
- Limite absoluto personalizado

---

## 7. Alertas Académicos

| Tipo              | Gatilho                                               |
|-------------------|-------------------------------------------------------|
| `academic_risk`   | Projecção de NF < nota de aprovação                   |
| `attendance_risk` | Faltas atingiram ≥ 80% do limite (configurável)       |

Os alertas são gerados automaticamente após cada lançamento/actualização de nota ou registo de presença.

---

## 8. Fluxo de Pauta (Grade Book)

```
draft → submitted → published → locked
                ↑                  ↓
                └──── (unlock) ────┘
```

| Estado      | Edição de notas | Acção disponível para...                    |
|-------------|-----------------|---------------------------------------------|
| `draft`     | ✅ Permitida    | Professor: submeter                          |
| `submitted` | ❌ Bloqueada    | Coordenador: publicar ou devolver a draft    |
| `published` | ❌ Bloqueada    | Coordenador: bloquear                        |
| `locked`    | ❌ Bloqueada    | Coordenador/Admin: desbloquear (c/ motivo)   |

O desbloqueio requer um motivo obrigatório e fica registado em auditoria.

---

## 9. Arredondamento e Apresentação

| Campo   | Armazenamento | Apresentação |
|---------|---------------|--------------|
| Notas AC/PP/PT | `decimal(5,2)` | 2 casas decimais |
| MAC     | `decimal(5,2)` | 2 casas decimais |
| NF (raw) | `decimal(5,2)` | 2 casas decimais |
| NF (display) | Arredondado | Inteiro (half-up) |
| MFA (raw) | `decimal(5,2)` | 2 casas decimais |
| MFA (display) | Arredondado | Inteiro (half-up) |

---

## 10. Isolamento Multi-tenant

Todos os dados são obrigatoriamente associados a um `school_id`.  
O trait `BelongsToSchool` aplica um global scope que garante que um utilizador de uma escola nunca acede a dados de outra.
