// src/App.js
import React, { useState, useEffect, useRef } from 'react';
import UserForm from './components/UserForm.js'; // .js eklendi
import SelectionGrid from './components/SelectionGrid.js'; // .js eklendi
import CountertopLayout from './components/CountertopLayout.js'; // .js eklendi
import CountertopExtras from './components/CountertopExtras.js'; // .js eklendi
import NewProductRequest from './components/NewProductRequest.js'; // .js eklendi
import OfferSummaryAndRegistration from './components/OfferSummaryAndRegistration.js'; // .js eklendi
import { hValueDisplayMapping } from './utils/constants.js'; // .js eklendi
import { calculateItemTotals, calculateAlternativeProductOffer } from './utils/calculations.js'; // .js eklendi
import { fetchAllData } from './services/api.js'; // .js eklendi

// ESLint hatasını önlemek ve yerel geliştirme/build için egemerOfferData'yı tanımla
// WordPress ortamında bu değer wp_localize_script tarafından üzerine yazılacaktır.
const egemerOfferData = typeof window.egemerOfferData !== 'undefined' ? window.egemerOfferData : {
  apiUrl: 'http://localhost/wordpress/wp-json/egemer/v1/', // Yerel geliştirme için mock API URL'si
  nonce: 'mock-nonce', // Mock nonce değeri
  ajaxUrl: 'http://localhost/wordpress/wp-admin/admin-ajax.php' // Yerel geliştirme için mock AJAX URL'si
};

