import React from 'react';
import { Outlet, Navigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';

export const GuestLayout: React.FC = () => {
  const token = useAuthStore((state) => state.token);

  if (token) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="guest-container">
      <div className="w-full">
        <h2 className="guest-header">
          Nzila Academy
        </h2>
      </div>
      <div className="w-full">
        <div className="guest-card">
          <Outlet />
        </div>
      </div>
    </div>
  );
};
