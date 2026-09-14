import { create } from 'zustand';
import type { Student, StudentFilters, PaginatedResponse } from '@/types';
import { getStudents } from '@/api/studentService';

interface StudentState {
  students: Student[];
  pagination: PaginatedResponse<Student>['meta'] | null;
  filters: StudentFilters;
  isLoading: boolean;
  error: string | null;

  setFilters: (filters: Partial<StudentFilters>) => void;
  fetchStudents: (page?: number) => Promise<void>;
}

export const useStudentStore = create<StudentState>((set, get) => ({
  students: [],
  pagination: null,
  filters: {},
  isLoading: false,
  error: null,

  setFilters: (filters) => {
    set((state) => ({ filters: { ...state.filters, ...filters } }));
    get().fetchStudents(1);
  },

  fetchStudents: async (page = 1) => {
    const { filters } = get();
    set({ isLoading: true, error: null });
    try {
      const response = await getStudents({ ...filters, page });
      set({
        students: response.data,
        pagination: response.meta,
        isLoading: false,
      });
    } catch {
      set({ isLoading: false, error: 'Erro ao carregar alunos.' });
    }
  },
}));
