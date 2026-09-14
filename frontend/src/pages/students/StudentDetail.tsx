import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuthStore, hasPermission } from '@/stores/authStore';
import { getStudent, updateStudent, getStudentGuardians, attachGuardianToStudent, detachGuardianFromStudent, deleteStudent } from '@/api/studentService';
import { PageHeader } from '@/components/ui/PageHeader';
import { Badge } from '@/components/ui/Badge';
import { Modal } from '@/components/ui/Modal';
import { FormField } from '@/components/ui/FormField';
import { ConfirmDialog } from '@/components/ui/ConfirmDialog';
import { useToast } from '@/components/ui/Toast';
import type { Student, Guardian } from '@/types';

export function StudentDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { addToast } = useToast();

  const [student, setStudent] = useState<Student | null>(null);
  const [guardians, setGuardians] = useState<Guardian[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const canEdit = hasPermission('students.update');
  const canDelete = hasPermission('students.delete');

  useEffect(() => {
    if (id) {
      loadData(Number(id));
    }
  }, [id]);

  const loadData = async (studentId: number) => {
    setIsLoading(true);
    try {
      const [studentRes, guardiansRes] = await Promise.all([
        getStudent(studentId),
        getStudentGuardians(studentId).catch(() => []), // Fallback if no permission
      ]);
      setStudent(studentRes.data);
      setGuardians(guardiansRes);
    } catch (err) {
      addToast('Erro ao carregar dados do aluno.', 'error');
      navigate('/students');
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoading) {
    return <div className="p-8 text-center">A carregar detalhes do aluno...</div>;
  }

  if (!student) {
    return <div className="p-8 text-center text-red-500">Aluno não encontrado.</div>;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={student.full_name}
        description={`Nº: ${student.student_number || 'N/D'} | Género: ${student.gender === 'M' ? 'Masculino' : 'Feminino'}`}
        onBack={() => navigate('/students')}
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Dados Pessoais</h3>
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span className="text-gray-500 block">Data de Nascimento</span>
                <span className="text-gray-900 font-medium">
                  {new Date(student.birth_date).toLocaleDateString()}
                </span>
              </div>
              <div>
                <span className="text-gray-500 block">Naturalidade</span>
                <span className="text-gray-900 font-medium">{student.birth_place || 'N/D'}</span>
              </div>
              <div>
                <span className="text-gray-500 block">Nacionalidade</span>
                <span className="text-gray-900 font-medium">{student.nationality || 'N/D'}</span>
              </div>
              <div>
                <span className="text-gray-500 block">Nº de Identificação (BI)</span>
                <span className="text-gray-900 font-medium">{student.bi_number || 'N/D'}</span>
              </div>
              <div className="col-span-2">
                <span className="text-gray-500 block">Morada</span>
                <span className="text-gray-900 font-medium">{student.address || 'N/D'}</span>
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-6">
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Estado e Acções</h3>
            <div className="mb-6">
              <Badge variant={student.is_active ? 'success' : 'danger'}>
                {student.is_active ? 'Activo' : 'Inactivo'}
              </Badge>
            </div>
            
            {canEdit && (
              <button className="w-full mb-3 nz-btn nz-btn--primary">
                Editar Dados
              </button>
            )}
            
            {canDelete && (
              <button className="w-full nz-btn nz-btn--danger nz-btn--ghost">
                Eliminar Aluno
              </button>
            )}
          </div>

          <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-medium text-gray-900">Encarregados</h3>
              {canEdit && (
                <button className="text-nzila-blue text-sm hover:underline font-medium">
                  + Adicionar
                </button>
              )}
            </div>
            
            {guardians.length === 0 ? (
              <p className="text-sm text-gray-500">Nenhum encarregado associado.</p>
            ) : (
              <div className="space-y-4">
                {guardians.map(g => (
                  <div key={g.id} className="border-t border-gray-100 pt-3 first:border-0 first:pt-0">
                    <div className="flex justify-between">
                      <span className="font-medium text-sm text-gray-900">{g.full_name}</span>
                      {g.pivot?.is_primary && <Badge variant="primary">Principal</Badge>}
                    </div>
                    <div className="text-xs text-gray-500 mt-1 capitalize">{g.pivot?.relationship}</div>
                    <div className="text-xs text-gray-600 mt-1">{g.phone}</div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
