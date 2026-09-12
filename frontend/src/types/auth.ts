export interface User {
  id: number;
  name: string;
  email: string;
  school_id: number;
  status: string;
}

export interface AuthState {
  user: User | null;
  token: string | null;
  permissions: string[];
}

export interface LoginResponse {
  message: string;
  data: {
    user: User;
    token: string;
    permissions: string[];
  };
}
