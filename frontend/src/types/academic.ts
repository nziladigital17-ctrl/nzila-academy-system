/* ============================================================
   Academic Module — TypeScript Types
   Matches API Resource contracts exactly
   ============================================================ */

/** Returned by AcademicYearResource */
export interface AcademicYear {
  id: number;
  school_id: number;
  name: string;
  start_date: string;
  end_date: string;
  is_current: boolean;
  created_at: string;
  updated_at: string;
}

export interface AcademicYearFormData {
  name: string;
  start_date: string;
  end_date: string;
  is_current?: boolean;
}

/** Returned by TermResource */
export interface Term {
  id: number;
  academic_year_id: number;
  name: string;
  start_date: string;
  end_date: string;
  created_at: string;
  updated_at: string;
}

/** Derived state — not from API */
export type TermStatus = 'planned' | 'active' | 'finished';

export interface TermFormData {
  academic_year_id: number;
  name: string;
  start_date: string;
  end_date: string;
}

/** Returned by SubjectResource */
export interface Subject {
  id: number;
  school_id: number;
  name: string;
  code: string;
  description: string | null;
  weekly_hours: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface SubjectFormData {
  name: string;
  code: string;
  description?: string | null;
  weekly_hours?: number;
  is_active?: boolean;
}

/** Returned by RoomResource */
export interface Room {
  id: number;
  school_id: number;
  name: string;
  capacity: number | null;
  building: string | null;
  created_at: string;
  updated_at: string;
}

export interface RoomFormData {
  name: string;
  capacity?: number | null;
  building?: string | null;
}

/** Returned by ClassResource */
export interface SchoolClass {
  id: number;
  school_id: number;
  academic_year_id: number;
  subject_id: number | null;
  room_id: number | null;
  name: string;
  grade_level: string;
  shift: ShiftType;
  max_students: number;
  created_at: string;
  updated_at: string;
}

export type ShiftType = 'morning' | 'afternoon' | 'evening';

export interface SchoolClassFormData {
  academic_year_id: number;
  name: string;
  grade_level: string;
  shift: ShiftType;
  max_students?: number;
  subject_id?: number | null;
  room_id?: number | null;
}

/** Returned by TeacherResource */
export interface Teacher {
  id: number;
  school_id: number;
  user_id: number | null;
  employee_number: string;
  full_name: string;
  specialization: string | null;
  academic_degree: string | null;
  phone: string | null;
  hire_date: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

/** Returned by TeacherAssignmentResource — with eager-loaded relations */
export interface TeacherAssignment {
  id: number;
  school_id: number;
  academic_year_id: number;
  teacher_id: number;
  class_id: number;
  subject_id: number;
  role: AssignmentRole;
  created_at: string;
  updated_at: string;
  /* Eager-loaded relations (returned by controller index/show) */
  teacher?: Teacher;
  school_class?: SchoolClass;
  subject?: Subject;
  academic_year?: AcademicYear;
}

export type AssignmentRole = 'titular' | 'auxiliary';

export interface TeacherAssignmentFormData {
  teacher_id: number;
  class_id: number;
  subject_id: number;
  academic_year_id: number;
  role: AssignmentRole;
}

/** Query parameters for list endpoints */
export interface AcademicYearListParams {
  page?: number;
  current?: string;
  search?: string;
}

export interface SubjectListParams {
  page?: number;
  status?: 'active' | 'inactive';
  search?: string;
}

export interface RoomListParams {
  page?: number;
  search?: string;
}

export interface ClassListParams {
  page?: number;
  academic_year_id?: number;
  grade_level?: string;
  shift?: ShiftType;
  search?: string;
}

export interface TeacherListParams {
  page?: number;
  status?: 'active' | 'inactive';
  search?: string;
}

export interface TeacherAssignmentListParams {
  page?: number;
  teacher_id?: number;
  class_id?: number;
  subject_id?: number;
  academic_year_id?: number;
}
