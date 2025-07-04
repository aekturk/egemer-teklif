// src/components/UserForm.js
import React from 'react';

const UserForm = ({ formData, handleChange }) => {
  return (
    <div className="space-y-4">
      <div>
        <label htmlFor="userName" className="block text-sm font-medium text-gray-700">Adı <span className="text-red-500">*</span></label>
        <input type="text" id="userName" name="userName" value={formData.userName} onChange={handleChange} required
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Adınızı girin" // Placeholder eklendi
        />
      </div>
      <div>
        <label htmlFor="userSurname" className="block text-sm font-medium text-gray-700">Soyadı <span className="text-red-500">*</span></label>
        <input type="text" id="userSurname" name="userSurname" value={formData.userSurname} onChange={handleChange} required
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Soyadınızı girin" // Placeholder eklendi
        />
      </div>
      <div>
        <label htmlFor="userPhone" className="block text-sm font-medium text-gray-700">Telefon Numarası <span className="text-red-500">*</span></label>
        <input type="tel" id="userPhone" name="userPhone" value={formData.userPhone} onChange={handleChange} required
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Örn: 5xx xxx xx xx" // Placeholder eklendi
        />
      </div>
      <div>
        <label htmlFor="userEmail" className="block text-sm font-medium text-gray-700">E-Posta <span className="text-red-500">*</span></label>
        <input type="email" id="userEmail" name="userEmail" value={formData.userEmail} onChange={handleChange} required
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="mail@example.com" // Placeholder eklendi
        />
      </div>
      <div>
        <label htmlFor="userAddress" className="block text-sm font-medium text-gray-700">Adresi <span className="text-red-500">*</span></label>
        <textarea id="userAddress" name="userAddress" value={formData.userAddress} onChange={handleChange} rows="3" required
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Açık adresinizi girin" // Placeholder eklendi
        ></textarea>
      </div>
      <div>
        <label htmlFor="taxOffice" className="block text-sm font-medium text-gray-700">Vergi Dairesi (İsteğe Bağlı)</label>
        <input type="text" id="taxOffice" name="taxOffice" value={formData.taxOffice} onChange={handleChange}
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Vergi Dairesi adını girin" // Placeholder eklendi
        />
      </div>
      <div>
        <label htmlFor="taxNumber" className="block text-sm font-medium text-gray-700">Vergi Numarası (İsteğe Bağlı)</label>
        <input type="text" id="taxNumber" name="taxNumber" value={formData.taxNumber} onChange={handleChange}
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          placeholder="Vergi numaranızı girin" // Placeholder eklendi
        />
      </div>
    </div>
  );
};

export default UserForm;
