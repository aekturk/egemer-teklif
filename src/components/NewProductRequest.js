// src/components/NewProductRequest.js
import React from 'react';
import { calculateAlternativeProductOffer, getWeekNumber } from '../utils/calculations.js'; // .js eklendi
import { hValueDisplayMapping } from '../utils/constants.js'; // .js eklendi

const NewProductRequest = ({ formData, handleChange, allOfferDetails, brands, colors }) => {
  const grandTotalForReview = allOfferDetails.reduce((sum, item) => sum + item.totalPrice, 0);

  const cheapestAlternativeOffers = allOfferDetails
    .map(item => calculateAlternativeProductOffer(item, brands, colors, hValueDisplayMapping, 'cheapest'))
    .filter(Boolean);

  const mostExpensiveAlternativeOffers = allOfferDetails
    .map(item => calculateAlternativeProductOffer(item, brands, colors, hValueDisplayMapping, 'mostExpensive'))
    .filter(Boolean);

  const grandTotalCheapestAlternative = cheapestAlternativeOffers.reduce((sum, item) => sum + item.totalPrice, 0);
  const grandTotalMostExpensiveAlternative = mostExpensiveAlternativeOffers.reduce((sum, item) => sum + item.totalPrice, 0);

  const renderOfferSummary = (offerItems, title, grandTotal) => (
    <div className="bg-gray-50 p-4 rounded-lg shadow-md h-full flex flex-col">
      <h4 className="text-xl font-semibold text-gray-800 mb-4 text-center">{title}</h4>
      {offerItems.length === 0 ? (
        <p className="text-center text-red-500">Henüz bir ürün eklenmedi veya alternatif bulunamadı.</p>
      ) : (
        <div className="text-left space-y-2 flex-grow overflow-y-auto pr-2">
          {offerItems.map((offer, idx) => (
            <div key={idx} className="border-b border-gray-300 pb-2 mb-2 last:border-b-0">
              <p className="font-semibold text-blue-700">Ürün {idx + 1}: {offer.selectedProduct} - {offer.selectedBrand} - {offer.selectedColor}</p>
              <ul className="list-disc list-inside ml-4 text-sm text-gray-700">
                <li>Tezgah Kalınlığı: {offer.selectedHValue.label}</li>
                {offer.depthSection.rows.map((row, rowIdx) => (
                  row.mtul > 0 && <li key={rowIdx}>Derinlik {row.depthOption}: {row.mtul.toFixed(2).replace('.', ',')} mtül (Toplam: {row.totalPrice.toLocaleString('tr-TR')}₺)</li>
                ))}
                {offer.panelSection.enabled && offer.panelSection.m2 > 0 && <li>Panel: {offer.panelSection.m2.toFixed(2).replace('.', ',')} m² (Toplam: {offer.panelSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                {offer.hoodPanelSection.enabled && offer.hoodPanelSection.m2 > 0 && <li>Davlumbaz Panel: {offer.hoodPanelSection.m2.toFixed(2).replace('.', ',')} m² (Toplam: {offer.hoodPanelSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                {offer.skirtingSection.option && offer.skirtingSection.mtul > 0 && <li>Süpürgelik: {offer.skirtingSection.mtul.toFixed(2).replace('.', ',')} mtül (Toplam: {offer.skirtingSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                <li className="font-bold text-gray-800">Bu Ürün İçin Toplam: {offer.totalPrice.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' })}</li>
              </ul>
            </div>
          ))}
        </div>
      )}
      <h4 className="text-2xl font-bold text-gray-900 mt-4 text-right pt-4 border-t border-gray-300">
        Toplam: {grandTotal.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' })}
      </h4>
    </div>
  );

  return (
    <div className="text-center py-10">
      <h3 className="text-2xl font-bold text-gray-800 mb-4">Yeni Ürün İsteği</h3>
      
      <label htmlFor="addAnotherProduct" className="block text-lg font-semibold text-gray-700 mb-2">
        Yeni bir ürün eklemek ister misiniz?
      </label>
      <select
        id="addAnotherProduct"
        name="addAnotherProduct"
        value={formData.addAnotherProduct}
        onChange={handleChange}
        className="block w-full max-w-xs mx-auto border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm text-gray-900"
      >
        <option value="Hayır">Hayır</option>
        <option value="Evet">Evet</option>
      </select>

      <div className="h-5"></div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
        <div className="bg-gray-50 p-4 rounded-lg shadow-md flex flex-col border-b md:border-b-0 md:border-r-2 md:border-gray-400">
          {renderOfferSummary(allOfferDetails, 'Mevcut Teklif Özeti', grandTotalForReview)}
        </div>

        <div className="bg-gray-50 p-4 rounded-lg shadow-md flex flex-col border-b md:border-b-0 md:border-r-2 md:border-gray-400">
          {renderOfferSummary(cheapestAlternativeOffers, 'En Ucuz Renk Alternatifi', grandTotalCheapestAlternative)}
        </div>

        <div className="bg-gray-50 p-4 rounded-lg shadow-md flex flex-col">
          {renderOfferSummary(mostExpensiveAlternativeOffers, 'En Pahalı Renk Alternatifi', grandTotalMostExpensiveAlternative)}
        </div>
      </div>
    </div>
  );
};

export default NewProductRequest;
