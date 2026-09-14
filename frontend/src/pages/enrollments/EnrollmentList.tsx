import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useEnrollmentStore } from '@/stores/enrollmentStore';
import { useAuthStore, hasPermission } from '@/stores/authStore';
import { PageHeader } from '@/components/ui/PageHeader';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';

export function EnrollmentList() {
  const navigate = useNavigate();
  const { enrollments, pagination, isLoading, filters, setFilters, fetchEnrollments } = useEnrollmentStore();
  const canCreate = hasPermission('enrollments.create');

  useEffect(() => {
    fetchEnrollments(1);
  }, [fetchEnrollments]);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Matrículas"
        description="Gestão de matrículas de alunos"
        actionLabel={canCreate ? "+ Nova Matrícula" : undefined}
        onAction={canCreate ? () => navigate('/enrollments/new') : undefined}
      />

      <div className="flex gap-4 mb-4">
        <select
          className="form-select border border-gray-300 rounded-md px-3 py-2 text-sm"
          value={filters.status || ''}
          onChange={(e) => setFilters({ status: e.target.value || undefined })}
        >
          <option value="">Todos os Estados</option>
          <option value="active">Activa</option>
          <option value="cancelled">Cancelada</option>
          <option value="completed">Concluída</option>
        </select>
        {/* Additional filters like Class or Academic Year can be added here */}
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">A carregar matrículas...</div>
        ) : enrollments.length === 0 ? (
          <EmptyState
            title="Nenhuma matrícula encontrada"
            description="Tente ajustar os filtros de pesquisa."
          />
        ) : (
          <>
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Aluno
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Turma
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Data Matrícula
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {enrollments.map((enrollment) => (
                  <tr key={enrollment.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {enrollment.student?.full_name || `ID: ${enrollment.student_id}`}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {enrollment.schoolClass?.name || `ID: ${enrollment.class_id}`}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {new Date(enrollment.enrollment_date || enrollment.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <Badge
                        variant={
                          enrollment.status === 'active' ? 'success' :
                          enrollment.status === 'cancelled' ? 'danger' : 'warning'
                        }
                      >
                        {enrollment.status === 'active' ? 'Activa' : 
                         enrollment.status === 'cancelled' ? 'Cancelada' : 'Concluída'}
                      </Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {pagination && pagination.last_page > 1 && (
              <div className="px-6 py-3 border-t border-gray-200 bg-gray-50">
                <Pagination
                  currentPage={pagination.current_page}
                  totalPages={pagination.last_page}
                  onPageChange={(page) => fetchEnrollments(page)}
                />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
