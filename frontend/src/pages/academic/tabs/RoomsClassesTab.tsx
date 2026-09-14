import React, { useEffect, useState, useCallback } from 'react';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import { hasPermission } from '@/stores/authStore';
import { getRooms, createRoom, updateRoom, deleteRoom, getClasses, createClass, updateClass, deleteClass } from '@/api/academicService';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';
import { LoadingSkeleton } from '@/components/ui/LoadingSkeleton';
import { SearchInput } from '@/components/ui/SearchInput';
import { FormField } from '@/components/ui/FormField';
import { Badge } from '@/components/ui/Badge';
import { useToast } from '@/components/ui/Toast';
import type { Room, RoomFormData, SchoolClass, SchoolClassFormData, ShiftType } from '@/types/academic';
import type { ApiError } from '@/types';
import { AxiosError } from 'axios';

interface RoomsClassesTabProps {
  onDataChange: () => void;
}

const SHIFT_LABELS: Record<ShiftType, string> = { morning: 'Manhã', afternoon: 'Tarde', evening: 'Noite' };
const EMPTY_ROOM: RoomFormData = { name: '', capacity: null, building: '' };
const EMPTY_CLASS: SchoolClassFormData = { academic_year_id: 0, name: '', grade_level: '', shift: 'morning', max_students: 35 };

