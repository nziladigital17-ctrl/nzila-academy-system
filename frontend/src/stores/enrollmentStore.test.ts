import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useEnrollmentStore } from './enrollmentStore';
import * as studentService from '@/api/studentService';

vi.mock('@/api/studentService');

describe('enrollmentStore', () => {
  beforeEach(() => {
    useEnrollmentStore.setState({ enrollments: [], pagination: null, filters: {}, isLoading: false, error: null });
    vi.clearAllMocks();
  });

  it('fetches enrollments and updates state', async () => {
    const mockData = {
      data: [{ id: 1, student_id: 1, class_id: 1, status: 'active', enrollment_date: '2000-01-01', created_at: '', updated_at: '', school_id: 1 }],
      meta: { current_page: 1, from: 1, last_page: 1, path: '', per_page: 15, to: 1, total: 1 },
      links: { first: '', last: '', prev: null, next: null }
    };
    
    vi.mocked(studentService.getEnrollments).mockResolvedValue(mockData as any);

    const store = useEnrollmentStore.getState();
    await store.fetchEnrollments();

    expect(studentService.getEnrollments).toHaveBeenCalledWith({ page: 1 });
    expect(useEnrollmentStore.getState().enrollments).toEqual(mockData.data);
    expect(useEnrollmentStore.getState().pagination).toEqual(mockData.meta);
    expect(useEnrollmentStore.getState().isLoading).toBe(false);
  });

  it('updates filters and fetches page 1', async () => {
    vi.mocked(studentService.getEnrollments).mockResolvedValue({ data: [], meta: {} as any, links: {} as any });
    
    const store = useEnrollmentStore.getState();
    store.setFilters({ status: 'active' });

    expect(useEnrollmentStore.getState().filters).toEqual({ status: 'active' });
    expect(studentService.getEnrollments).toHaveBeenCalledWith({ status: 'active', page: 1 });
  });

  it('handles fetch errors', async () => {
    vi.mocked(studentService.getEnrollments).mockRejectedValue(new Error('Network error'));
    
    const store = useEnrollmentStore.getState();
    await store.fetchEnrollments();

    expect(useEnrollmentStore.getState().error).toBe('Erro ao carregar matrículas.');
    expect(useEnrollmentStore.getState().isLoading).toBe(false);
  });
});
