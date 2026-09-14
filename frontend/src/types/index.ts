export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
  };
}

export interface ApiResponse<T> {
  message?: string;
  data: T;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export * from './student';
export * from './guardian';
export * from './enrollment';