export const RoomsClassesTab: React.FC<RoomsClassesTabProps> = ({ onDataChange }) => {
  const { selectedYearId } = useAcademicYearStore();
  const { addToast } = useToast();

  // ── Rooms state ──
  const [rooms, setRooms] = useState<Room[]>([]);
  const [roomsLoading, setRoomsLoading] = useState(true);
  const [roomPage, setRoomPage] = useState(1);
  const [roomSearch, setRoomSearch] = useState('');
  const [roomMeta, setRoomMeta] = useState({ total: 0, from: 0, to: 0, last_page: 1, current_page: 1, per_page: 15 });
  const [roomModalOpen, setRoomModalOpen] = useState(false);
  const [editingRoom, setEditingRoom] = useState<Room | null>(null);
  const [roomForm, setRoomForm] = useState<RoomFormData>(EMPTY_ROOM);
  const [roomErrors, setRoomErrors] = useState<Record<string, string>>({});
  const [roomSaving, setRoomSaving] = useState(false);
  const [roomDeleteTarget, setRoomDeleteTarget] = useState<Room | null>(null);
  const [roomDeleting, setRoomDeleting] = useState(false);

  // ── Classes state ──
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [classesLoading, setClassesLoading] = useState(true);
  const [classPage, setClassPage] = useState(1);
  const [classSearch, setClassSearch] = useState('');
  const [classMeta, setClassMeta] = useState({ total: 0, from: 0, to: 0, last_page: 1, current_page: 1, per_page: 15 });
  const [classModalOpen, setClassModalOpen] = useState(false);
  const [editingClass, setEditingClass] = useState<SchoolClass | null>(null);
  const [classForm, setClassForm] = useState<SchoolClassFormData>(EMPTY_CLASS);
  const [classErrors, setClassErrors] = useState<Record<string, string>>({});
  const [classSaving, setClassSaving] = useState(false);
  const [classDeleteTarget, setClassDeleteTarget] = useState<SchoolClass | null>(null);
  const [classDeleting, setClassDeleting] = useState(false);

  const loadRooms = useCallback(async () => {
    setRoomsLoading(true);
    try {
      const params: Record<string, string | number> = { page: roomPage };
      if (roomSearch) params.search = roomSearch;
      const res = await getRooms(params);
      setRooms(res.data);
      setRoomMeta(res.meta);
    } catch { addToast('error', 'Erro', 'Não foi possível carregar as salas.'); }
    finally { setRoomsLoading(false); }
  }, [roomPage, roomSearch, addToast]);

  const loadClasses = useCallback(async () => {
    if (!selectedYearId) return;
    setClassesLoading(true);
    try {
      const params: Record<string, string | number> = { page: classPage, academic_year_id: selectedYearId };
      if (classSearch) params.search = classSearch;
      const res = await getClasses(params);
      setClasses(res.data);
      setClassMeta(res.meta);
    } catch { addToast('error', 'Erro', 'Não foi possível carregar as turmas.'); }
    finally { setClassesLoading(false); }
  }, [classPage, classSearch, selectedYearId, addToast]);

  useEffect(() => { loadRooms(); }, [loadRooms]);
  useEffect(() => { loadClasses(); }, [loadClasses]);
  useEffect(() => { setRoomPage(1); }, [roomSearch]);
  useEffect(() => { setClassPage(1); }, [classSearch]);

  // ── Room handlers ──
  const openRoomCreate = () => { setEditingRoom(null); setRoomForm(EMPTY_ROOM); setRoomErrors({}); setRoomModalOpen(true); };
  const openRoomEdit = (room: Room) => { setEditingRoom(room); setRoomForm({ name: room.name, capacity: room.capacity, building: room.building ?? '' }); setRoomErrors({}); setRoomModalOpen(true); };

  const handleRoomSubmit = async (e: React.FormEvent) => {
    e.preventDefault(); setRoomErrors({}); setRoomSaving(true);
    try {
      if (editingRoom) { await updateRoom(editingRoom.id, roomForm); addToast('success', 'Sala actualizada.'); }
      else { await createRoom(roomForm); addToast('success', 'Sala criada.'); }
      setRoomModalOpen(false); loadRooms(); onDataChange();
    } catch (err) {
      const ax = err as AxiosError<ApiError>;
      if (ax.response?.status === 422 && ax.response.data.errors) {
        const fe: Record<string, string> = {};
        Object.entries(ax.response.data.errors).forEach(([k, v]) => { fe[k] = v[0]; });
        setRoomErrors(fe);
      } else { addToast('error', 'Erro', ax.response?.data?.message ?? 'Erro inesperado.'); }
    } finally { setRoomSaving(false); }
  };

  const handleRoomDelete = async () => {
    if (!roomDeleteTarget) return; setRoomDeleting(true);
    try { await deleteRoom(roomDeleteTarget.id); addToast('success', 'Sala eliminada.'); setRoomDeleteTarget(null); loadRooms(); onDataChange(); }
    catch (err) { const ax = err as AxiosError<ApiError>; addToast('error', 'Erro', ax.response?.data?.message ?? 'Não foi possível eliminar.'); }
    finally { setRoomDeleting(false); }
  };

  // ── Class handlers ──
  const openClassCreate = () => { setEditingClass(null); setClassForm({ ...EMPTY_CLASS, academic_year_id: selectedYearId ?? 0 }); setClassErrors({}); setClassModalOpen(true); };
  const openClassEdit = (cls: SchoolClass) => {
    setEditingClass(cls);
    setClassForm({ academic_year_id: cls.academic_year_id, name: cls.name, grade_level: cls.grade_level, shift: cls.shift, max_students: cls.max_students, room_id: cls.room_id, subject_id: cls.subject_id });
    setClassErrors({}); setClassModalOpen(true);
  };

  const handleClassSubmit = async (e: React.FormEvent) => {
    e.preventDefault(); setClassErrors({}); setClassSaving(true);
    try {
      if (editingClass) { await updateClass(editingClass.id, classForm); addToast('success', 'Turma actualizada.'); }
      else { await createClass(classForm); addToast('success', 'Turma criada.'); }
      setClassModalOpen(false); loadClasses(); onDataChange();
    } catch (err) {
      const ax = err as AxiosError<ApiError>;
      if (ax.response?.status === 422 && ax.response.data.errors) {
        const fe: Record<string, string> = {};
        Object.entries(ax.response.data.errors).forEach(([k, v]) => { fe[k] = v[0]; });
        setClassErrors(fe);
      } else { addToast('error', 'Erro', ax.response?.data?.message ?? 'Erro inesperado.'); }
    } finally { setClassSaving(false); }
  };

  const handleClassDelete = async () => {
    if (!classDeleteTarget) return; setClassDeleting(true);
    try { await deleteClass(classDeleteTarget.id); addToast('success', 'Turma eliminada.'); setClassDeleteTarget(null); loadClasses(); onDataChange(); }
    catch (err) { const ax = err as AxiosError<ApiError>; addToast('error', 'Erro', ax.response?.data?.message ?? 'Não foi possível eliminar.'); }
    finally { setClassDeleting(false); }
  };

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nz-space-8, 32px)' }}>
      {/* ── SALAS ── */}
      {hasPermission('rooms.view') && (
        <section>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--nz-space-4, 16px)' }}>
            <h3 className="nz-headline-sm">Salas</h3>
            {hasPermission('rooms.create') && (
              <button className="nz-btn nz-btn--primary nz-btn--sm" onClick={openRoomCreate} type="button">+ Nova Sala</button>
            )}
          </div>
          {roomsLoading && rooms.length === 0 ? (
            <LoadingSkeleton variant="table" rows={3} columns={4} />
          ) : rooms.length === 0 ? (
            <EmptyState icon="🏫" title="Nenhuma sala registada" description="Adicione salas para poder atribuí-las a turmas." />
          ) : (
            <div className="nz-table-container">
              <div className="nz-table-toolbar">
                <SearchInput value={roomSearch} onChange={setRoomSearch} placeholder="Pesquisar sala..." />
              </div>
              <div className="nz-table-scroll">
                <table className="nz-table">
                  <thead><tr><th>Nome</th><th>Capacidade</th><th>Edifício</th><th>Acções</th></tr></thead>
                  <tbody>
                    {rooms.map((room) => (
                      <tr key={room.id}>
                        <td style={{ fontWeight: 500 }}>{room.name}</td>
                        <td>{room.capacity ?? '—'}</td>
                        <td>{room.building ?? '—'}</td>
                        <td>
                          <div className="nz-table-actions">
                            {hasPermission('rooms.update') && <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => openRoomEdit(room)} type="button">✏️</button>}
                            {hasPermission('rooms.delete') && <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => setRoomDeleteTarget(room)} type="button">🗑️</button>}
                          </div>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <Pagination currentPage={roomMeta.current_page} totalPages={roomMeta.last_page} total={roomMeta.total} from={roomMeta.from} to={roomMeta.to} onPageChange={setRoomPage} itemLabel="salas" />
            </div>
          )}
        </section>
      )}

      {/* ── TURMAS ── */}
      {hasPermission('classes.view') && (
        <section>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 'var(--nz-space-4, 16px)' }}>
            <h3 className="nz-headline-sm">Turmas</h3>
            {hasPermission('classes.create') && (
              <button className="nz-btn nz-btn--primary nz-btn--sm" onClick={openClassCreate} type="button">+ Nova Turma</button>
            )}
          </div>
          {classesLoading && classes.length === 0 ? (
            <LoadingSkeleton variant="table" rows={5} columns={6} />
          ) : classes.length === 0 ? (
            <EmptyState icon="🏛️" title="Nenhuma turma encontrada" description="Crie turmas para este ano lectivo." />
          ) : (
            <div className="nz-table-container">
              <div className="nz-table-toolbar">
                <SearchInput value={classSearch} onChange={setClassSearch} placeholder="Pesquisar turma..." />
              </div>
              <div className="nz-table-scroll">
                <table className="nz-table">
                  <thead><tr><th>Nome</th><th>Classe</th><th>Turno</th><th>Sala</th><th>Capacidade</th><th>Acções</th></tr></thead>
                  <tbody>
                    {classes.map((cls) => {
                      const room = rooms.find((r) => r.id === cls.room_id);
                      return (
                        <tr key={cls.id}>
                          <td style={{ fontWeight: 500 }}>{cls.name}</td>
                          <td>{cls.grade_level}</td>
                          <td><Badge variant="info">{SHIFT_LABELS[cls.shift]}</Badge></td>
                          <td>{room?.name ?? '—'}</td>
                          <td>{cls.max_students}</td>
                          <td>
                            <div className="nz-table-actions">
                              {hasPermission('classes.update') && <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => openClassEdit(cls)} type="button">✏️</button>}
                              {hasPermission('classes.delete') && <button className="nz-btn nz-btn--ghost nz-btn--sm" onClick={() => setClassDeleteTarget(cls)} type="button">🗑️</button>}
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              <Pagination currentPage={classMeta.current_page} totalPages={classMeta.last_page} total={classMeta.total} from={classMeta.from} to={classMeta.to} onPageChange={setClassPage} itemLabel="turmas" />
            </div>
          )}
        </section>
      )}

      {/* ── Room Modal ── */}
      <Modal isOpen={roomModalOpen} onClose={() => setRoomModalOpen(false)} title={editingRoom ? 'Editar Sala' : 'Nova Sala'} footer={
        <><button className="nz-btn nz-btn--secondary" onClick={() => setRoomModalOpen(false)} disabled={roomSaving} type="button">Cancelar</button>
        <button className="nz-btn nz-btn--primary" onClick={handleRoomSubmit} disabled={roomSaving} type="button">{roomSaving ? 'A guardar...' : 'Guardar'}</button></>
      }>
        <form onSubmit={handleRoomSubmit}>
          <FormField label="Nome" htmlFor="room-name" required error={roomErrors.name}>
            <input id="room-name" className={`nz-input ${roomErrors.name ? 'nz-input--error' : ''}`} value={roomForm.name} onChange={(e) => setRoomForm({ ...roomForm, name: e.target.value })} required />
          </FormField>
          <div className="nz-form-grid">
            <FormField label="Capacidade" htmlFor="room-cap" error={roomErrors.capacity}>
              <input id="room-cap" type="number" className="nz-input" value={roomForm.capacity ?? ''} onChange={(e) => setRoomForm({ ...roomForm, capacity: e.target.value ? parseInt(e.target.value) : null })} min={1} />
            </FormField>
            <FormField label="Edifício" htmlFor="room-building" error={roomErrors.building}>
              <input id="room-building" className="nz-input" value={roomForm.building ?? ''} onChange={(e) => setRoomForm({ ...roomForm, building: e.target.value })} />
            </FormField>
          </div>
        </form>
      </Modal>

      {/* ── Class Modal ── */}
      <Modal isOpen={classModalOpen} onClose={() => setClassModalOpen(false)} title={editingClass ? 'Editar Turma' : 'Nova Turma'} footer={
        <><button className="nz-btn nz-btn--secondary" onClick={() => setClassModalOpen(false)} disabled={classSaving} type="button">Cancelar</button>
        <button className="nz-btn nz-btn--primary" onClick={handleClassSubmit} disabled={classSaving} type="button">{classSaving ? 'A guardar...' : 'Guardar'}</button></>
      }>
        <form onSubmit={handleClassSubmit}>
          <div className="nz-form-grid">
            <FormField label="Nome" htmlFor="cls-name" required error={classErrors.name}>
              <input id="cls-name" className={`nz-input ${classErrors.name ? 'nz-input--error' : ''}`} value={classForm.name} onChange={(e) => setClassForm({ ...classForm, name: e.target.value })} placeholder="Ex: 10.ª A" required />
            </FormField>
            <FormField label="Classe" htmlFor="cls-grade" required error={classErrors.grade_level}>
              <input id="cls-grade" className={`nz-input ${classErrors.grade_level ? 'nz-input--error' : ''}`} value={classForm.grade_level} onChange={(e) => setClassForm({ ...classForm, grade_level: e.target.value })} placeholder="Ex: 10.ª Classe" required />
            </FormField>
          </div>
          <div className="nz-form-grid">
            <FormField label="Turno" htmlFor="cls-shift" required error={classErrors.shift}>
              <select id="cls-shift" className="nz-select" value={classForm.shift} onChange={(e) => setClassForm({ ...classForm, shift: e.target.value as ShiftType })}>
                <option value="morning">Manhã</option>
                <option value="afternoon">Tarde</option>
                <option value="evening">Noite</option>
              </select>
            </FormField>
            <FormField label="Capacidade Máxima" htmlFor="cls-max" error={classErrors.max_students}>
              <input id="cls-max" type="number" className="nz-input" value={classForm.max_students ?? 35} onChange={(e) => setClassForm({ ...classForm, max_students: parseInt(e.target.value) || 35 })} min={1} max={100} />
            </FormField>
          </div>
          <FormField label="Sala" htmlFor="cls-room" error={classErrors.room_id}>
            <select id="cls-room" className="nz-select" value={classForm.room_id ?? ''} onChange={(e) => setClassForm({ ...classForm, room_id: e.target.value ? parseInt(e.target.value) : null })}>
              <option value="">— Sem sala —</option>
              {rooms.map((r) => <option key={r.id} value={r.id}>{r.name} {r.capacity ? `(${r.capacity} lug.)` : ''}</option>)}
            </select>
          </FormField>
        </form>
      </Modal>

      {/* Delete confirmations */}
      <ConfirmDialog isOpen={!!roomDeleteTarget} onClose={() => setRoomDeleteTarget(null)} onConfirm={handleRoomDelete} title="Eliminar Sala" message={`Eliminar "${roomDeleteTarget?.name}"? Só é possível se não houver turmas associadas.`} confirmLabel="Eliminar" variant="danger" isLoading={roomDeleting} />
      <ConfirmDialog isOpen={!!classDeleteTarget} onClose={() => setClassDeleteTarget(null)} onConfirm={handleClassDelete} title="Eliminar Turma" message={`Eliminar "${classDeleteTarget?.name}"? Só é possível se não houver matrículas ou professores associados.`} confirmLabel="Eliminar" variant="danger" isLoading={classDeleting} />
    </div>
  );
};
