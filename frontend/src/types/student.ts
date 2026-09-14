import { Guardian } from './guardian';
import { SchoolClass } from './academic';

export interface Student {
  id: number;
  school_id: number;
  full_name: string;
  student_number: string;
  gender: 'M' | 'F';
  birth_date: string;
  birth_place: string | null;
  nationality: string | null;
  bi_number: string | null;
  address: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  guardians?: Guardian[];
  classes?: SchoolClass[];
}

export interface StudentFilters {
  search?: string;
  is_active?: boolean | string;
  class_id?: number;
}
