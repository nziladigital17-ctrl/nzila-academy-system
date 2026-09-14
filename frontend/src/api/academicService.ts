/**
 * Academic Module — API Service Layer
 * Consumes the Laravel API endpoints for academic structure management.
 * All functions use the pre-configured Axios instance from @/lib/axios.
 */
import api from '@/lib/axios';
import type { PaginatedResponse, ApiResponse } from '@/types';
import type {
  AcademicYear,
  AcademicYearFormData,
  AcademicYearListParams,
  Term,
  TermFormData,
  Subject,
  SubjectFormData,
  SubjectListParams,
  Room,
  RoomFormData,
  RoomListParams,
  SchoolClass,
  SchoolClassFormData,
  ClassListParams,
  Teacher,
  TeacherListParams,
  TeacherAssignment,
  TeacherAssignmentFormData,
  TeacherAssignmentListParams,
} from '@/types/academic';

// ── Academic Years ────────────────────────────────────────────

export async function getAcademicYears(params?: AcademicYearListParams): Promise<PaginatedResponse<AcademicYear>> {
  const { data } = await api.get<PaginatedResponse<AcademicYear>>('/academic-years', { params });
  return data;
}

export async function getAcademicYear(id: number): Promise<ApiResponse<AcademicYear & { terms?: Term[] }>> {
  const { data } = await api.get<ApiResponse<AcademicYear & { terms?: Term[] }>>(`/academic-years/${id}`);
  return data;
}

export async function createAcademicYear(formData: AcademicYearFormData): Promise<AcademicYear> {
  const { data } = await api.post<{ data: AcademicYear }>('/academic-years', formData);
  return data.data;
}

export async function updateAcademicYear(id: number, formData: Partial<AcademicYearFormData>): Promise<AcademicYear> {
  const { data } = await api.put<{ data: AcademicYear }>(`/academic-years/${id}`, formData);
  return data.data;
}

export async function deleteAcademicYear(id: number): Promise<void> {
  await api.delete(`/academic-years/${id}`);
}

// ── Terms ─────────────────────────────────────────────────────

export async function getTerms(academicYearId: number): Promise<Term[]> {
  const { data } = await api.get<{ data: Term[] }>(`/academic-years/${academicYearId}/terms`);
  return data.data;
}

export async function createTerm(formData: TermFormData): Promise<Term> {
  const { data } = await api.post<{ data: Term }>('/terms', formData);
  return data.data;
}

export async function updateTerm(id: number, formData: Partial<TermFormData>): Promise<Term> {
  const { data } = await api.put<{ data: Term }>(`/terms/${id}`, formData);
  return data.data;
}

export async function deleteTerm(id: number): Promise<void> {
  await api.delete(`/terms/${id}`);
}

// ── Subjects ──────────────────────────────────────────────────

export async function getSubjects(params?: SubjectListParams): Promise<PaginatedResponse<Subject>> {
  const { data } = await api.get<PaginatedResponse<Subject>>('/subjects', { params });
  return data;
}

export async function createSubject(formData: SubjectFormData): Promise<Subject> {
  const { data } = await api.post<{ data: Subject }>('/subjects', formData);
  return data.data;
}

export async function updateSubject(id: number, formData: Partial<SubjectFormData>): Promise<Subject> {
  const { data } = await api.put<{ data: Subject }>(`/subjects/${id}`, formData);
  return data.data;
}

export async function deleteSubject(id: number): Promise<void> {
  await api.delete(`/subjects/${id}`);
}

// ── Rooms ─────────────────────────────────────────────────────

export async function getRooms(params?: RoomListParams): Promise<PaginatedResponse<Room>> {
  const { data } = await api.get<PaginatedResponse<Room>>('/rooms', { params });
  return data;
}

export async function createRoom(formData: RoomFormData): Promise<Room> {
  const { data } = await api.post<{ data: Room }>('/rooms', formData);
  return data.data;
}

export async function updateRoom(id: number, formData: Partial<RoomFormData>): Promise<Room> {
  const { data } = await api.put<{ data: Room }>(`/rooms/${id}`, formData);
  return data.data;
}

export async function deleteRoom(id: number): Promise<void> {
  await api.delete(`/rooms/${id}`);
}

// ── Classes ───────────────────────────────────────────────────

export async function getClasses(params?: ClassListParams): Promise<PaginatedResponse<SchoolClass>> {
  const { data } = await api.get<PaginatedResponse<SchoolClass>>('/classes', { params });
  return data;
}

export async function createClass(formData: SchoolClassFormData): Promise<SchoolClass> {
  const { data } = await api.post<{ data: SchoolClass }>('/classes', formData);
  return data.data;
}

export async function updateClass(id: number, formData: Partial<SchoolClassFormData>): Promise<SchoolClass> {
  const { data } = await api.put<{ data: SchoolClass }>(`/classes/${id}`, formData);
  return data.data;
}

export async function deleteClass(id: number): Promise<void> {
  await api.delete(`/classes/${id}`);
}

// ── Teachers (for dropdowns) ──────────────────────────────────

export async function getTeachers(params?: TeacherListParams): Promise<PaginatedResponse<Teacher>> {
  const { data } = await api.get<PaginatedResponse<Teacher>>('/teachers', { params });
  return data;
}

// ── Teacher Assignments ───────────────────────────────────────

export async function getTeacherAssignments(params?: TeacherAssignmentListParams): Promise<PaginatedResponse<TeacherAssignment>> {
  const { data } = await api.get<PaginatedResponse<TeacherAssignment>>('/teaching-assignments', { params });
  return data;
}

export async function createTeacherAssignment(formData: TeacherAssignmentFormData): Promise<TeacherAssignment> {
  const { data } = await api.post<{ data: TeacherAssignment }>('/teaching-assignments', formData);
  return data.data;
}

export async function deleteTeacherAssignment(id: number): Promise<void> {
  await api.delete(`/teaching-assignments/${id}`);
}
