import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { AuthState, LoginResponse } from '@/types/auth';

interface AuthStore extends AuthState {
  isLoading: boolean;
  setAuth: (data: LoginResponse['data']) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthStore>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      permissions: [],
      isLoading: false,
      setAuth: (data) =>
        set({
          user: data.user,
          token: data.token,
          permissions: data.permissions,
        }),
      logout: () => set({ user: null, token: null, permissions: [] }),
    }),
    {
      name: 'auth-storage',
    }
  )
);

export const hasPermission = (permission: string): boolean => {
  const permissions = useAuthStore.getState().permissions;
  return permissions.includes(permission);
};
