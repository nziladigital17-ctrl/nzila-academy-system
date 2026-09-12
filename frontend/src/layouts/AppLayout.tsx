import React from 'react';
import { Outlet, useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import api from '@/lib/axios';

export const AppLayout: React.FC = () => {
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.error('Logout failed', error);
    } finally {
      logout();
      navigate('/login');
    }
  };

  return (
    <div className="app-container">
      {/* Sidebar (Placeholder) */}
      <aside className="app-sidebar">
        <h1>Nzila Academy</h1>
        <nav>
          <ul style={{ listStyle: 'none', padding: 0 }}>
            <li style={{ marginBottom: '0.5rem' }}>
              <button onClick={() => navigate('/')} className="nav-link">
                Dashboard
              </button>
            </li>
            {/* Future navigation links here */}
          </ul>
        </nav>
      </aside>

      {/* Main Content */}
      <main className="app-main">
        {/* Header (Placeholder) */}
        <header className="app-header">
          <div>Bem-vindo, {user?.name}</div>
          <button
            onClick={handleLogout}
            className="btn-logout"
          >
            Sair
          </button>
        </header>
        
        <div className="app-content">
          <Outlet />
        </div>
      </main>
    </div>
  );
};
