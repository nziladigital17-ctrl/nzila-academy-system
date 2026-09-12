import React from 'react';
import { Link } from 'react-router-dom';

export const NotFound: React.FC = () => (
  <div className="guest-container" style={{ alignItems: 'center' }}>
    <h1 className="error-title">404</h1>
    <p style={{ fontSize: '1.25rem', marginBottom: '2rem' }}>Página não encontrada</p>
    <Link to="/" className="text-link">Voltar à página inicial</Link>
  </div>
);

export const Forbidden: React.FC = () => (
  <div className="guest-container" style={{ alignItems: 'center' }}>
    <h1 className="error-title text-red">403</h1>
    <p style={{ fontSize: '1.25rem', marginBottom: '2rem' }}>Acesso Negado</p>
    <Link to="/" className="text-link">Voltar à página inicial</Link>
  </div>
);

export const ServerError: React.FC = () => (
  <div className="guest-container" style={{ alignItems: 'center' }}>
    <h1 className="error-title text-red">500</h1>
    <p style={{ fontSize: '1.25rem', marginBottom: '2rem' }}>Erro interno do servidor</p>
    <Link to="/" className="text-link">Tentar novamente</Link>
  </div>
);
