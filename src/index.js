import React from 'react';
import ReactDOM from 'react-dom/client';
import './index.css'; // Tailwind CSS'i import edin
import App from './App';
import reportWebVitals from './reportWebVitals';

// React uygulamasının bağlanacağı div'i bul
const rootElement = document.getElementById('egemer-offer-root');

if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
} else {
  console.error('Egemer Teklif Eklentisi: #egemer-offer-root elementi bulunamadı. Lütfen kısa kodun bir sayfaya eklendiğinden emin olun.');
}

reportWebVitals();
