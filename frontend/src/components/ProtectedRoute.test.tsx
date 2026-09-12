
import { render } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import { describe, it, expect, beforeEach } from 'vitest';
import { ProtectedRoute } from './ProtectedRoute';
import { useAuthStore } from '@/stores/authStore';

const TestComponent = () => <div data-testid="protected-content">Protected Content</div>;
const LoginComponent = () => <div data-testid="login-page">Login Page</div>;
const ForbiddenComponent = () => <div data-testid="forbidden-page">Forbidden Page</div>;

describe('ProtectedRoute', () => {
  beforeEach(() => {
    useAuthStore.setState({ user: null, token: null, permissions: [] });
  });

  const renderRoute = (requiredPermission?: string) => {
    return render(
      <MemoryRouter initialEntries={['/protected']}>
        <Routes>
          <Route path="/login" element={<LoginComponent />} />
          <Route path="/403" element={<ForbiddenComponent />} />
          <Route element={<ProtectedRoute requiredPermission={requiredPermission} />}>
            <Route path="/protected" element={<TestComponent />} />
          </Route>
        </Routes>
      </MemoryRouter>
    );
  };

  it('should redirect to /login if there is no token', () => {
    const { getByTestId, queryByTestId } = renderRoute();
    
    expect(getByTestId('login-page')).toBeInTheDocument();
    expect(queryByTestId('protected-content')).not.toBeInTheDocument();
  });

  it('should redirect to /403 if token exists but lacks required permission', () => {
    useAuthStore.setState({
      token: 'fake-token',
      permissions: ['some.other.permission']
    });

    const { getByTestId, queryByTestId } = renderRoute('required.permission');
    
    expect(getByTestId('forbidden-page')).toBeInTheDocument();
    expect(queryByTestId('protected-content')).not.toBeInTheDocument();
  });

  it('should render children if token exists and has required permission', () => {
    useAuthStore.setState({
      token: 'fake-token',
      permissions: ['required.permission']
    });

    const { getByTestId } = renderRoute('required.permission');
    
    expect(getByTestId('protected-content')).toBeInTheDocument();
  });
});
