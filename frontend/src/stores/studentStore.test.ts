import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useStudentStore } from './studentStore';
import * as studentService from '@/api/studentService';

vi.mock('@/api/studentService');

describe('studentStore', () => {
  beforeEach(() => {
    useStudentStore.setState({ students: [], pagination: null, filters: {}, isLoading: false, error: null });
    vi.clearAllMocks();
  });

  it('fetches students and updates state', async () => {
    const mockData = {
      data: [{ id: 1, full_name: 'John Doe', student_number: '123', gender: 'M', birth_date: '2000-01-01', is_active: true, created_at: '', updated_at: '', school_id: 1, birth_place: null, nationality: null, bi_number: null, address: null }],
      meta: { current_page: 1, from: 1, last_page: 1, path: '', per_page: 15, to: 1, total: 1 },
      links: { first: '', last: '', prev: null, next: null }
    };
    
    vi.mocked(studentService.getStudents).mockResolvedValue(mockData as any);

    const store = useStudentStore.getState();
    await store.fetchStudents();

    expect(studentService.getStudents).toHaveBeenCalledWith({ page: 1 });
    expect(useStudentStore.getState().students).toEqual(mockData.data);
    expect(useStudentStore.getState().pagination).toEqual(mockData.meta);
    expect(useStudentStore.getState().isLoading).toBe(false);
  });

  it('updates filters and fetches page 1', async () => {
    vi.mocked(studentService.getStudents).mockResolvedValue({ data: [], meta: {} as any, links: {} as any });
    
    const store = useStudentStore.getState();
    store.setFilters({ search: 'John' });

    expect(useStudentStore.getState().filters).toEqual({ search: 'John' });
    expect(studentService.getStudents).toHaveBeenCalledWith({ search: 'John', page: 1 });
  });

  it('handles fetch errors', async () => {
    vi.mocked(studentService.getStudents).mockRejectedValue(new Error('Network error'));
    
    const store = useStudentStore.getState();
    await store.fetchStudents();

    expect(useStudentStore.getState().error).toBe('Erro ao carregar alunos.');
    expect(useStudentStore.getState().isLoading).toBe(false);
  });
});
