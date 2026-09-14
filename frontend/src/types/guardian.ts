export interface Guardian {
  id: number;
  school_id: number;
  full_name: string;
  relationship: string;
  phone: string;
  email: string | null;
  bi_number: string | null;
  occupation: string | null;
  address: string | null;
  created_at: string;
  updated_at: string;
  pivot?: {
    relationship: string;
    is_primary: boolean;
  };
}

export interface StudentGuardianRequest {
  guardian_id: number;
  relationship: 'pai' | 'mae' | 'tutor' | 'outro';
  is_primary?: boolean;
}
