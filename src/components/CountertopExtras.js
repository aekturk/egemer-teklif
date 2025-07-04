// src/components/CountertopExtras.js
import React from 'react';
import QuantityInput from './QuantityInput.js'; // .js eklendi
import { depthMultipliers, skirtingDividers } from '../utils/constants.js'; // .js eklendi // Import constants

const CountertopExtras = ({
  basePrice,
  formData,
  handleChange,
  handleQuantityInputChange,
  handleQuantityButtonClick,
  handleDepthCountChange,
  handleDepthRowChange,
}) => {
  // Calculated values for DEPTH SECTION
  const calculatedDepthRows = formData.depthRows.map(row => {
    const multiplier = depthMultipliers[row.depthOption] || 0;
    const unitPrice = basePrice * multiplier;
    const mtulValue = parseFloat(row.mtul) || 0;
    const totalPrice = unitPrice * mtulValue;
    return { ...row, unitPrice, totalPrice };
  });

  // Calculated values for PANEL SECTION
  const panelUnitPrice = formData.panelEnabled === 'Evet' ? basePrice * 1.25 : 0;
  const panelM2Value = parseFloat(formData.panelM2) || 0;
  const panelTotalPrice = panelUnitPrice * panelM2Value;

  // Calculated values for HOOD PANEL SECTION
  const hoodPanelUnitPrice = formData.hoodPanelEnabled === 'Evet' ? basePrice * 1.25 : 0;
  const hoodPanelM2Value = parseFloat(formData.hoodPanelM2) || 0;
  const hoodPanelTotalPrice = hoodPanelUnitPrice * hoodPanelM2Value;

  // Calculated values for SKIRTING SECTION
  const skirtingUnitPrice = formData.skirtingOption ? Math.round(basePrice / (skirtingDividers[formData.skirtingOption] || 1)) : 0;
  const skirtingMtulValue = parseFloat(formData.skirtingMtul) || 0;
  const skirtingTotalPrice = skirtingUnitPrice * skirtingMtulValue;

  return (
    <div>
      {/* DEPTH SECTION - Arka plan: #6b8bbf */}
      <div className="p-4 rounded-xl shadow-md" style={{ backgroundColor: '#6b8bbf' }}>
        <div className="pb-2 mb-4 rounded-md" style={{ backgroundColor: '#6b8bbf' }}>
          <h3 className="text-lg font-semibold text-gray-800 text-center py-2"><br />DERİNLİK BÖLÜMÜ</h3>
        </div>
        <div className="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
          <label htmlFor="depthCount" className="block text-sm font-medium text-gray-700 md:w-1/2">
            Projedeki farklı derinlik adedi?
          </label>
          <select
            name="depthCount"
            id="depthCount"
            value={formData.depthCount}
            onChange={handleDepthCountChange}
            className="mt-1 md:mt-0 block w-full md:w-1/4 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
            style={{ boxSizing: 'border-box' }}
          >
            <option value="">Seçim Yapın (Zorunlu Değil)</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
          </select>
        </div>
        {calculatedDepthRows.map((row, index) => (
          <div key={index} className="flex flex-wrap sm:flex-nowrap w-full py-3 items-center gap-x-3" style={{ display: 'flex', flexWrap: 'nowrap', borderTop: index > 0 ? '1px solid #e0e0e0' : 'none' }}>
            <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
              <label htmlFor={`depthOption-${index}`} className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Derinlik ({index + 1})</label>
              <select
                name="depthOption"
                id={`depthOption-${index}`}
                value={row.depthOption}
                onChange={(e) => handleDepthRowChange(index, e.target.name, e.target.value)}
                className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
                style={{ boxSizing: 'border-box' }}
              >
                <option value="">Seçiniz...</option>
                {Object.keys(depthMultipliers).map(option => (
                  <option key={option} value={option}>{option}</option>
                ))}
              </select>
            </div>
            <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1 items-center" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
              <label htmlFor={`mtul-${index}`} className="block lg:hidden text-sm font-medium text-gray-700 mb-1">MTÜL ({index + 1})</label>
              <QuantityInput
                id={`mtul-${index}`}
                value={row.mtul}
                onChange={(e) => handleQuantityInputChange('depth', index, e.target.value)}
                onMinus={() => handleQuantityButtonClick('depth', index, -1)}
                onPlus={() => handleQuantityButtonClick('depth', index, 1)}
              />
            </div>
            <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
              <label htmlFor={`unitPrice-${index}`} className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Birim Fiyatı ({index + 1}) (₺)</label>
              <input type="text" id={`unitPrice-${index}`} value={`${row.unitPrice.toLocaleString('tr-TR')}₺`} readOnly
                className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
                style={{ boxSizing: 'border-box' }}
              />
            </div>
            <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
              <label htmlFor={`totalPrice-${index}`} className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Toplam Fiyat ({index + 1}) (₺)</label>
              <input type="text" id={`totalPrice-${index}`} value={`${row.totalPrice.toLocaleString('tr-TR')}₺`} readOnly
                className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
                style={{ boxSizing: 'border-box' }}
              />
            </div>
          </div>
        ))}
      </div>
      <br />
      <div className="h-5"></div>
      <hr />
      <div className="h-5"></div>

      {/* PANEL SECTION - Arka plan: #a2aab8 */}
      <div className="p-4 rounded-xl shadow-md" style={{ backgroundColor: '#a2aab8' }}>
        <div className="pb-2 mb-4 rounded-md" style={{ backgroundColor: '#a2aab8' }}>
          <h3 className="text-lg font-semibold text-gray-800 text-center py-2"><br />PANEL BÖLÜMÜ</h3>
        </div>
        <div className="flex flex-wrap sm:flex-nowrap w-full py-3 items-center gap-x-3" style={{ display: 'flex', flexWrap: 'nowrap' }}>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="panelEnabled" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Panel Seçimi</label>
            <select
              name="panelEnabled"
              id="panelEnabled"
              value={formData.panelEnabled}
              onChange={handleChange}
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
              style={{ boxSizing: 'border-sizing' }}
            >
              <option value="Hayır">Hayır</option>
              <option value="Evet">Evet</option>
            </select>
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1 items-center" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="panelM2" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">M2</label>
            <QuantityInput
              id="panelM2"
              value={formData.panelM2}
              onChange={(e) => handleQuantityInputChange('panel', null, e.target.value)}
              onMinus={() => handleQuantityButtonClick('panel', null, -1)}
              onPlus={() => handleQuantityButtonClick('panel', null, 1)}
              disabled={formData.panelEnabled === 'Hayır'}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="panelUnitPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Birim Fiyatı (₺)</label>
            <input type="text" id="panelUnitPrice" value={`${panelUnitPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="panelTotalPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Toplam Fiyat (₺)</label>
            <input type="text" id="panelTotalPrice" value={`${panelTotalPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
        </div>
      </div>
      <br />
      <hr />
      <div className="h-5"></div>

      {/* HOOD PANEL SECTION - Arka plan: #6b8bbf */}
      <div className="h-5"></div>
      <div className="p-4 rounded-xl shadow-md" style={{ backgroundColor: '#6b8bbf' }}>
        <div className="pb-2 mb-4 rounded-md" style={{ backgroundColor: '#6b8bbf' }}>
          <h3 className="text-lg font-semibold text-gray-800 text-center py-2"><br />DAVLUMBAZ PANEL BÖLÜMÜ</h3>
        </div>
        <div className="flex flex-wrap sm:flex-nowrap w-full py-3 items-center gap-x-3" style={{ display: 'flex', flexWrap: 'nowrap' }}>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="hoodPanelEnabled" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Davlumbaz Panel Seçimi</label>
            <select
              name="hoodPanelEnabled"
              id="hoodPanelEnabled"
              value={formData.hoodPanelEnabled}
              onChange={handleChange}
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            >
              <option value="Hayır">Hayır</option>
              <option value="Evet">Evet</option>
            </select>
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1 items-center" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="hoodPanelM2" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">M2</label>
            <QuantityInput
              id="hoodPanelM2"
              value={formData.hoodPanelM2}
              onChange={(e) => handleQuantityInputChange('hoodPanel', null, e.target.value)}
              onMinus={() => handleQuantityButtonClick('hoodPanel', null, -1)}
              onPlus={() => handleQuantityButtonClick('hoodPanel', null, 1)}
              disabled={formData.hoodPanelEnabled === 'Hayır'}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="hoodPanelUnitPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Birim Fiyat (₺)</label>
            <input type="text" id="hoodPanelUnitPrice" value={`${hoodPanelUnitPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="hoodPanelTotalPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Toplam Fiyat (₺)</label>
            <input type="text" id="hoodPanelTotalPrice" value={`${hoodPanelTotalPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
        </div>
      </div>
      <br />
      <hr />
      <div className="h-5"></div>
      {/* SKIRTING SECTION - Arka plan: #a2aab8 */}
      <div className="h-5"></div>
      <div className="p-4 rounded-xl shadow-md" style={{ backgroundColor: '#a2aab8' }}>
        <div className="pb-2 mb-4 rounded-md" style={{ backgroundColor: '#a2aab8' }}>
          <h3 className="text-lg font-semibold text-gray-800 text-center py-2"><br />SÜPÜRGELİK BÖLÜMÜ</h3>
        </div>
        <div className="flex flex-wrap sm:flex-nowrap w-full py-3 items-center gap-x-3" style={{ display: 'flex', flexWrap: 'nowrap' }}>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="skirtingOption" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Süpürgelik Seçimi</label>
            <select
              name="skirtingOption"
              id="skirtingOption"
              value={formData.skirtingOption}
              onChange={handleChange}
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            >
              <option value="">Seçiniz...</option>
              {Object.keys(skirtingDividers).map(option => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1 items-center" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="skirtingMtul" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">MTÜL</label>
            <QuantityInput
              id="skirtingMtul"
              value={formData.skirtingMtul}
              onChange={(e) => handleQuantityInputChange('skirting', null, e.target.value)}
              onMinus={() => handleQuantityButtonClick('skirting', null, -1)}
              onPlus={() => handleQuantityButtonClick('skirting', null, 1)}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="skirtingUnitPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Birim Fiyat (₺)</label>
            <input type="text" id="skirtingUnitPrice" value={`${skirtingUnitPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
          <div className="flex flex-col mb-4 sm:mb-0 justify-center flex-1" style={{ flexBasis: '25%', minWidth: 'unset', padding: '0 4px' }}>
            <label htmlFor="skirtingTotalPrice" className="block lg:hidden text-sm font-medium text-gray-700 mb-1">Toplam Fiyat (₺)</label>
            <input type="text" id="skirtingTotalPrice" value={`${skirtingTotalPrice.toLocaleString('tr-TR')}₺`} readOnly
              className="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed text-sm text-gray-900"
              style={{ boxSizing: 'border-box' }}
            />
          </div>
        </div>
      </div>
      <br />
      <hr />
      <div className="h-5"></div>
    </div>
  );
};

export default CountertopExtras;
