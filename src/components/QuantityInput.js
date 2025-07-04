// src/components/QuantityInput.js
import React from 'react';

const QuantityInput = ({ id, value, onChange, onMinus, onPlus, disabled = false }) => {
  return (
    <div className="flex items-center w-full text-xs">
      <button type="button" onClick={onMinus} disabled={disabled}
        className="flex-shrink-0 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        style={{ boxSizing: 'border-box', lineHeight: '1', padding: '0.25rem 0.5rem', borderRadius: '0.25rem 0 0 0.25rem' }}>
        -
      </button>
      <input
        type="text"
        id={id}
        value={typeof value === 'number' ? value.toFixed(2).replace('.', ',') : (value || '').replace('.', ',')}
        onChange={onChange}
        disabled={disabled}
        className="text-center border-t border-b border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50
                   [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        style={{ boxSizing: 'border-box', flexGrow: '1', maxWidth: '80px', padding: '0.25rem 0.1rem' }}
      />
      <button type="button" onClick={onPlus} disabled={disabled}
        className="flex-shrink-0 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        style={{ boxSizing: 'border-box', lineHeight: '1', padding: '0.25rem 0.5rem', borderRadius: '0 0.25rem 0.25rem 0' }}>
        +
      </button>
    </div>
  );
};

export default QuantityInput;