function App() {
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState({
    // Step 1 - User Info (kept across multiple product additions)
    userName: '',
    userSurname: '',
    userPhone: '',
    userEmail: '',
    userAddress: '',
    taxOffice: '',
    taxNumber: '',
    // Product-specific data (resets when adding another product)
    selectedProductId: null,
    selectedBrandId: null,
    selectedColorId: null,
    selectedHValueKey: null,
    selectedHValuePrice: 0,
    depthCount: '',
    depthRows: [{ depthOption: '', mtul: 0.00 }],
    panelEnabled: 'Hayır',
    panelM2: 0.00,
    hoodPanelEnabled: 'Hayır',
    hoodPanelM2: 0.00,
    skirtingOption: '',
    skirtingMtul: 0.00,
    // Step 7 specific
    addAnotherProduct: 'Hayır',
  });

  const [allOfferDetails, setAllOfferDetails] = useState([]);
  const [submittedRegistrationNumber, setSubmittedRegistrationNumber] = useState('');
  const [submittedPdfUrl, setSubmittedPdfUrl] = useState('');

  const [products, setProducts] = useState([]);
  const [brands, setBrands] = useState([]);
  const [colors, setColors] = useState([]);

  const [filteredBrands, setFilteredBrands] = useState([]);
  const [filteredColors, setFilteredColors] = useState([]);
  const [colorHValues, setColorHValues] = useState([]);

  const formRef = useRef(null);

  // Fetch initial data from WordPress REST API on component mount
  useEffect(() => {
    const loadData = async () => {
      const { productsData, brandsData, colorsData, error } = await fetchAllData(egemerOfferData.apiUrl, egemerOfferData.nonce);
      if (error) {
        console.error('Veri çekilirken hata oluştu:', error);
        alert('Veri yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
      } else {
        setProducts(productsData);
        setBrands(brandsData);
        setColors(colorsData);
      }
    };
    loadData();
  }, []);

  // Calculate the base price from Step 5 for use in Step 6
  const getBasePriceFromStep5 = () => {
    return formData.selectedHValuePrice || 0;
  };

  // Step 2: Product Selection Effects
  useEffect(() => {
    if (formData.selectedProductId) {
      setFormData(prev => ({
        ...prev,
        selectedBrandId: null,
        selectedColorId: null,
        selectedHValueKey: null,
        selectedHValuePrice: 0,
      }));
      setFilteredBrands(brands.filter(brand => brand.product_id === formData.selectedProductId));
    } else {
      setFilteredBrands([]);
    }
  }, [formData.selectedProductId, brands]);

  // Step 3: Brand Selection Effects
  useEffect(() => {
    if (formData.selectedBrandId) {
      setFormData(prev => ({
        ...prev,
        selectedColorId: null,
        selectedHValueKey: null,
        selectedHValuePrice: 0,
      }));
      setFilteredColors(colors.filter(color => color.brand_id === formData.selectedBrandId));
    } else {
      setFilteredColors([]);
    }
  }, [formData.selectedBrandId, colors]);

  // Step 4: Color Selection Effects
  useEffect(() => {
    if (formData.selectedColorId) {
      const selectedColor = colors.find(c => c.id === formData.selectedColorId);
      if (selectedColor) {
        const hValues = Object.keys(hValueDisplayMapping)
          .map(key => ({
            key,
            value: selectedColor[key],
            label: hValueDisplayMapping[key]
          }))
          .filter(h => h.value !== null && h.value !== undefined);

        setColorHValues(hValues);
        setFormData(prev => ({
          ...prev,
          selectedHValueKey: null,
          selectedHValuePrice: 0,
        }));
      }
    } else {
      setColorHValues([]);
    }
  }, [formData.selectedColorId, colors]);

  // Step 5: Tezgah Düzeni Price Update
  useEffect(() => {
    if (formData.selectedColorId && formData.selectedHValueKey) {
      const selectedColor = colors.find(c => c.id === formData.selectedColorId);
      if (selectedColor && selectedColor[formData.selectedHValueKey] !== undefined) {
        setFormData(prev => ({
          ...prev,
          selectedHValuePrice: selectedColor[formData.selectedHValueKey],
        }));
      }
    } else {
      setFormData(prev => ({ ...prev, selectedHValuePrice: 0 }));
    }
  }, [formData.selectedColorId, formData.selectedHValueKey, colors]);

  // EFFECT: Scroll to top when currentStep changes
  useEffect(() => {
    if (formRef.current) {
      formRef.current.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  }, [currentStep]);

  // Navigation Handlers
  const handleNext = () => {
    switch (currentStep) {
      case 1:
        if (!formData.userName || !formData.userSurname || !formData.userPhone || !formData.userEmail || !formData.userAddress) {
          alert('Lütfen tüm zorunlu alanları doldurun.');
          return;
        }
        break;
      case 2:
        if (!formData.selectedProductId) {
          alert('Lütfen bir ürün seçin.');
          return;
        }
        break;
      case 3:
        if (!formData.selectedBrandId) {
          alert('Lütfen bir marka seçin.');
          return;
        }
        break;
      case 4:
        if (!formData.selectedColorId) {
          alert('Lütfen bir renk seçin.');
          return;
        }
        break;
      case 5:
        if (!formData.selectedHValueKey) {
          alert('Lütfen bir tezgah kalınlığı seçin.');
          return;
        }
        break;
      default:
        console.warn("Unexpected step in handleNext:", currentStep);
        break;
    }
    setCurrentStep(prev => prev + 1);
  };

  const handlePrev = () => {
    setCurrentStep(prev => prev - 1);
  };

  // Generic form input change handler
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  // Handler for image/item selection in grids
  const handleGridItemClick = (type, id) => {
    switch (type) {
      case 'product':
        setFormData(prev => ({ ...prev, selectedProductId: id }));
        break;
      case 'brand':
        setFormData(prev => ({ ...prev, selectedBrandId: id }));
        break;
      case 'color':
        setFormData(prev => ({ ...prev, selectedColorId: id }));
        break;
      default:
        break;
    }
  };

  // Handler for quantity inputs (MTÜL/M2) that can be typed into or incremented/decremented
  const handleQuantityInputChange = (section, index, value) => {
    const cleanValue = String(value).replace(',', '.');
    const numericValue = cleanValue === '' ? '' : parseFloat(cleanValue);

    setFormData(prev => {
      const newFormData = { ...prev };
      if (section === 'depth') {
        const newRows = [...newFormData.depthRows];
        newRows[index].mtul = isNaN(numericValue) ? 0.00 : numericValue;
        newFormData.depthRows = newRows;
      } else if (section === 'panel') {
        newFormData.panelM2 = isNaN(numericValue) ? 0.00 : numericValue;
      } else if (section === 'hoodPanel') {
        newFormData.hoodPanelM2 = isNaN(numericValue) ? 0.00 : numericValue;
      } else if (section === 'skirting') {
        newFormData.skirtingMtul = isNaN(numericValue) ? 0.00 : numericValue;
      }
      return newFormData;
    });
  };

  // Handler for MTÜL / M2 increment/decrement buttons
  const handleQuantityButtonClick = (section, index, delta) => {
    setFormData(prev => {
      const newFormData = { ...prev };
      if (section === 'depth') {
        const newRows = [...newFormData.depthRows];
        const currentMtul = parseFloat(newRows[index].mtul) || 0;
        const newValue = (currentMtul + delta * 0.01).toFixed(2);
        newRows[index].mtul = parseFloat(newValue);
        newFormData.depthRows = newRows;
      } else if (section === 'panel') {
        const currentM2 = parseFloat(newFormData.panelM2) || 0;
        const newValue = (currentM2 + delta * 0.01).toFixed(2);
        newFormData.panelM2 = parseFloat(newValue);
      } else if (section === 'hoodPanel') {
        const currentM2 = parseFloat(newFormData.hoodPanelM2) || 0;
        const newValue = (currentM2 + delta * 0.01).toFixed(2);
        newFormData.hoodPanelM2 = parseFloat(newValue);
      } else if (section === 'skirting') {
        const currentMtul = parseFloat(newFormData.skirtingMtul) || 0;
        const newValue = (currentMtul + delta * 0.01).toFixed(2);
        newFormData.skirtingMtul = parseFloat(newValue);
      }
      return newFormData;
    });
  };

  // Handler for Derinlik Bölümü dropdown count
  const handleDepthCountChange = (e) => {
    const count = parseInt(e.target.value) || 0;
    setFormData(prev => {
      let newDepthRows = [...prev.depthRows];
      if (count > newDepthRows.length) {
        for (let i = newDepthRows.length; i < count; i++) {
          newDepthRows.push({ depthOption: '', mtul: 0.00 });
        }
      } else if (count < newDepthRows.length) {
        newDepthRows = newDepthRows.slice(0, count);
      }
      return { ...prev, depthCount: e.target.value, depthRows: newDepthRows };
    });
  };

  // Handler for individual depth row changes
  const handleDepthRowChange = (index, name, value) => {
    setFormData(prev => {
      const newRows = [...prev.depthRows];
      newRows[index] = { ...newRows[index], [name]: value };
      return { ...prev, depthRows: newRows };
    });
  };

  const finalizeCurrentProductOffer = () => {
    const basePrice = getBasePriceFromStep5();

    const {
        calculatedDepthRows,
        panelCalculated,
        hoodPanelCalculated,
        skirtingCalculated,
        currentProductTotalPrice
    } = calculateItemTotals(
        basePrice,
        formData.depthRows,
        formData.panelEnabled,
        formData.panelM2,
        formData.hoodPanelEnabled,
        formData.hoodPanelM2,
        formData.skirtingOption,
        formData.skirtingMtul
    );

    const productOfferDetail = {
      selectedProduct: products.find(p => p.id === formData.selectedProductId)?.name || 'N/A',
      selectedBrand: brands.find(b => b.id === formData.selectedBrandId)?.name || 'N/A',
      selectedColor: colors.find(c => c.id === formData.selectedColorId)?.name || 'N/A',
      selectedHValue: {
        key: formData.selectedHValueKey,
        label: hValueDisplayMapping[formData.selectedHValueKey] || formData.selectedHValueKey,
        price: formData.selectedHValuePrice
      },
      depthSection: {
        count: parseInt(formData.depthCount) || 0,
        rows: calculatedDepthRows
      },
      panelSection: panelCalculated,
      hoodPanelSection: hoodPanelCalculated,
      skirtingSection: skirtingCalculated,
      totalPrice: currentProductTotalPrice
    };

    setAllOfferDetails(prev => [...prev, productOfferDetail]);
  };


  const handleSubmit = async (e) => {
    e.preventDefault();

    finalizeCurrentProductOffer();

    if (typeof egemerOfferData === 'undefined' || !egemerOfferData.ajaxUrl || !egemerOfferData.nonce) {
      console.error('egemerOfferData not found for AJAX submission.');
      alert('Form gönderilirken bir hata oluştu (yapılandırma eksik).');
      return;
    }

    setCurrentStep(7);
  };

  const handleSaveAndFinish = async () => {
    const grandTotalOfferPrice = allOfferDetails.reduce((sum, item) => sum + item.totalPrice, 0);

    const dataToSend = new FormData();
    dataToSend.append('action', 'egemer_submit_offer');
    dataToSend.append('nonce', egemerOfferData.nonce);
    dataToSend.append('userName', formData.userName);
    dataToSend.append('userSurname', formData.userSurname);
    dataToSend.append('userPhone', formData.userPhone);
    dataToSend.append('userEmail', formData.userEmail);
    dataToSend.append('userAddress', formData.userAddress);
    dataToSend.append('taxOffice', formData.taxOffice);
    dataToSend.append('taxNumber', formData.taxNumber);
    dataToSend.append('offerData', JSON.stringify(allOfferDetails));
    dataToSend.append('grandTotalOfferPrice', grandTotalOfferPrice.toFixed(2));

    try {
      const response = await fetch(egemerOfferData.ajaxUrl, {
        method: 'POST',
        body: dataToSend,
      });

      const result = await response.json();

      if (result.success) {
        alert('Teklifiniz başarıyla gönderildi! Teşekkür ederiz.');
        setSubmittedRegistrationNumber(result.data.registration_number);
        setSubmittedPdfUrl(result.data.pdf_url);
        setCurrentStep(8);
      } else {
        alert(`Teklif gönderilirken hata: ${result.data || 'Bilinmeyen Hata'}`);
        console.error('AJAX Error:', result.data);
      }
    } catch (error) {
      console.error('Network or Parse Error:', error);
      alert('Form gönderilirken bir ağ hatası oluştu. Lütfen internet bağlantınızı kontrol edin.');
    }
  };

  const handleAddAnotherProduct = () => {
    setFormData(prev => ({
      ...prev,
      selectedProductId: null,
      selectedBrandId: null,
      selectedColorId: null,
      selectedHValueKey: null,
      selectedHValuePrice: 0,
      depthCount: '',
      depthRows: [{ depthOption: '', mtul: 0.00 }],
      panelEnabled: 'Hayır',
      panelM2: 0.00,
      hoodPanelEnabled: 'Hayır',
      hoodPanelM2: 0.00,
      skirtingOption: '',
      skirtingMtul: 0.00,
      addAnotherProduct: 'Hayır',
    }));
    setCurrentStep(2);
  };

  const resetForm = () => {
    setFormData({
      userName: '', userSurname: '', userPhone: '', userEmail: '', userAddress: '', taxOffice: '', taxNumber: '',
      selectedProductId: null, selectedBrandId: null, selectedColorId: null, selectedHValueKey: null, selectedHValuePrice: 0,
      depthCount: '', depthRows: [{ depthOption: '', mtul: 0.00 }],
      panelEnabled: 'Hayır', panelM2: 0.00,
      hoodPanelEnabled: 'Hayır', hoodPanelM2: 0.00,
      skirtingOption: '', skirtingMtul: 0.00,
      addAnotherProduct: 'Hayır',
    });
    setAllOfferDetails([]);
    setSubmittedRegistrationNumber('');
    setSubmittedPdfUrl('');
  };

  const getStepTitle = () => {
    switch (currentStep) {
      case 1: return 'Kullanıcı Bilgisi';
      case 2: return 'Ürün Seçimi';
      case 3: return 'Marka Seçimi';
      case 4: return 'Renk Seçimi';
      case 5: return 'Tezgah Düzeni';
      case 6: return 'Tezgah Ekstra';
      case 7: return 'Yeni Ürün İsteği';
      case 8: return 'Teklif Özeti ve Kayıt';
      default: return '';
    }
  };

  const renderHeader = () => (
    <div className="mb-8">
      <h2 className="text-gray-900 mb-4 text-center">
        <span className="text-sm sm:text-base md:text-lg font-normal mr-2">Adım {currentStep} / 8 -</span>
        <span className="text-2xl sm:text-3xl md:text-4xl font-bold">{getStepTitle()}</span>
      </h2>
      <div className="w-full bg-gray-200 rounded-full h-2.5">
        <div className="bg-green-500 h-2.5 rounded-full transition-all duration-500 ease-in-out" style={{ width: `${(currentStep / 8) * 100}%` }}></div>
      </div>
    </div>
  );

  const renderStepContent = () => {
    const basePrice = getBasePriceFromStep5();

    switch (currentStep) {
      case 1:
        return <UserForm formData={formData} handleChange={handleChange} />;
      case 2:
        return (
          <SelectionGrid
            title="Lütfen bir ürün seçin:"
            items={products}
            selectedItemId={formData.selectedProductId}
            onItemClick={(id) => handleGridItemClick('product', id)}
            noItemsMessage="Ürünler yükleniyor veya bulunamadı..."
          />
        );
      case 3:
        return (
          <SelectionGrid
            title="Lütfen bir marka seçin:"
            items={filteredBrands}
            selectedItemId={formData.selectedBrandId}
            onItemClick={(id) => handleGridItemClick('brand', id)}
            noItemsMessage="Önceki adımda seçtiğiniz ürün için marka bulunmamaktadır."
          />
        );
      case 4:
        return (
          <SelectionGrid
            title="Lütfen bir renk seçin:"
            items={filteredColors}
            selectedItemId={formData.selectedColorId}
            onItemClick={(id) => handleGridItemClick('color', id)}
            noItemsMessage="Önceki adımda seçtiğiniz marka için renk bulunmamaktadır."
          />
        );
      case 5:
        return (
          <CountertopLayout
            selectedHValueKey={formData.selectedHValueKey}
            selectedHValuePrice={formData.selectedHValuePrice}
            colorHValues={colorHValues}
            handleChange={handleChange}
          />
        );
      case 6:
        return (
          <CountertopExtras
            basePrice={basePrice}
            formData={formData}
            handleChange={handleChange}
            handleQuantityInputChange={handleQuantityInputChange}
            handleQuantityButtonClick={handleQuantityButtonClick}
            handleDepthCountChange={handleDepthCountChange}
            handleDepthRowChange={handleDepthRowChange}
          />
        );
      case 7:
        return (
          <NewProductRequest
            formData={formData}
            handleChange={handleChange}
            allOfferDetails={allOfferDetails}
            brands={brands}
            colors={colors}
            hValueDisplayMapping={hValueDisplayMapping}
            calculateAlternativeProductOffer={calculateAlternativeProductOffer}
          />
        );
      case 8:
        return (
          <OfferSummaryAndRegistration
            formData={formData}
            allOfferDetails={allOfferDetails}
            submittedRegistrationNumber={submittedRegistrationNumber}
            submittedPdfUrl={submittedPdfUrl}
            resetForm={resetForm}
            setCurrentStep={setCurrentStep}
          />
        );
      default:
        return null;
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 py-10 px-4 sm:px-6 lg:px-8 font-sans">
      <div ref={formRef} className="lg:max-w-screen-lg xl:max-w-screen-xl mx-auto bg-white p-8 rounded-xl shadow-lg" id="egemer-offer-root-inner-wrapper">
        {renderHeader()}

        <form onSubmit={handleSubmit} className="space-y-6">
          {renderStepContent()}

          {/* Navigation Buttons */}
          <div className="flex justify-between items-center mt-8 px-4 flex-nowrap">
            {currentStep > 1 && currentStep < 7 && (
              <button
                type="button"
                onClick={handlePrev}
                data-button-type="prev"
                className="flex-shrink-0 inline-flex items-center justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200"
              >
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 0 011.414 0z" clipRule="evenodd" />
                </svg>
                Geri
              </button>
            )}

            {currentStep < 6 && (
              <button
                type="button"
                onClick={handleNext}
                data-button-type="next"
                className="ml-auto flex-shrink-0 inline-flex items-center justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-base font-medium text-white transition-all duration-200"
              >
                İleri
                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 0 011.414-1.414l4 4a1 0 010 1.414l-4 4a1 0 01-1.414 0z" clipRule="evenodd" />
                </svg>
              </button>
            )}

            {currentStep === 6 && (
              <button
                type="submit"
                data-button-type="submit"
                className="ml-auto flex-shrink-0 inline-flex items-center justify-center py-2 px-6 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200"
              >
                Teklifi Oluştur
              </button>
            )}

            {currentStep === 7 && formData.addAnotherProduct === 'Hayır' && (
                <button
                    type="button"
                    onClick={handleSaveAndFinish}
                    className="ml-auto bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-md shadow-md transition-all"
                >
                    Kaydet ve Bitir
                </button>
            )}

            {currentStep === 7 && formData.addAnotherProduct === 'Evet' && (
                <button
                    type="button"
                    onClick={handleAddAnotherProduct}
                    className="ml-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-md shadow-md transition-all"
                >
                    Yeni Ürün Ekle (Devam Et)
                </button>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}

export default App;
