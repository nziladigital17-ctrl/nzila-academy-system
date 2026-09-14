import React, { useEffect, useState, useCallback } from 'react';
import { hasPermission } from '@/stores/authStore';
import { getSubjects, createSubject, updateSubject, deleteSubject } from '@/api/academicService';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { SearchInput } from '@/components/ui/SearchInput';
import { FormField } from '@/components/ui/FormField';
import { useToast } from '@/components/ui/Toast';
import type { Subject, SubjectFormData } from '@/types/academic';
import type { ApiError } from '@/types';
import { AxiosError } from 'axios';

interface SubjectsTabProps {
  onDataChange: () => void;
}

const EMPTY_FORM: SubjectFormData = { name: '', code: '', description: '', weekly_hours: 0, is_active: true };

export const SubjectsTab: React.FC<SubjectsTabProps> = ({ onDataChange }) => {
  const { addToast } = useToast();
  const [data, setData] = useState<Subject[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [meta, setMeta] = useState({ total: 0, from: 0, to: 0, last_page: 1, current_page: 1, per_page: 15 });
  const [modalOpen, setModalOpen] = useState(false);
  const [editingSubject, setEditingSubject] = useState<Subject | null>(null);
  const [form, setForm] = useState<SubjectFormData>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Subject | null>(null);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params: Record<string, string | number> = { page };
      if (search) params.search = search;
      if (statusFilter) params.status = statusFilter;
      const res = await getSubjects(params);
      setData(res.data);
      setMeta(res.meta);
    } catch {
      addToast('error', 'Erro', 'Não foi possível carregar as disciplinas.');
    } finally {
      setLoading(false);
    }
  }, [page, search, statusFilter, addToast]);

  useEffect(() => { load(); }, [load]);
  useEffect(() => { setPage(1); }, [search, statusFilter]);

  const openCreate = () => {
    setEditingSubject(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setModalOpen(true);
  };

  const openEdit = (subject: Subject) => {
    setEditingSubject(subject);
    setForm({
      name: subject.name,
      code: subject.code,
      description: subject.description ?? '',
      weekly_hours: subject.weekly_hours,
      is_active: subject.is_active,
    });
    setErrors({});
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setSaving(true);
    try {
      if (editingSubject) {
        await updateSubject(editingSubject.id, form);
        addToast('success', 'Disciplina actualizada com sucesso.');
      } else {
        await createSubject(form);
        addToast('success', 'Disciplina criada com sucesso.');
      }
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
      await deleteSubject(deleteTarget.id);
      addToast('success', 'Disciplina eliminada com sucesso.');
      setDeleteTarget(null);
      load();
      onDataChange();
    } catch (err) {
      const axiosErr = err as AxiosError<ApiError>;
      addToast('error', 'Erro', axiosErr.response?.data?.message ?? 'Não foi possível eliminar.');
    } finally {
      setDeleting(false);
    }
  };

  if (loading && data.length === 0 && !search && !statusFilter) {
    return <LoadingSkeleton variant="table" rows={6} columns={6} />;
  }

  return (
    <div>
      <div className="nz-table-container">
        <div className="nz-table-toolbar">
          <div className="nz-table-toolbar__filters">
            <SearchInput value={search} onChange={setSearch} placeholder="Pesquisar disciplina..." />
            <select
              className="nz-select"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              style={{ width: 'auto', minWidth: '140px' }}
              aria-label="Filtrar por estado"
            >
              <option value="">Todos os estados</option>
              <option value="active">Activas</option>
              <option value="inactive">Inactivas</option>
            </select>
          </div>
          {hasPermission('subjects.create') && (
            <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
              + Nova Disciplina
            </button>
          )}
        </div>

        {data.length === 0 ? (
          <EmptyState
            icon="📖"
            title="Nenhuma disciplina encontrada"
            description={search ? 'Tente alterar os termos de pesquisa.' : 'Crie a primeira disciplina para começar.'}
            action={
              !search && hasPermission('subjects.create') ? (
                <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
                  + Adicionar disciplina
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
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Horas/Semana</th>
                    <th>Estado</th>
                    <th>Acções</th>
                  </tr>
                </thead>
                <tbody>
                  {data.map((subject) => (
                    <tr key={subject.id}>
                      <td><code style={{ fontSize: '13px', background: 'var(--nz-bg)', padding: '2px 6px', borderRadius: '4px' }}>{subject.code}</code></td>
                      <td style={{ fontWeight: 500 }}>{subject.name}</td>
                      <td>{subject.weekly_hours}h</td>
                      <td>
                        {subject.is_active ? (
                          <Badge variant="success">Activa</Badge>
                        ) : (
                          <Badge variant="neutral">Inactiva</Badge>
                        )}
                      </td>
                      <td>
                        <div className="nz-table-actions">
                          {hasPermission('subjects.update') && (
                            <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => openEdit(subject)} title="Editar" type="button">
                              ✏️
                            </button>
                          )}
                          {hasPermission('subjects.delete') && (
                            <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => setDeleteTarget(subject)} title="Eliminar" type="button">
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
              itemLabel="disciplinas"
            />
          </>
        )}
      </div>

      {/* Create/Edit Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingSubject ? 'Editar Disciplina' : 'Nova Disciplina'}
        footer={
          <>
            <button className="nz-btn nz-btn--secondary" onClick={() => setModalOpen(false)} disabled={saving} type="button">Cancelar</button>
            <button className="nz-btn nz-btn--primary" onClick={handleSubmit} disabled={saving} type="button">
              {saving ? 'A guardar...' : editingSubject ? 'Guardar' : 'Criar Disciplina'}
            </button>
          </>
        }
      >
        <form onSubmit={handleSubmit}>
          <div className="nz-form-grid">
            <FormField label="Nome" htmlFor="subj-name" required error={errors.name}>
              <input id="subj-name" className={`nz-input ${errors.name ? 'nz-input--error' : ''}`} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
            </FormField>
            <FormField label="Código" htmlFor="subj-code" required error={errors.code}>
              <input id="subj-code" className={`nz-input ${errors.code ? 'nz-input--error' : ''}`} value={form.code} onChange={(e) => setForm({ ...form, code: e.target.value })} placeholder="Ex: MAT" required />
            </FormField>
          </div>
          <FormField label="Descrição" htmlFor="subj-desc" error={errors.description}>
            <textarea id="subj-desc" className="nz-textarea" value={form.description ?? ''} onChange={(e) => setForm({ ...form, description: e.target.value })} rows={3} />
          </FormField>
          <div className="nz-form-grid">
            <FormField label="Horas por Semana" htmlFor="subj-hours" error={errors.weekly_hours}>
              <input id="subj-hours" type="number" className="nz-input" value={form.weekly_hours ?? 0} onChange={(e) => setForm({ ...form, weekly_hours: parseInt(e.target.value) || 0 })} min={0} max={40} />
            </FormField>
            <FormField label="Estado" htmlFor="subj-active">
              <select id="subj-active" className="nz-select" value={form.is_active ? 'true' : 'false'} onChange={(e) => setForm({ ...form, is_active: e.target.value === 'true' })}>
                <option value="true">Activa</option>
                <option value="false">Inactiva</option>
              </select>
            </FormField>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Eliminar Disciplina"
        message={`Tem certeza que deseja eliminar "${deleteTarget?.name}" (${deleteTarget?.code})? Esta acção é irreversível.`}
        confirmLabel="Confirmar Eliminação"
        variant="danger"
        isLoading={deleting}
      />
    </div>
  );
};
