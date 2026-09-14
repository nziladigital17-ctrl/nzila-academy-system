import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useAcademicYearStore } from './academicYearStore';
import * as academicService from '@/api/academicService';

// Mock the API service
vi.mock('@/api/academicService', () => ({
  getAcademicYears: vi.fn(),
}));

describe('academicYearStore', () => {
  const mockYears = [
    { id: 1, school_id: 1, name: '2024/2025', start_date: '2024-09-01', end_date: '2025-06-30', is_current: false, created_at: '', updated_at: '' },
    { id: 2, school_id: 1, name: '2025/2026', start_date: '2025-09-01', end_date: '2026-06-30', is_current: true, created_at: '', updated_at: '' },
  ];

  beforeEach(() => {
    useAcademicYearStore.setState({ years: [], selectedYearId: null, isLoading: false, error: null });
    vi.clearAllMocks();
  });

  it('should initialize with empty state', () => {
    const state = useAcademicYearStore.getState();
    expect(state.years).toEqual([]);
    expect(state.selectedYearId).toBeNull();
    expect(state.isLoading).toBe(false);
  });

  it('should fetch years and set the current year as selected by default', async () => {
    (academicService.getAcademicYears as any).mockResolvedValue({ data: mockYears });

    await useAcademicYearStore.getState().fetchYears();

    const state = useAcademicYearStore.getState();
    expect(state.years).toEqual(mockYears);
    expect(state.selectedYearId).toBe(2); // The current one
    expect(state.isLoading).toBe(false);
  });

  it('should fallback to the first year if no current year is found', async () => {
    const noCurrentYears = mockYears.map(y => ({ ...y, is_current: false }));
    (academicService.getAcademicYears as any).mockResolvedValue({ data: noCurrentYears });

    await useAcademicYearStore.getState().fetchYears();

    const state = useAcademicYearStore.getState();
    expect(state.selectedYearId).toBe(1); // The first one
  });

  it('should preserve selectedYearId if it already exists during fetch', async () => {
    useAcademicYearStore.setState({ selectedYearId: 1 });
    (academicService.getAcademicYears as any).mockResolvedValue({ data: mockYears });

    await useAcademicYearStore.getState().fetchYears();

    const state = useAcademicYearStore.getState();
    expect(state.selectedYearId).toBe(1); // Should not override with 2
  });

  it('setSelectedYear should update selectedYearId', () => {
    useAcademicYearStore.getState().setSelectedYear(99);
    expect(useAcademicYearStore.getState().selectedYearId).toBe(99);
  });

  it('getSelectedYear should return the selected year object', () => {
    useAcademicYearStore.setState({ years: mockYears, selectedYearId: 1 });
    const selected = useAcademicYearStore.getState().getSelectedYear();
    expect(selected).toEqual(mockYears[0]);
  });
});
