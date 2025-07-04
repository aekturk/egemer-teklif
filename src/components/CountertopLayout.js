// src/components/CountertopLayout.js
import React from 'react';

const CountertopLayout = ({ selectedHValueKey, selectedHValuePrice, colorHValues, handleChange }) => {
  return (
    <div className="space-y-4">
      <div className="flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <div className="flex-1 w-full sm:w-auto">
          <label className="block text-sm font-medium text-gray-700">TEZGAH ÖN KALINLIK (h)</label>
          <label htmlFor="hValueSelect" className="block text-sm font-medium text-gray-700">Lütfen seçim yapın <span className="text-red-500">*</span></label>
          <select
            id="hValueSelect"
            name="selectedHValueKey"
            value={selectedHValueKey || ''}
            onChange={handleChange}
            className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            style={{ boxSizing: 'border-box' }}
          >
            <option value="">Seçiniz...</option>
            {colorHValues.map(h => (
              <option key={h.key} value={h.key}>{h.label}</option>
            ))}
          </select>
        </div>
        <div className="flex-1 w-full sm:w-auto">
          <label htmlFor="hValuePrice" className="block text-sm font-medium text-gray-700">Seçilen Değer (₺)</label>
          <input
            type="text"
            id="hValuePrice"
            name="selectedHValuePriceDisplay"
            value={selectedHValuePrice ? `${selectedHValuePrice.toLocaleString('tr-TR')}₺` : ''}
            readOnly
            className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed sm:text-sm"
          />
        </div>
      </div>
    </div>
  );
};

export default CountertopLayout;
