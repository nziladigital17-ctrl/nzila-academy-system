import React from 'react';

interface PaginationProps {
  currentPage: number;
  totalPages: number;
  total: number;
  from: number;
  to: number;
  onPageChange: (page: number) => void;
  itemLabel?: string;
}

export const Pagination: React.FC<PaginationProps> = ({
  currentPage,
  totalPages,
  total,
  from,
  to,
  onPageChange,
  itemLabel = 'registos',
}) => {
  if (totalPages <= 1) return null;

  const pages: (number | string)[] = [];
  const maxVisible = 5;

  if (totalPages <= maxVisible + 2) {
    for (let i = 1; i <= totalPages; i++) pages.push(i);
  } else {
    pages.push(1);
    if (currentPage > 3) pages.push('...');
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);
    for (let i = start; i <= end; i++) pages.push(i);
    if (currentPage < totalPages - 2) pages.push('...');
    pages.push(totalPages);
  }

  return (
    <div className="nz-pagination">
      <span>
        A mostrar {from}–{to} de {total} {itemLabel}
      </span>
      <div className="nz-pagination__buttons">
        <button
          className="nz-pagination__btn"
          disabled={currentPage <= 1}
          onClick={() => onPageChange(currentPage - 1)}
          aria-label="Página anterior"
          type="button"
        >
          ‹
        </button>
        {pages.map((page, idx) =>
          typeof page === 'string' ? (
            <span key={`ellipsis-${idx}`} className="nz-pagination__btn" style={{ cursor: 'default', border: 'none' }}>
              …
            </span>
          ) : (
            <button
              key={page}
              className={`nz-pagination__btn ${page === currentPage ? 'nz-pagination__btn--active' : ''}`}
              onClick={() => onPageChange(page)}
              aria-current={page === currentPage ? 'page' : undefined}
              type="button"
            >
              {page}
            </button>
          )
        )}
        <button
          className="nz-pagination__btn"
          disabled={currentPage >= totalPages}
          onClick={() => onPageChange(currentPage + 1)}
          aria-label="Página seguinte"
          type="button"
        >
          ›
        </button>
      </div>
    </div>
  );
};
