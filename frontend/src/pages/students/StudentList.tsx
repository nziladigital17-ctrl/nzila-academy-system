import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useStudentStore } from '@/stores/studentStore';
import { useAuthStore, hasPermission } from '@/stores/authStore';
import { PageHeader } from '@/components/ui/PageHeader';
import { SearchInput } from '@/components/ui/SearchInput';
import { Badge } from '@/components/ui/Badge';
import { Pagination } from '@/components/ui/Pagination';
import { EmptyState } from '@/components/ui/EmptyState';

export function StudentList() {
  const navigate = useNavigate();
  const { students, pagination, isLoading, filters, setFilters, fetchStudents } = useStudentStore();
  const canCreate = hasPermission('students.create');

  useEffect(() => {
    fetchStudents(1);
  }, [fetchStudents]);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Alunos"
        description="Gestão de alunos e processos individuais"
        actionLabel={canCreate ? "+ Novo Aluno" : undefined}
        onAction={canCreate ? () => navigate('/students/new') : undefined}
      />

      <div className="flex gap-4 mb-4">
        <SearchInput
          placeholder="Pesquisar por nome ou número..."
          value={filters.search || ''}
          onChange={(v) => setFilters({ search: v })}
          className="max-w-md"
        />
        <select
          className="form-select border border-gray-300 rounded-md px-3 py-2 text-sm"
          value={filters.is_active === undefined ? '' : String(filters.is_active)}
          onChange={(e) => {
            const val = e.target.value;
            setFilters({ is_active: val === '' ? undefined : val === 'true' });
          }}
        >
          <option value="">Todos os Estados</option>
          <option value="true">Activos</option>
          <option value="false">Inactivos</option>
        </select>
      </div>

      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">A carregar alunos...</div>
        ) : students.length === 0 ? (
          <EmptyState
            title="Nenhum aluno encontrado"
            description="Tente ajustar os filtros de pesquisa."
          />
        ) : (
          <>
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nº Aluno
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nome Completo
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Género
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acções
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {students.map((student) => (
                  <tr key={student.id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {student.student_number || 'N/D'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {student.full_name}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {student.gender === 'M' ? 'Masculino' : 'Feminino'}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <Badge variant={student.is_active ? 'success' : 'danger'}>
                        {student.is_active ? 'Activo' : 'Inactivo'}
                      </Badge>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button
                        onClick={() => navigate(`/students/${student.id}`)}
                        className="text-nzila-blue hover:text-nzila-gold"
                      >
                        Ver Detalhe
                      </button>
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
                  onPageChange={(page) => fetchStudents(page)}
                />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
