/**
 * Academic Year Store — Global state for the selected academic year.
 * Used across the academic module as a context filter.
 */
import { create } from 'zustand';
import type { AcademicYear } from '@/types/academic';
import { getAcademicYears } from '@/api/academicService';

interface AcademicYearState {
  years: AcademicYear[];
  selectedYearId: number | null;
  isLoading: boolean;
  error: string | null;

  fetchYears: () => Promise<void>;
  setSelectedYear: (id: number) => void;
  getSelectedYear: () => AcademicYear | undefined;
  getCurrentYear: () => AcademicYear | undefined;
}

export const useAcademicYearStore = create<AcademicYearState>((set, get) => ({
  years: [],
  selectedYearId: null,
  isLoading: false,
  error: null,

  fetchYears: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await getAcademicYears({ page: 1 });
      const years = response.data;
      const currentYear = years.find((y) => y.is_current);
      set({
        years,
        selectedYearId: get().selectedYearId ?? currentYear?.id ?? years[0]?.id ?? null,
        isLoading: false,
      });
    } catch {
      set({ isLoading: false, error: 'Erro ao carregar anos lectivos.' });
    }
  },

  setSelectedYear: (id: number) => {
    set({ selectedYearId: id });
  },

  getSelectedYear: () => {
    const { years, selectedYearId } = get();
    return years.find((y) => y.id === selectedYearId);
  },

  getCurrentYear: () => {
    return get().years.find((y) => y.is_current);
  },
}));
