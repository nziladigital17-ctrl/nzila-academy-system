import api from '@/lib/axios';
import type { PaginatedResponse, ApiResponse } from '@/types';
import type { Student, StudentFilters, Guardian, StudentGuardianRequest, Enrollment, EnrollmentFilters } from '@/types';

// ── Students ──────────────────────────────────────────────────

export async function getStudents(params?: StudentFilters & { page?: number }): Promise<PaginatedResponse<Student>> {
  const { data } = await api.get<PaginatedResponse<Student>>('/students', { params });
  return data;
}

export async function getStudent(id: number): Promise<ApiResponse<Student>> {
  const { data } = await api.get<ApiResponse<Student>>(`/students/${id}`);
  return data;
}

export async function createStudent(formData: any): Promise<Student> {
  const { data } = await api.post<{ data: Student }>('/students', formData);
  return data.data;
}

export async function updateStudent(id: number, formData: any): Promise<Student> {
  const { data } = await api.put<{ data: Student }>(`/students/${id}`, formData);
  return data.data;
}

export async function deleteStudent(id: number): Promise<void> {
  await api.delete(`/students/${id}`);
}

// ── Student Guardians ──────────────────────────────────────────

export async function getStudentGuardians(studentId: number): Promise<Guardian[]> {
  const { data } = await api.get<{ data: Guardian[] }>(`/students/${studentId}/guardians`);
  return data.data;
}

export async function attachGuardianToStudent(studentId: number, formData: StudentGuardianRequest): Promise<void> {
  await api.post(`/students/${studentId}/guardians`, formData);
}

export async function detachGuardianFromStudent(studentId: number, guardianId: number): Promise<void> {
  await api.delete(`/students/${studentId}/guardians/${guardianId}`);
}

// ── Guardians ──────────────────────────────────────────────────

export async function getGuardians(params?: { search?: string, page?: number }): Promise<PaginatedResponse<Guardian>> {
  const { data } = await api.get<PaginatedResponse<Guardian>>('/guardians', { params });
  return data;
}

export async function createGuardian(formData: any): Promise<Guardian> {
  const { data } = await api.post<{ data: Guardian }>('/guardians', formData);
  return data.data;
}

// ── Enrollments ────────────────────────────────────────────────

export async function getEnrollments(params?: EnrollmentFilters & { page?: number }): Promise<PaginatedResponse<Enrollment>> {
  const { data } = await api.get<PaginatedResponse<Enrollment>>('/enrollments', { params });
  return data;
}

export async function createEnrollment(formData: any): Promise<Enrollment> {
  const { data } = await api.post<{ data: Enrollment }>('/enrollments', formData);
  return data.data;
}

export async function updateEnrollment(id: number, formData: any): Promise<Enrollment> {
  const { data } = await api.put<{ data: Enrollment }>(`/enrollments/${id}`, formData);
  return data.data;
}

export async function deleteEnrollment(id: number): Promise<void> {
  await api.delete(`/enrollments/${id}`);
}
