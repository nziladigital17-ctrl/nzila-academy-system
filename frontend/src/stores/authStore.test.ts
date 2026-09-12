import { describe, it, expect, beforeEach } from 'vitest';
import { useAuthStore, hasPermission } from './authStore';

describe('AuthStore', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, token: null, permissions: [], isLoading: false });
  });

  it('should initialize with null user and token', () => {
    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.token).toBeNull();
    expect(state.permissions).toEqual([]);
  });

  it('should set authentication data on login', () => {
    const mockData = {
      user: { id: 1, name: 'Admin', email: 'admin@test.com', school_id: 1, status: 'active' },
      token: 'fake-token-123',
      permissions: ['finance.view', 'users.create'],
    };

    useAuthStore.getState().setAuth(mockData);

    const state = useAuthStore.getState();
    expect(state.user).toEqual(mockData.user);
    expect(state.token).toBe('fake-token-123');
    expect(state.permissions).toContain('finance.view');
  });

  it('should clear authentication data on logout', () => {
    useAuthStore.setState({
      user: { id: 1, name: 'Admin', email: 'a@a.com', school_id: 1, status: 'active' },
      token: 'token',
      permissions: ['test']
    });

    useAuthStore.getState().logout();
    
    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.token).toBeNull();
    expect(state.permissions).toEqual([]);
  });

  it('hasPermission should return true if permission exists', () => {
    useAuthStore.setState({ permissions: ['schools.view', 'schools.create'] });
    expect(hasPermission('schools.view')).toBe(true);
    expect(hasPermission('schools.delete')).toBe(false);
  });
});
