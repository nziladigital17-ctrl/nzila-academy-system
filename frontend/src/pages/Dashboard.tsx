import React from 'react';
import { useAuthStore } from '@/stores/authStore';

export const Dashboard: React.FC = () => {
  const { user, permissions } = useAuthStore();

  return (
    <div className="card">
      <h2 style={{ fontSize: '1.5rem', marginBottom: '1rem' }}>Visão Geral</h2>
      <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem' }}>
        Acesso concedido à fundação funcional do Nzila Academy.
      </p>

      <div className="grid-2">
        <div className="border-card">
          <h3>Seus Dados</h3>
          <p><strong>Nome:</strong> {user?.name}</p>
          <p><strong>Email:</strong> {user?.email}</p>
          <p><strong>Escola ID:</strong> {user?.school_id}</p>
        </div>

        <div className="border-card">
          <h3>Suas Permissões ({permissions.length})</h3>
          <div style={{ maxHeight: '10rem', overflowY: 'auto' }}>
            <ul style={{ paddingLeft: '1.5rem' }}>
              {permissions.map((p) => (
                <li key={p}>{p}</li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};
