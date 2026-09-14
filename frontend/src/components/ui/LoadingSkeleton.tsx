import React from 'react';

interface LoadingSkeletonProps {
  rows?: number;
  columns?: number;
  variant?: 'table' | 'cards' | 'text';
}

export const LoadingSkeleton: React.FC<LoadingSkeletonProps> = ({ rows = 5, columns = 4, variant = 'table' }) => {
  if (variant === 'cards') {
    return (
      <div className="nz-metric-grid">
        {Array.from({ length: 4 }).map((_, i) => (
          <div key={i} className="nz-skeleton nz-skeleton--card" />
        ))}
      </div>
    );
  }

  if (variant === 'text') {
    return (
      <div>
        <div className="nz-skeleton nz-skeleton--title" />
        {Array.from({ length: rows }).map((_, i) => (
          <div key={i} className="nz-skeleton nz-skeleton--text" style={{ width: `${70 + Math.random() * 30}%` }} />
        ))}
      </div>
    );
  }

  return (
    <div className="nz-table-container">
      <table className="nz-table">
        <thead>
          <tr>
            {Array.from({ length: columns }).map((_, i) => (
              <th key={i}><div className="nz-skeleton nz-skeleton--text" style={{ width: '80px' }} /></th>
            ))}
          </tr>
        </thead>
        <tbody>
          {Array.from({ length: rows }).map((_, rowIdx) => (
            <tr key={rowIdx}>
              {Array.from({ length: columns }).map((_, colIdx) => (
                <td key={colIdx}>
                  <div className="nz-skeleton nz-skeleton--text" style={{ width: `${60 + Math.random() * 40}%` }} />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
