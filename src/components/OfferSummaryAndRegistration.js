// src/components/OfferSummaryAndRegistration.js
import React from 'react';

const OfferSummaryAndRegistration = ({ formData, allOfferDetails, submittedRegistrationNumber, submittedPdfUrl, resetForm, setCurrentStep }) => {
  const registerDate = new Date().toLocaleDateString('tr-TR');
  const registerTime = new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });

  const grandTotalForFinalReview = allOfferDetails.reduce((sum, item) => sum + item.totalPrice, 0);

  return (
    <div className="py-10">
      <h3 className="text-2xl font-bold text-gray-800 mb-6 text-center">Teklif Özeti ve Kayıt</h3>

      <div className="bg-gray-100 p-6 rounded-lg shadow-md mb-8 relative">
        <div className="absolute top-4 right-4 text-right text-sm">
          <p><strong>Kayıt Tarihi:</strong> {registerDate}</p>
          <p><strong>Kayıt Saati:</strong> {registerTime}</p>
          <p><strong>Kayıt Numarası:</strong> {submittedRegistrationNumber}</p>
        </div>
        <h4 className="text-xl font-semibold text-gray-800 mb-4 text-left">Müşteri Bilgileri</h4>
        <div className="text-left space-y-1 text-sm">
          <p><strong>Adı Soyadı:</strong> {formData.userName} {formData.userSurname}</p>
          <p><strong>Telefon:</strong> {formData.userPhone}</p>
          <p><strong>E-Posta:</strong> {formData.userEmail}</p>
          <p><strong>Adres:</strong> {formData.userAddress}</p>
          {formData.taxOffice && <p><strong>Vergi Dairesi:</strong> {formData.taxOffice}</p>}
          {formData.taxNumber && <p><strong>Vergi Numarası:</strong> {formData.taxNumber}</p>}
        </div>
      </div>

      <div className="bg-white p-6 rounded-lg shadow-md mb-8">
        <h4 className="text-xl font-semibold text-gray-800 mb-4 text-center">Teklif Kalemleri</h4>
        {allOfferDetails.length === 0 ? (
          <p className="text-center text-red-500">Hiçbir ürün teklifi bulunamadı.</p>
        ) : (
          <div className="text-left space-y-4">
            {allOfferDetails.map((offer, idx) => (
              <div key={idx} className="border border-gray-200 p-4 rounded-md shadow-sm">
                <p className="font-bold text-blue-700 text-lg mb-2">Ürün {idx + 1}: {offer.selectedProduct}</p>
                <ul className="list-disc list-inside ml-4 text-sm text-gray-700 space-y-1">
                  <li>Marka: {offer.selectedBrand}</li>
                  <li>Renk: {offer.selectedColor}</li>
                  <li>Tezgah Kalınlığı: {offer.selectedHValue.label} (Birim Fiyat: {offer.selectedHValue.price.toLocaleString('tr-TR')}₺)</li>
                  {offer.depthSection.rows.map((row, rowIdx) => (
                    row.mtul > 0 && <li key={rowIdx}>Derinlik {row.depthOption}: {row.mtul.toFixed(2).replace('.', ',')} mtül (Toplam: {row.totalPrice.toLocaleString('tr-TR')}₺)</li>
                  ))}
                  {offer.panelSection.enabled && offer.panelSection.m2 > 0 && <li>Panel: {offer.panelSection.m2.toFixed(2).replace('.', ',')} m² (Toplam: {offer.panelSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                  {offer.hoodPanelSection.enabled && offer.hoodPanelSection.m2 > 0 && <li>Davlumbaz Panel: {offer.hoodPanelSection.m2.toFixed(2).replace('.', ',')} m² (Toplam: {offer.hoodPanelSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                  {offer.skirtingSection.option && offer.skirtingSection.mtul > 0 && <li>Süpürgelik: {offer.skirtingSection.mtul.toFixed(2).replace('.', ',')} mtül (Toplam: {offer.skirtingSection.totalPrice.toLocaleString('tr-TR')}₺)</li>}
                </ul>
                <p className="font-bold text-right text-gray-900 mt-2">Bu Ürün İçin Toplam: {offer.totalPrice.toLocaleString('tr-TR')}₺</p>
              </div>
            ))}
            <h4 className="text-2xl font-bold text-gray-900 mt-6 text-right">
              Genel Toplam Teklif Fiyatı: {grandTotalForFinalReview.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' })}
            </h4>
          </div>
        )}
      </div>

      <div className="flex flex-col sm:flex-row justify-center items-center gap-4 mt-8">
        <a
          href={submittedPdfUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-md shadow-md transition-all flex items-center justify-center"
        >
          <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fillRule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L10 11.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z" clipRule="evenodd" />
          </svg>
          Teklif Formunu İndir (PDF)
        </a>
        <button
          type="button"
          onClick={() => {
            resetForm();
            setCurrentStep(1);
          }}
          className="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-md shadow-md transition-all flex items-center justify-center"
        >
          Yeni Teklif Oluştur
        </button>
      </div>
    </div>
  );
};

export default OfferSummaryAndRegistration;
