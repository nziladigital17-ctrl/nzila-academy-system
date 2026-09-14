import React, { useEffect, useState, useCallback } from 'react';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import { hasPermission } from '@/stores/authStore';
import { getAcademicYears, createAcademicYear, updateAcademicYear, deleteAcademicYear } from '@/api/academicService';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { FormField } from '@/components/ui/FormField';
import { useToast } from '@/components/ui/Toast';
import type { AcademicYear, AcademicYearFormData } from '@/types/academic';
import type { ApiError } from '@/types';
import { AxiosError } from 'axios';

interface AcademicYearsTabProps {
  onDataChange: () => void;
}

const EMPTY_FORM: AcademicYearFormData = { name: '', start_date: '', end_date: '', is_current: false };

export const AcademicYearsTab: React.FC<AcademicYearsTabProps> = ({ onDataChange }) => {
  const { fetchYears } = useAcademicYearStore();
  const { addToast } = useToast();
  const [data, setData] = useState<AcademicYear[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({ total: 0, from: 0, to: 0, last_page: 1, current_page: 1, per_page: 15 });
  const [modalOpen, setModalOpen] = useState(false);
  const [editingYear, setEditingYear] = useState<AcademicYear | null>(null);
  const [form, setForm] = useState<AcademicYearFormData>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<AcademicYear | null>(null);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getAcademicYears({ page });
      setData(res.data);
      setMeta(res.meta);
    } catch {
      addToast('error', 'Erro', 'Não foi possível carregar os anos lectivos.');
    } finally {
      setLoading(false);
    }
  }, [page, addToast]);

  useEffect(() => { load(); }, [load]);

  const openCreate = () => {
    setEditingYear(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setModalOpen(true);
  };

  const openEdit = (year: AcademicYear) => {
    setEditingYear(year);
    setForm({
      name: year.name,
      start_date: year.start_date,
      end_date: year.end_date,
      is_current: year.is_current,
    });
    setErrors({});
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setSaving(true);
    try {
      if (editingYear) {
        await updateAcademicYear(editingYear.id, form);
        addToast('success', 'Ano lectivo actualizado com sucesso.');
      } else {
        await createAcademicYear(form);
        addToast('success', 'Ano lectivo criado com sucesso.');
      }
      setModalOpen(false);
      load();
      fetchYears();
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
      await deleteAcademicYear(deleteTarget.id);
      addToast('success', 'Ano lectivo eliminado com sucesso.');
      setDeleteTarget(null);
      load();
      fetchYears();
      onDataChange();
    } catch (err) {
      const axiosErr = err as AxiosError<ApiError>;
      addToast('error', 'Erro', axiosErr.response?.data?.message ?? 'Não foi possível eliminar.');
    } finally {
      setDeleting(false);
    }
  };

  const handleToggleCurrent = async (year: AcademicYear) => {
    try {
      await updateAcademicYear(year.id, { is_current: !year.is_current });
      addToast('success', year.is_current ? 'Ano lectivo desactivado.' : 'Ano lectivo definido como actual.');
      load();
      fetchYears();
    } catch {
      addToast('error', 'Erro', 'Não foi possível alterar o estado.');
    }
  };

  if (loading && data.length === 0) {
    return <LoadingSkeleton variant="table" rows={5} columns={5} />;
  }

  return (
    <div>
      {/* Toolbar */}
      <div className="nz-table-toolbar" style={{ background: 'transparent', border: 'none', padding: '0 0 var(--nz-space-4, 16px) 0' }}>
        <div />
        {hasPermission('academic_years.create') && (
          <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
            + Novo Ano Lectivo
          </button>
        )}
      </div>

      {data.length === 0 ? (
        <EmptyState
          icon="📅"
          title="Nenhum ano lectivo encontrado"
          description="Crie o primeiro ano lectivo para começar a configurar a estrutura académica."
          action={
            hasPermission('academic_years.create') ? (
              <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
                + Adicionar novo registo
              </button>
            ) : undefined
          }
        />
      ) : (
        <div className="nz-table-container">
          <div className="nz-table-scroll">
            <table className="nz-table">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Data Início</th>
                  <th>Data Fim</th>
                  <th>Estado</th>
                  <th>Acções</th>
                </tr>
              </thead>
              <tbody>
                {data.map((year) => (
                  <tr key={year.id}>
                    <td style={{ fontWeight: 600 }}>{year.name}</td>
                    <td>{formatDate(year.start_date)}</td>
                    <td>{formatDate(year.end_date)}</td>
                    <td>
                      {year.is_current ? (
                        <Badge variant="success">Em curso</Badge>
                      ) : (
                        <Badge variant="neutral">Inactivo</Badge>
                      )}
                    </td>
                    <td>
                      <div className="nz-table-actions">
                        {hasPermission('academic_years.update') && (
                          <>
                            <button
                              className="nz-btn nz-btn--ghost nz-btn--sm"
                              onClick={() => openEdit(year)}
                              title="Editar"
                              type="button"
                            >
                              ✏️
                            </button>
                            <button
                              className="nz-btn nz-btn--ghost nz-btn--sm"
                              onClick={() => handleToggleCurrent(year)}
                              title={year.is_current ? 'Desactivar' : 'Definir como actual'}
                              type="button"
                            >
                              {year.is_current ? '⏸️' : '▶️'}
                            </button>
                          </>
                        )}
                        {hasPermission('academic_years.delete') && (
                          <button
                            className="nz-btn nz-btn--ghost nz-btn--sm"
                            onClick={() => setDeleteTarget(year)}
                            title="Eliminar"
                            type="button"
                          >
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
            itemLabel="anos lectivos"
          />
        </div>
      )}

      {/* Create/Edit Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingYear ? 'Editar Ano Lectivo' : 'Novo Ano Lectivo'}
        footer={
          <>
            <button className="nz-btn nz-btn--secondary" onClick={() => setModalOpen(false)} disabled={saving} type="button">
              Cancelar
            </button>
            <button className="nz-btn nz-btn--primary" onClick={handleSubmit} disabled={saving} type="button">
              {saving ? 'A guardar...' : editingYear ? 'Guardar Alterações' : 'Criar Ano Lectivo'}
            </button>
          </>
        }
      >
        <form onSubmit={handleSubmit}>
          <FormField label="Nome" htmlFor="year-name" required error={errors.name}>
            <input
              id="year-name"
              className={`nz-input ${errors.name ? 'nz-input--error' : ''}`}
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              placeholder="Ex: 2026"
              required
            />
          </FormField>
          <div className="nz-form-grid">
            <FormField label="Data de Início" htmlFor="year-start" required error={errors.start_date}>
              <input
                id="year-start"
                type="date"
                className={`nz-input ${errors.start_date ? 'nz-input--error' : ''}`}
                value={form.start_date}
                onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                required
              />
            </FormField>
            <FormField label="Data de Fim" htmlFor="year-end" required error={errors.end_date}>
              <input
                id="year-end"
                type="date"
                className={`nz-input ${errors.end_date ? 'nz-input--error' : ''}`}
                value={form.end_date}
                onChange={(e) => setForm({ ...form, end_date: e.target.value })}
                required
              />
            </FormField>
          </div>
          <FormField label="" htmlFor="year-current">
            <label style={{ display: 'flex', alignItems: 'center', gap: '8px', cursor: 'pointer' }}>
              <input
                id="year-current"
                type="checkbox"
                checked={form.is_current ?? false}
                onChange={(e) => setForm({ ...form, is_current: e.target.checked })}
              />
              <span className="nz-body-md">Marcar como ano lectivo em curso</span>
            </label>
          </FormField>
        </form>
      </Modal>

      {/* Delete Confirmation */}
      <ConfirmDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Eliminar Ano Lectivo"
        message={`Esta acção irá remover permanentemente o ano lectivo "${deleteTarget?.name}". Esta acção é irreversível e só é possível se não existirem trimestres ou turmas associadas.`}
        confirmLabel="Confirmar Eliminação"
        variant="danger"
        isLoading={deleting}
      />
    </div>
  );
};

function formatDate(dateStr: string): string {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('pt-AO', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
