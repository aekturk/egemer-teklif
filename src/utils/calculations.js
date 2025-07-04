// src/utils/calculations.js
import { depthMultipliers, skirtingDividers, hValueDisplayMapping } from './constants.js'; // .js eklendi

// Helper to get ISO week number (used for generating Kayıt Numarası)
export function getWeekNumber(d) {
    d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    var weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    return weekNo;
}

// Function to calculate all totals for a single product item
export const calculateItemTotals = (basePrice, depthRows, panelEnabled, panelM2, hoodPanelEnabled, hoodPanelM2, skirtingOption, skirtingMtul) => {
    const calculatedDepthRows = depthRows.map(row => {
        const multiplier = depthMultipliers[row.depthOption] || 0;
        const unitPrice = basePrice * multiplier;
        const mtulValue = parseFloat(row.mtul) || 0;
        const totalPrice = unitPrice * mtulValue;
        return { depthOption: row.depthOption, mtul: mtulValue, unitPrice, totalPrice };
    });

    const panelCalculated = (() => {
        if (panelEnabled === 'Evet') {
            const unitPrice = basePrice * 1.25;
            const panelM2Value = parseFloat(panelM2) || 0;
            const totalPrice = unitPrice * panelM2Value;
            return { enabled: true, m2: panelM2Value, unitPrice, totalPrice };
        }
        return { enabled: false, m2: 0, unitPrice: 0, totalPrice: 0 };
    })();

    const hoodPanelCalculated = (() => {
        if (hoodPanelEnabled === 'Evet') {
            const unitPrice = basePrice * 1.25;
            const hoodPanelM2Value = parseFloat(hoodPanelM2) || 0;
            const totalPrice = unitPrice * hoodPanelM2Value;
            return { enabled: true, m2: hoodPanelM2Value, unitPrice, totalPrice };
        }
        return { enabled: false, m2: 0, unitPrice: 0, totalPrice: 0 };
    })();

    const skirtingCalculated = (() => {
        if (skirtingOption) {
            const divider = skirtingDividers[skirtingOption] || 1;
            const unitPrice = Math.round(basePrice / divider);
            const skirtingMtulValue = parseFloat(skirtingMtul) || 0;
            const totalPrice = unitPrice * skirtingMtulValue;
            return { option: skirtingOption, mtul: skirtingMtulValue, unitPrice, totalPrice };
        }
        return { option: '', mtul: 0, unitPrice: 0, totalPrice: 0 };
    })();

    const currentProductTotalPrice = calculatedDepthRows.reduce((sum, row) => sum + row.totalPrice, 0) +
                                   panelCalculated.totalPrice +
                                   hoodPanelCalculated.totalPrice +
                                   skirtingCalculated.totalPrice;

    return {
        calculatedDepthRows,
        panelCalculated,
        hoodPanelCalculated,
        skirtingCalculated,
        currentProductTotalPrice
    };
};

// Function to calculate alternative offer details (cheapest or most expensive color for the same H-value)
export const calculateAlternativeProductOffer = (originalItem, allBrands, allColors, hValueDisplayMapping, type) => {
    const originalHValueKey = originalItem.selectedHValue.key;
    const originalBrand = allBrands.find(b => b.name === originalItem.selectedBrand);

    if (!originalBrand || !originalHValueKey) {
        return null;
    }

    const relevantColorsForBrand = allColors.filter(color => color.brand_id === originalBrand.id);

    let alternativeBasePrice = originalItem.selectedHValue.price;
    let alternativeColorName = originalItem.selectedColor;
    
    const colorsWithHValue = relevantColorsForBrand.filter(colorOption =>
        colorOption[originalHValueKey] !== null &&
        colorOption[originalHValueKey] !== undefined &&
        typeof colorOption[originalHValueKey] === 'number'
    );

    if (colorsWithHValue.length > 0) {
        if (type === 'cheapest') {
            let minPrice = Infinity;
            let minColor = null;
            for (const colorOption of colorsWithHValue) {
                if (colorOption[originalHValueKey] < minPrice) {
                    minPrice = colorOption[originalHValueKey];
                    minColor = colorOption;
                }
            }
            if (minColor) {
                alternativeBasePrice = minPrice;
                alternativeColorName = minColor.name;
            }
        } else if (type === 'mostExpensive') {
            let maxPrice = -Infinity;
            let maxColor = null;
            for (const colorOption of colorsWithHValue) {
                if (colorOption[originalHValueKey] > maxPrice) {
                    maxPrice = colorOption[originalHValueKey];
                    maxColor = colorOption;
                }
            }
            if (maxColor) {
                alternativeBasePrice = maxPrice;
                alternativeColorName = maxColor.name;
            }
        }
    }

    const {
        calculatedDepthRows,
        panelCalculated,
        hoodPanelCalculated,
        skirtingCalculated,
        currentProductTotalPrice
    } = calculateItemTotals(
        alternativeBasePrice,
        originalItem.depthSection.rows,
        originalItem.panelSection.enabled ? 'Evet' : 'Hayır',
        originalItem.panelSection.m2,
        originalItem.hoodPanelSection.enabled ? 'Evet' : 'Hayır',
        originalItem.hoodPanelSection.m2,
        originalItem.skirtingSection.option,
        originalItem.skirtingSection.mtul
    );

    return {
        selectedProduct: originalItem.selectedProduct,
        selectedBrand: originalItem.selectedBrand,
        selectedColor: alternativeColorName,
        selectedHValue: {
            key: originalHValueKey,
            label: hValueDisplayMapping[originalHValueKey] || originalHValueKey,
            price: alternativeBasePrice
        },
        depthSection: {
            count: originalItem.depthSection.count,
            rows: calculatedDepthRows
        },
        panelSection: panelCalculated,
        hoodPanelSection: hoodPanelCalculated,
        skirtingSection: skirtingCalculated,
        totalPrice: currentProductTotalPrice
    };
};
