import React from 'react';
import { Modal } from './Modal';

interface ConfirmDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  confirmLabel?: string;
  cancelLabel?: string;
  variant?: 'danger' | 'warning';
  isLoading?: boolean;
}

export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title,
  message,
  confirmLabel = 'Confirmar',
  cancelLabel = 'Cancelar',
  variant = 'danger',
  isLoading = false,
}) => {
  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title=""
      footer={
        <>
          <button
            className="nz-btn nz-btn--secondary"
            onClick={onClose}
            disabled={isLoading}
            type="button"
          >
            {cancelLabel}
          </button>
          <button
            className={`nz-btn ${variant === 'danger' ? 'nz-btn--danger' : 'nz-btn--primary'}`}
            onClick={onConfirm}
            disabled={isLoading}
            type="button"
          >
            {isLoading ? 'A processar...' : confirmLabel}
          </button>
        </>
      }
    >
      <div className={`nz-confirm__icon nz-confirm__icon--${variant}`}>
        {variant === 'danger' ? '⚠' : '⚡'}
      </div>
      <div className="nz-confirm__title">{title}</div>
      <div className="nz-confirm__message">{message}</div>
    </Modal>
  );
};
