import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '@/lib/axios';
import { useAuthStore } from '@/stores/authStore';
import { LoginResponse } from '@/types/auth';

export const Login: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  
  const setAuth = useAuthStore((state) => state.setAuth);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      // CSRF if needed by sanctum, we can get it later or skip it if using API tokens
      const { data } = await api.post<LoginResponse>('/auth/login', {
        email,
        password,
      });
      
      setAuth(data.data);
      navigate('/');
    } catch (err: any) {
      if (err.response?.status === 422) {
        setError(err.response.data.message || 'Dados inválidos.');
      } else {
        setError('Ocorreu um erro ao iniciar sessão. Verifique as suas credenciais.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      {error && (
        <div className="alert-error">
          {error}
        </div>
      )}
      
      <div className="form-group">
        <label className="form-label">Email</label>
        <div>
          <input
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="form-input"
          />
        </div>
      </div>

      <div className="form-group">
        <label className="form-label">Password</label>
        <div>
          <input
            type="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="form-input"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          disabled={isLoading}
          className="btn-primary"
        >
          {isLoading ? 'A iniciar...' : 'Entrar'}
        </button>
      </div>
    </form>
  );
};
