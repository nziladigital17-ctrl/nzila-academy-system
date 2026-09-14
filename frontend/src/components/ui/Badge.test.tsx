import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import { Badge } from './Badge';

describe('Badge component', () => {
  it('should render children correctly', () => {
    render(<Badge variant="success">Em Curso</Badge>);
    expect(screen.getByText('Em Curso')).toBeInTheDocument();
  });

  it('should apply the correct variant class for success', () => {
    render(<Badge variant="success">Success</Badge>);
    const badge = screen.getByText('Success');
    expect(badge).toHaveClass('nz-badge', 'nz-badge--success');
  });

  it('should apply the correct variant class for neutral', () => {
    render(<Badge variant="neutral">Neutral</Badge>);
    const badge = screen.getByText('Neutral');
    expect(badge).toHaveClass('nz-badge', 'nz-badge--neutral');
  });

  it('should apply the correct variant class for warning', () => {
    render(<Badge variant="warning">Warning</Badge>);
    const badge = screen.getByText('Warning');
    expect(badge).toHaveClass('nz-badge', 'nz-badge--warning');
  });
});
