import React, { useEffect, useState, useCallback } from 'react';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import { hasPermission } from '@/stores/authStore';
import { getTeacherAssignments, createTeacherAssignment, deleteTeacherAssignment, getTeachers, getClasses, getSubjects } from '@/api/academicService';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { FormField } from '@/components/ui/FormField';
import { useToast } from '@/components/ui/Toast';
import type { TeacherAssignment, TeacherAssignmentFormData, Teacher, SchoolClass, Subject, AssignmentRole } from '@/types/academic';
import type { ApiError } from '@/types';
import { AxiosError } from 'axios';

interface TeacherAssignmentsTabProps {
  onDataChange: () => void;
}

const ROLE_LABELS: Record<AssignmentRole, string> = { titular: 'Titular', auxiliary: 'Auxiliar' };
const EMPTY_FORM: TeacherAssignmentFormData = { teacher_id: 0, class_id: 0, subject_id: 0, academic_year_id: 0, role: 'titular' };

export const TeacherAssignmentsTab: React.FC<TeacherAssignmentsTabProps> = ({ onDataChange }) => {
  const { selectedYearId } = useAcademicYearStore();
  const { addToast } = useToast();
  const [data, setData] = useState<TeacherAssignment[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({ total: 0, from: 0, to: 0, last_page: 1, current_page: 1, per_page: 15 });

  // For the create form dropdowns
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [subjects, setSubjects] = useState<Subject[]>([]);

  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState<TeacherAssignmentFormData>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<TeacherAssignment | null>(null);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    if (!selectedYearId) return;
    setLoading(true);
    try {
      const res = await getTeacherAssignments({ academic_year_id: selectedYearId, page });
      setData(res.data);
      setMeta(res.meta);
    } catch {
      addToast('error', 'Erro', 'Não foi possível carregar as atribuições.');
    } finally {
      setLoading(false);
    }
  }, [selectedYearId, page, addToast]);

  useEffect(() => { load(); }, [load]);

  const loadDropdownData = useCallback(async () => {
    if (!selectedYearId) return;
    try {
      const [teachersRes, classesRes, subjectsRes] = await Promise.all([
        hasPermission('teachers.view') ? getTeachers({ status: 'active' }) : null,
        hasPermission('classes.view') ? getClasses({ academic_year_id: selectedYearId }) : null,
        hasPermission('subjects.view') ? getSubjects({ status: 'active' }) : null,
      ]);
      if (teachersRes) setTeachers(teachersRes.data);
      if (classesRes) setClasses(classesRes.data);
      if (subjectsRes) setSubjects(subjectsRes.data);
    } catch {
      // Non-critical
    }
  }, [selectedYearId]);

  const openCreate = () => {
    setForm({ ...EMPTY_FORM, academic_year_id: selectedYearId ?? 0 });
    setErrors({});
    setModalOpen(true);
    loadDropdownData();
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setSaving(true);
    try {
      await createTeacherAssignment(form);
      addToast('success', 'Atribuição criada com sucesso.');
      setModalOpen(false);
      load();
      onDataChange();
    } catch (err) {
      const axiosErr = err as AxiosError<ApiError>;
      if (axiosErr.response?.status === 422 && axiosErr.response.data.errors) {
        const fieldErrors: Record<string, string> = {};
        Object.entries(axiosErr.response.data.errors).forEach(([key, msgs]) => {
          fieldErrors[key] = msgs[0];
        });
        setErrors(fieldErrors);
      } else {
        addToast('error', 'Erro', axiosErr.response?.data?.message ?? 'Ocorreu um erro inesperado.');
      }
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await deleteTeacherAssignment(deleteTarget.id);
      addToast('success', 'Atribuição removida com sucesso.');
      setDeleteTarget(null);
      load();
      onDataChange();
    } catch (err) {
      const axiosErr = err as AxiosError<ApiError>;
      addToast('error', 'Erro', axiosErr.response?.data?.message ?? 'Não foi possível remover.');
    } finally {
      setDeleting(false);
    }
  };

  if (!selectedYearId) {
    return <EmptyState icon="👨‍🏫" title="Seleccione um ano lectivo" description="Seleccione um ano lectivo para visualizar as atribuições de docentes." />;
  }

  if (loading && data.length === 0) {
    return <LoadingSkeleton variant="table" rows={5} columns={5} />;
  }

  return (
    <div>
      <div className="nz-table-container">
        <div className="nz-table-toolbar">
          <div />
          {hasPermission('teaching_assignments.create') && (
            <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
              + Nova Atribuição
            </button>
          )}
        </div>

        {data.length === 0 ? (
          <EmptyState
            icon="👨‍🏫"
            title="Nenhuma atribuição encontrada"
            description="Associe docentes a turmas e disciplinas para este ano lectivo."
            action={
              hasPermission('teaching_assignments.create') ? (
                <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
                  + Criar atribuição
                </button>
              ) : undefined
            }
          />
        ) : (
          <>
            <div className="nz-table-scroll">
              <table className="nz-table">
                <thead>
                  <tr>
                    <th>Docente</th>
                    <th>Disciplina</th>
                    <th>Turma</th>
                    <th>Papel</th>
                    <th>Acções</th>
                  </tr>
                </thead>
                <tbody>
                  {data.map((assignment) => (
                    <tr key={assignment.id}>
                      <td style={{ fontWeight: 500 }}>
                        {assignment.teacher?.full_name ?? `Professor #${assignment.teacher_id}`}
                      </td>
                      <td>
                        {assignment.subject?.name ?? `Disciplina #${assignment.subject_id}`}
                      </td>
                      <td>
                        {assignment.school_class?.name ?? `Turma #${assignment.class_id}`}
                      </td>
                      <td>
                        <Badge variant={assignment.role === 'titular' ? 'gold' : 'info'}>
                          {ROLE_LABELS[assignment.role]}
                        </Badge>
                      </td>
                      <td>
                        <div className="nz-table-actions">
                          {hasPermission('teaching_assignments.delete') && (
                            <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => setDeleteTarget(assignment)} title="Remover" type="button">
                              🗑️
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Pagination
              currentPage={meta.current_page}
              totalPages={meta.last_page}
              total={meta.total}
              from={meta.from}
              to={meta.to}
              onPageChange={setPage}
              itemLabel="atribuições"
            />
          </>
        )}
      </div>

      {/* Create Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title="Nova Atribuição de Docente"
        size="lg"
        footer={
          <>
            <button className="nz-btn nz-btn--secondary" onClick={() => setModalOpen(false)} disabled={saving} type="button">Cancelar</button>
            <button className="nz-btn nz-btn--primary" onClick={handleSubmit} disabled={saving} type="button">
              {saving ? 'A guardar...' : 'Criar Atribuição'}
            </button>
          </>
        }
      >
        <form onSubmit={handleSubmit}>
          <FormField label="Docente" htmlFor="assign-teacher" required error={errors.teacher_id}>
            <select
              id="assign-teacher"
              className={`nz-select ${errors.teacher_id ? 'nz-select--error' : ''}`}
              value={form.teacher_id || ''}
              onChange={(e) => setForm({ ...form, teacher_id: parseInt(e.target.value) || 0 })}
              required
            >
              <option value="">Seleccione um docente</option>
              {teachers.map((t) => <option key={t.id} value={t.id}>{t.full_name} ({t.employee_number})</option>)}
            </select>
          </FormField>

          <div className="nz-form-grid">
            <FormField label="Disciplina" htmlFor="assign-subject" required error={errors.subject_id}>
              <select
                id="assign-subject"
                className={`nz-select ${errors.subject_id ? 'nz-select--error' : ''}`}
                value={form.subject_id || ''}
                onChange={(e) => setForm({ ...form, subject_id: parseInt(e.target.value) || 0 })}
                required
              >
                <option value="">Seleccione uma disciplina</option>
                {subjects.map((s) => <option key={s.id} value={s.id}>{s.name} ({s.code})</option>)}
              </select>
            </FormField>

            <FormField label="Turma" htmlFor="assign-class" required error={errors.class_id}>
              <select
                id="assign-class"
                className={`nz-select ${errors.class_id ? 'nz-select--error' : ''}`}
                value={form.class_id || ''}
                onChange={(e) => setForm({ ...form, class_id: parseInt(e.target.value) || 0 })}
                required
              >
                <option value="">Seleccione uma turma</option>
                {classes.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.grade_level})</option>)}
              </select>
            </FormField>
          </div>

          <FormField label="Papel" htmlFor="assign-role" required error={errors.role}>
            <select
              id="assign-role"
              className="nz-select"
              value={form.role}
              onChange={(e) => setForm({ ...form, role: e.target.value as AssignmentRole })}
            >
              <option value="titular">Titular</option>
              <option value="auxiliary">Auxiliar</option>
            </select>
          </FormField>
        </form>
      </Modal>

      <ConfirmDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Remover Atribuição"
        message="Tem certeza que deseja remover esta atribuição de docente? Esta acção é irreversível."
        confirmLabel="Remover"
        variant="danger"
        isLoading={deleting}
      />
    </div>
  );
};
