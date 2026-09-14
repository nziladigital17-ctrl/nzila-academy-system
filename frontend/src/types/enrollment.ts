import { Student } from './student';
import { SchoolClass } from './academic';

export interface Enrollment {
  id: number;
  school_id: number;
  student_id: number;
  class_id: number;
  status: 'active' | 'cancelled' | 'completed';
  enrollment_date: string;
  created_at: string;
  updated_at: string;
  student?: Student;
  schoolClass?: SchoolClass;
}

export interface EnrollmentFilters {
  student_id?: number;
  class_id?: number;
  status?: string;
  academic_year_id?: number;
}
