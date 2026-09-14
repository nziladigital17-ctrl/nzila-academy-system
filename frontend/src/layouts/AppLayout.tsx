import React, { useState } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { useAuthStore, hasPermission } from '@/stores/authStore';
import { useAcademicYearStore } from '@/stores/academicYearStore';
import api from '@/lib/axios';

interface NavItem {
  id: string;
  label: string;
  icon: string;
  path: string;
  permission?: string;
}

const NAV_ITEMS: NavItem[] = [
  { id: 'dashboard', label: 'Visão Geral', icon: '📊', path: '/' },
  { id: 'academic', label: 'Estrutura Académica', icon: '🏛️', path: '/academic', permission: 'academic_years.view' },
  { id: 'students', label: 'Alunos', icon: '👥', path: '/students', permission: 'students.view' },
  { id: 'enrollments', label: 'Matrículas', icon: '📝', path: '/enrollments', permission: 'enrollments.view' },
  // Future modules — not yet implemented
  // { id: 'pedagogic', label: 'Pedagógico', icon: '📚', path: '/pedagogic', permission: 'grades.view' },
  // { id: 'finance', label: 'Financeiro', icon: '💰', path: '/finance', permission: 'finance.view' },
  // { id: 'reports', label: 'Relatórios', icon: '📈', path: '/reports', permission: 'reports.view' },
];

export const AppLayout: React.FC = () => {
  const { user, logout } = useAuthStore();
  const { years, selectedYearId, setSelectedYear } = useAcademicYearStore();
  const navigate = useNavigate();
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);

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

  const handleNavClick = (path: string) => {
    navigate(path);
    setSidebarOpen(false);
  };

  const isActive = (path: string) => {
    if (path === '/') return location.pathname === '/';
    return location.pathname.startsWith(path);
  };

  const selectedYear = years.find((y) => y.id === selectedYearId);
  const userInitials = user?.name
    ?.split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase() ?? '?';

  return (
    <div className="nz-app">
      {/* Mobile sidebar backdrop */}
      {sidebarOpen && (
        <div
          className="nz-sidebar-backdrop nz-sidebar-backdrop--visible"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside className={`nz-sidebar ${sidebarOpen ? 'nz-sidebar--open' : ''}`}>
        <div className="nz-sidebar__brand">
          <div className="nz-sidebar__brand-icon">NA</div>
          <div className="nz-sidebar__brand-text">
            <span className="nz-sidebar__brand-name">Nzila Academy</span>
            <span className="nz-sidebar__brand-sub">Gestão Escolar</span>
          </div>
        </div>

        {selectedYear && (
          <div className="nz-sidebar__year-pill">
            <span aria-hidden="true">📅</span>
            Ano Lectivo {selectedYear.name}
          </div>
        )}

        <div className="nz-sidebar__section-label">Menu Principal</div>

        <nav className="nz-sidebar__nav">
          {NAV_ITEMS.filter(
            (item) => !item.permission || hasPermission(item.permission)
          ).map((item) => (
            <button
              key={item.id}
              className={`nz-sidebar__link ${isActive(item.path) ? 'nz-sidebar__link--active' : ''}`}
              onClick={() => handleNavClick(item.path)}
              type="button"
            >
              <span className="nz-sidebar__link-icon" aria-hidden="true">{item.icon}</span>
              {item.label}
            </button>
          ))}
        </nav>

        <div className="nz-sidebar__footer">
          <div>Suporte Técnico — 24/7</div>
          <div>República de Angola — v2.4</div>
        </div>
      </aside>

      {/* Main content */}
      <div className="nz-main">
        {/* Top bar */}
        <header className="nz-topbar">
          <div className="nz-topbar__left">
            <button
              className="nz-topbar__menu-btn"
              onClick={() => setSidebarOpen(!sidebarOpen)}
              aria-label="Abrir menu"
              type="button"
            >
              ☰
            </button>

            {years.length > 0 && (
              <select
                className="nz-select"
                value={selectedYearId ?? ''}
                onChange={(e) => setSelectedYear(Number(e.target.value))}
                style={{ width: 'auto', minWidth: '180px', height: '36px', fontSize: '13px' }}
                aria-label="Seleccionar ano lectivo"
              >
                {years.map((year) => (
                  <option key={year.id} value={year.id}>
                    {year.name} {year.is_current ? '(Em curso)' : ''}
                  </option>
                ))}
              </select>
            )}
          </div>

          <div className="nz-topbar__right">
            <div className="nz-topbar__user">
              <div className="nz-topbar__user-info">
                <span className="nz-topbar__user-name">{user?.name}</span>
                <span className="nz-topbar__user-role">{user?.email}</span>
              </div>
              <div className="nz-topbar__avatar">{userInitials}</div>
            </div>
            <button
              className="nz-btn nz-btn--ghost nz-btn--sm"
              onClick={handleLogout}
              type="button"
            >
              Sair
            </button>
          </div>
        </header>

        <div className="nz-content">
          <Outlet />
        </div>
      </div>
    </div>
  );
};
