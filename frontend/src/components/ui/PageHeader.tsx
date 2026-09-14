import React from 'react';

interface PageHeaderProps {
  title: string;
  subtitle?: string;
  actions?: React.ReactNode;
}

export const PageHeader: React.FC<PageHeaderProps> = ({ title, subtitle, actions }) => {
  return (
    <div className="nz-page-header">
      <div className="nz-page-header__info">
        <h1 className="nz-page-header__title">{title}</h1>
        {subtitle && <p className="nz-page-header__subtitle">{subtitle}</p>}
      </div>
      {actions && (
        <div className="nz-page-header__actions">
          {actions}
        </div>
      )}
    </div>
  );
};
