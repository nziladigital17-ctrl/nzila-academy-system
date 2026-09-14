import { create } from 'zustand';
import type { Enrollment, EnrollmentFilters, PaginatedResponse } from '@/types';
import { getEnrollments } from '@/api/studentService';

interface EnrollmentState {
  enrollments: Enrollment[];
  pagination: PaginatedResponse<Enrollment>['meta'] | null;
  filters: EnrollmentFilters;
  isLoading: boolean;
  error: string | null;

  setFilters: (filters: Partial<EnrollmentFilters>) => void;
  fetchEnrollments: (page?: number) => Promise<void>;
}

export const useEnrollmentStore = create<EnrollmentState>((set, get) => ({
  enrollments: [],
  pagination: null,
  filters: {},
  isLoading: false,
  error: null,

  setFilters: (filters) => {
    set((state) => ({ filters: { ...state.filters, ...filters } }));
    get().fetchEnrollments(1);
  },

  fetchEnrollments: async (page = 1) => {
    const { filters } = get();
    set({ isLoading: true, error: null });
    try {
      const response = await getEnrollments({ ...filters, page });
      set({
        enrollments: response.data,
        pagination: response.meta,
        isLoading: false,
      });
    } catch {
      set({ isLoading: false, error: 'Erro ao carregar matrículas.' });
    }
  },
}));
