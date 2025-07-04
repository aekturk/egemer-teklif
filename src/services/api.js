// src/services/api.js

export const fetchAllData = async (apiUrl, nonce) => {
  try {
    const headers = {
      'X-WP-Nonce': nonce,
      'Content-Type': 'application/json',
    };

    const [productsRes, brandsRes, colorsRes] = await Promise.all([
      fetch(`${apiUrl}products`, { headers }),
      fetch(`${apiUrl}brands`, { headers }),
      fetch(`${apiUrl}colors`, { headers }),
    ]);

    const productsData = await productsRes.json();
    const brandsData = await brandsRes.json();
    const colorsData = await colorsRes.json();

    return { productsData, brandsData, colorsData, error: null };
  } catch (error) {
    console.error('Veri çekilirken hata oluştu:', error);
    return { productsData: [], brandsData: [], colorsData: [], error: error.message };
  }
};
