import React from 'react';

interface FormFieldProps {
  label: string;
  htmlFor: string;
  error?: string;
  required?: boolean;
  children: React.ReactNode;
  className?: string;
}

export const FormField: React.FC<FormFieldProps> = ({ label, htmlFor, error, required, children, className }) => {
  return (
    <div className={`nz-form-group ${className ?? ''}`}>
      <label className="nz-form-label" htmlFor={htmlFor}>
        {label}
        {required && <span className="nz-form-required">*</span>}
      </label>
      {children}
      {error && <span className="nz-form-error" role="alert">{error}</span>}
    </div>
  );
};
