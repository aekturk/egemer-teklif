import React, { useState } from 'react';

const OfferForm = () => {
    const [errorMessage, setErrorMessage] = useState('');
    const [pdfUrl, setPdfUrl] = useState('');
    const [offerSuccess, setOfferSuccess] = useState(false);

    // Teklif gönderme fonksiyonu:
    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrorMessage('');
        setPdfUrl('');
        setOfferSuccess(false);

        const formData = new FormData();
        formData.append('action', 'egemer_submit_offer');
        formData.append('user_name', userName);
        formData.append('user_surname', userSurname);
        formData.append('user_email', userEmail);
        formData.append('user_phone', userPhone);
        formData.append('grand_total_price', grandTotalPrice);
        formData.append('offer_items', JSON.stringify(offerItems));
        // ...diğer alanlar...

        try {
            const response = await fetch(egemerOfferData.ajaxUrl, {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();
            if (result.success) {
                setOfferSuccess(true);
                if (result.data && result.data.pdf_url) {
                    setPdfUrl(result.data.pdf_url);
                }
            } else {
                setErrorMessage(result.data || "Bilinmeyen Hata");
            }
        } catch (err) {
            setErrorMessage("Sunucuya ulaşılamadı.");
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* ...form alanları... */}

            <button type="submit" className="button">
                Teklif Gönder
            </button>

            {/* Adım 8/8'de gösterilecek alan: */}
            {offerSuccess && pdfUrl && (
                <div className="offer-success">
                    <p>Teklifiniz başarıyla kaydedildi!</p>
                    <a href={pdfUrl} target="_blank" rel="noopener noreferrer" className="button">
                        PDF İndir
                    </a>
                </div>
            )}
            {errorMessage && (
                <div className="offer-error">
                    <p>{errorMessage}</p>
                </div>
            )}
        </form>
    );
};

export default OfferForm;