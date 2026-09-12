import { describe, it, expect, beforeEach, afterAll } from 'vitest';
import api from './axios';
import { useAuthStore } from '@/stores/authStore';

// Mock window.location
const originalLocation = window.location;

describe('Axios Interceptors', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, token: null, permissions: [] });
    
    // Reset location mock
    delete (window as any).location;
    window.location = { ...originalLocation, href: '', pathname: '/some-path' } as any;
  });

  afterAll(() => {
    (window as any).location = originalLocation;
  });

  it('should add Authorization header if token exists', async () => {
    useAuthStore.setState({ token: 'test-token' });
    
    // We can extract the request interceptor and call it directly
    const requestInterceptor = (api.interceptors.request as any).handlers[0].fulfilled;
    const config = { headers: {} };
    
    const result = await requestInterceptor(config);
    expect(result.headers.Authorization).toBe('Bearer test-token');
  });

  it('should handle 401 response by clearing auth state and redirecting to /login', async () => {
    useAuthStore.setState({
      user: { id: 1, name: 'Test', email: 't@t.com', school_id: 1, status: 'active' },
      token: 'test-token',
      permissions: ['test']
    });

    const responseInterceptorError = (api.interceptors.response as any).handlers[0].rejected;
    
    const error = {
      response: { status: 401 }
    };

    try {
      await responseInterceptorError(error);
    } catch (e) {
      // expected to reject
    }

    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.user).toBeNull();
    expect(state.permissions).toEqual([]);
    
    expect(window.location.href).toBe('/login');
  });
});
