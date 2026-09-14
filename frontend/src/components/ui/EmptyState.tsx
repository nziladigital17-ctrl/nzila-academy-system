import React from 'react';

interface EmptyStateProps {
  icon?: string;
  title: string;
  description?: string;
  action?: React.ReactNode;
}

export const EmptyState: React.FC<EmptyStateProps> = ({ icon = '📋', title, description, action }) => {
  return (
    <div className="nz-empty-state">
      <div className="nz-empty-state__icon" aria-hidden="true">
        <span style={{ fontSize: '32px' }}>{icon}</span>
      </div>
      <h3 className="nz-empty-state__title">{title}</h3>
      {description && <p className="nz-empty-state__description">{description}</p>}
      {action}
    </div>
  );
};
