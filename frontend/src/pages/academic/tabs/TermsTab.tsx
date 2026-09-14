import React, { useEffect, useState, useCallback } from 'react';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import { hasPermission } from '@/stores/authStore';
import { getTerms, createTerm, updateTerm, deleteTerm } from '@/api/academicService';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { FormField } from '@/components/ui/FormField';
import { useToast } from '@/components/ui/Toast';
import type { Term, TermFormData, TermStatus } from '@/types/academic';
import type { ApiError } from '@/types';
import { AxiosError } from 'axios';

const EMPTY_FORM: TermFormData = { academic_year_id: 0, name: '', start_date: '', end_date: '' };

export const TermsTab: React.FC = () => {
  const { selectedYearId, getSelectedYear } = useAcademicYearStore();
  const { addToast } = useToast();
  const [terms, setTerms] = useState<Term[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingTerm, setEditingTerm] = useState<Term | null>(null);
  const [form, setForm] = useState<TermFormData>(EMPTY_FORM);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Term | null>(null);
  const [deleting, setDeleting] = useState(false);

  const selectedYear = getSelectedYear();

  const load = useCallback(async () => {
    if (!selectedYearId) return;
    setLoading(true);
    try {
      const data = await getTerms(selectedYearId);
      setTerms(data);
    } catch {
      addToast('error', 'Erro', 'Não foi possível carregar os trimestres.');
    } finally {
      setLoading(false);
    }
  }, [selectedYearId, addToast]);

  useEffect(() => { load(); }, [load]);

  const getTermStatus = (term: Term): TermStatus => {
    const now = new Date();
    const start = new Date(term.start_date);
    const end = new Date(term.end_date);
    if (now < start) return 'planned';
    if (now > end) return 'finished';
    return 'active';
  };

  const statusLabels: Record<TermStatus, { label: string; variant: 'success' | 'warning' | 'neutral' }> = {
    active: { label: 'Em Curso', variant: 'success' },
    planned: { label: 'Planeado', variant: 'warning' },
    finished: { label: 'Encerrado', variant: 'neutral' },
  };

  const openCreate = () => {
    setEditingTerm(null);
    setForm({ ...EMPTY_FORM, academic_year_id: selectedYearId ?? 0 });
    setErrors({});
    setModalOpen(true);
  };

  const openEdit = (term: Term) => {
    setEditingTerm(term);
    setForm({
      academic_year_id: term.academic_year_id,
      name: term.name,
      start_date: term.start_date,
      end_date: term.end_date,
    });
    setErrors({});
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setSaving(true);
    try {
      if (editingTerm) {
        await updateTerm(editingTerm.id, form);
        addToast('success', 'Trimestre actualizado com sucesso.');
      } else {
        await createTerm(form);
        addToast('success', 'Trimestre criado com sucesso.');
      }
      setModalOpen(false);
      load();
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
      await deleteTerm(deleteTarget.id);
      addToast('success', 'Trimestre eliminado com sucesso.');
      setDeleteTarget(null);
      load();
    } catch (err) {
      const axiosErr = err as AxiosError<ApiError>;
      addToast('error', 'Erro', axiosErr.response?.data?.message ?? 'Não foi possível eliminar.');
    } finally {
      setDeleting(false);
    }
  };

  if (!selectedYearId) {
    return (
      <EmptyState
        icon="📆"
        title="Seleccione um ano lectivo"
        description="Seleccione um ano lectivo na barra superior para visualizar os trimestres."
      />
    );
  }

  if (loading) {
    return <LoadingSkeleton variant="cards" />;
  }

  return (
    <div>
      {/* Header */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--nz-space-6, 24px)' }}>
        <div>
          <h3 className="nz-headline-sm">Trimestres — {selectedYear?.name ?? ''}</h3>
          <p className="nz-body-sm" style={{ color: 'var(--nz-text-muted)' }}>
            {terms.length} trimestre{terms.length !== 1 ? 's' : ''} configurado{terms.length !== 1 ? 's' : ''}
          </p>
        </div>
        {hasPermission('terms.create') && (
          <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
            + Novo Trimestre
          </button>
        )}
      </div>

      {terms.length === 0 ? (
        <EmptyState
          icon="📆"
          title="Nenhum trimestre configurado"
          description="Adicione trimestres a este ano lectivo para organizar o calendário escolar."
          action={
            hasPermission('terms.create') ? (
              <button className="nz-btn nz-btn--primary" onClick={openCreate} type="button">
                + Adicionar trimestre
              </button>
            ) : undefined
          }
        />
      ) : (
        <div className="nz-term-grid">
          {terms.map((term) => {
            const status = getTermStatus(term);
            const statusInfo = statusLabels[status];
            return (
              <div key={term.id} className={`nz-term-card ${status === 'active' ? 'nz-term-card--active' : ''}`}>
                <div className="nz-term-card__header">
                  <h4 className="nz-term-card__name">{term.name}</h4>
                  <Badge variant={statusInfo.variant}>{statusInfo.label}</Badge>
                </div>
                <div className="nz-term-card__dates">
                  <span>📅 Início: {formatDate(term.start_date)}</span>
                  <span>📅 Fim: {formatDate(term.end_date)}</span>
                </div>
                <div className="nz-term-card__actions">
                  {hasPermission('terms.update') && (
                    <button className="nz-btn nz-btn--secondary nz-btn--sm" onClick={() => openEdit(term)} type="button">
                      ✏️ Editar
                    </button>
                  )}
                  {hasPermission('terms.delete') && (
                    <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => setDeleteTarget(term)} type="button">
                      🗑️ Eliminar
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Create/Edit Modal */}
      <Modal
        isOpen={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editingTerm ? 'Editar Trimestre' : 'Novo Trimestre'}
        footer={
          <>
            <button className="nz-btn nz-btn--secondary" onClick={() => setModalOpen(false)} disabled={saving} type="button">
              Cancelar
            </button>
            <button className="nz-btn nz-btn--primary" onClick={handleSubmit} disabled={saving} type="button">
              {saving ? 'A guardar...' : editingTerm ? 'Guardar' : 'Criar Trimestre'}
            </button>
          </>
        }
      >
        <form onSubmit={handleSubmit}>
          <FormField label="Nome" htmlFor="term-name" required error={errors.name}>
            <input
              id="term-name"
              className={`nz-input ${errors.name ? 'nz-input--error' : ''}`}
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              placeholder="Ex: 1.º Trimestre"
              required
            />
          </FormField>
          <div className="nz-form-grid">
            <FormField label="Data de Início" htmlFor="term-start" required error={errors.start_date}>
              <input
                id="term-start"
                type="date"
                className={`nz-input ${errors.start_date ? 'nz-input--error' : ''}`}
                value={form.start_date}
                onChange={(e) => setForm({ ...form, start_date: e.target.value })}
                required
              />
            </FormField>
            <FormField label="Data de Fim" htmlFor="term-end" required error={errors.end_date}>
              <input
                id="term-end"
                type="date"
                className={`nz-input ${errors.end_date ? 'nz-input--error' : ''}`}
                value={form.end_date}
                onChange={(e) => setForm({ ...form, end_date: e.target.value })}
                required
              />
            </FormField>
          </div>
        </form>
      </Modal>

      <ConfirmDialog
        isOpen={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Eliminar Trimestre"
        message={`Tem certeza que deseja eliminar "${deleteTarget?.name}"? Esta acção é irreversível.`}
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
