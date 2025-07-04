<?php
/**
 * Teklif detaylarından PDF için HTML içeriği oluşturur.
 * @param array $offer_details
 * @param array $user_data
 * @return string HTML içeriği
 */
function egemer_generate_offer_html($offer_details, $user_data) {
    ob_start();
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Egemer Teklif Detayı</title>
        <style>
            /* Genel Stil Ayarları */
            body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 10px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }

            /* Üst Bilgi (Logo ve Şirket Bilgileri) */
            .company-header {
                width: 100%;
                margin-bottom: 20px;
                overflow: hidden; /* Clearfix */
            }
            .company-logo {
                float: left;
                width: 150px; /* Logo genişliği */
                height: auto;
            }
            .company-info {
                float: right;
                text-align: right;
                font-size: 10px;
            }
            .company-info h1 {
                font-size: 16px;
                color: #2c3e50;
                margin: 0 0 5px 0;
            }
            .company-info p {
                margin: 0;
                line-height: 1.3;
            }

            /* Teklif Bilgileri Tablosu (Üst Kısım) */
            .offer-info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
                font-size: 9px;
            }
            .offer-info-table th, .offer-info-table td {
                border: 1px solid #ddd;
                padding: 6px 8px;
                text-align: left;
                vertical-align: top;
            }
            .offer-info-table th {
                background-color: #f0f8ff;
                font-weight: bold;
                width: 15%; /* Sütun başlıkları için genişlik */
            }
            .offer-info-table td {
                width: 35%; /* Veri sütunları için genişlik */
            }

            /* Müşteri Bilgileri */
            .customer-info-section {
                margin-bottom: 20px;
                border: 1px solid #e0e0e0;
                border-radius: 5px;
                padding: 10px;
                background-color: #fdfdfd;
            }
            .customer-info-section h3 {
                font-size: 14px;
                color: #34495e;
                margin-top: 0;
                margin-bottom: 10px;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            .customer-info-section p {
                margin: 3px 0;
                font-size: 10px;
            }
            .customer-info-section strong {
                display: inline-block;
                min-width: 80px;
                color: #555;
            }

            /* Teklif Kalemleri Tablosu */
            .items-table-container {
                margin-bottom: 30px;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .items-table th, .items-table td {
                border: 1px solid #ddd;
                padding: 7px 10px;
                text-align: left;
                font-size: 9px;
            }
            .items-table th {
                background: #e9f5ff;
                color: #2c3e50;
                font-weight: bold;
                text-transform: uppercase;
            }
            .items-table tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .items-table .text-right {
                text-align: right;
            }
            .item-group-header {
                background-color: #dbe9f5;
                color: #2c3e50;
                font-weight: bold;
                padding: 8px 10px;
                margin-top: 15px;
                margin-bottom: 5px;
                border-radius: 3px;
                font-size: 11px;
            }

            /* Toplamlar */
            .total-summary-table {
                width: 40%; /* Toplamlar tablosunun genişliği */
                float: right; /* Sağa hizala */
                border-collapse: collapse;
                margin-top: 20px;
            }
            .total-summary-table td {
                padding: 6px 10px;
                font-size: 11px;
                border: 1px solid #ddd;
            }
            .total-summary-table .total-label {
                background-color: #f0f0f0;
                font-weight: bold;
                text-align: right;
            }
            .total-summary-table .total-value {
                background-color: #f8f8f8;
                font-weight: bold;
                text-align: right;
                color: #2c3e50;
                font-size: 12px;
            }
            .grand-total-final {
                font-size: 16px !important;
                color: #1a4d8f !important;
                background-color: #e6f0fa !important;
            }

            /* Açıklamalar ve Dipnotlar */
            .notes-section {
                clear: both; /* Toplam tablosunun altından başla */
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px dashed #ccc;
                font-size: 9px;
                color: #555;
            }
            .notes-section h4 {
                font-size: 12px;
                color: #34495e;
                margin-bottom: 10px;
            }
            .notes-section ul {
                list-style: disc;
                margin-left: 20px;
                padding: 0;
            }
            .notes-section li {
                margin-bottom: 5px;
            }

            /* İmza Alanları */
            .signature-section {
                margin-top: 40px;
                overflow: hidden;
            }
            .signature-box {
                width: 48%;
                float: left;
                border: 1px solid #ccc;
                padding: 15px;
                text-align: center;
                box-sizing: border-box;
                border-radius: 5px;
            }
            .signature-box.right {
                float: right;
            }
            .signature-box p {
                margin: 5px 0;
                font-size: 10px;
                font-weight: bold;
                color: #444;
            }

            /* Footer */
            .footer {
                clear: both;
                margin-top: 50px;
                padding-top: 15px;
                border-top: 1px solid #eee;
                text-align: center;
                font-size: 9px;
                color: #888;
            }
            .footer p {
                margin: 3px 0;
            }
        </style>
    </head>
    <body>
    <div class="company-header">
        <img src="https://egemer.ercanceviz.com.tr/wp-content/uploads/2025/05/2024-egemer-logo-final.png" alt="Egemer Logo" class="company-logo">
        <div class="company-info">
            <h1>EGEMER MADENCİLİK, MÜH, EĞT. SAN VE TİC. LTD. ŞTİ.</h1>
            <p>FERHATPAŞA MH. 51. SK. NO:22</p>
            <p>ATAŞEHİR / İSTANBUL</p>
            <p>KÜÇÜKYALI VD./325 008 14 08</p>
            <p>T: +90 216 455 97 73 C: +90 533 513 33 45 M: info@egemer.com</p>
            <p>www.egemertezgah.com</p>
        </div>
    </div>

    <table class="offer-info-table">
        <tr>
            <th>TEKLİF TARİHİ</th><td><?php echo esc_html($user_data['kayit_tarihi']); ?></td>
            <th>FİRMA ÜNVANI</th><td>[Firma Ünvanı Gelecek]</td>
            <th>MÜŞTERİ ADI</th><td><?php echo esc_html($user_data['user_name'] . ' ' . $user_data['user_surname']); ?></td>
            <th>FİRMA TEMSİLCİSİ</th><td>[Firma Temsilcisi Gelecek]</td>
            <th>TEKNİK SORUMLU</th><td>[Teknik Sorumlu Gelecek]</td>
            <th>EGEMER TEMSİLCİSİ</th><td>[Egemer Temsilcisi Gelecek]</td>
        </tr>
        <tr>
            <th>FİYAT GEÇERLİLİK TARİHİ</th><td>[Fiyat Geçerlilik Tarihi Gelecek]</td>
            <th>MONTAJ ADRESİ</th><td><?php echo esc_html($user_data['user_address']); ?></td>
            <th>TEL (MÜŞTERİ & PROJE)</th><td><?php echo esc_html($user_data['user_phone']); ?></td>
            <th>TEL (FİRMA TEMSİLCİSİ)</th><td>[Firma Temsilcisi Tel Gelecek]</td>
            <th>TEL (TEKNİK SORUMLU)</th><td>[Teknik Sorumlu Tel Gelecek]</td>
            <th>TEL (EGEMER TEMSİLCİSİ)</th><td>[Egemer Temsilcisi Tel Gelecek]</td>
        </tr>
    </table>

    <div class="customer-info-section" style="display: none;">
        <h3>Müşteri Bilgileri</h3>
        <p><strong>Adı Soyadı:</strong> <?php echo esc_html($user_data['user_name'] . ' ' . $user_data['user_surname']); ?></p>
        <p><strong>Telefon:</strong> <?php echo esc_html($user_data['user_phone']); ?></p>
        <p><strong>E-Posta:</strong> <?php echo esc_html($user_data['user_email']); ?></p>
        <p><strong>Adres:</strong> <?php echo esc_html($user_data['user_address']); ?></p>
        <p><strong>Vergi Dairesi:</strong> <?php echo esc_html($user_data['tax_office']); ?></p>
        <p><strong>Vergi Numarası:</strong> <?php echo esc_html($user_data['tax_number']); ?></p>
    </div>

    <div class="items-table-container">
        <h3>FİYAT TEKLİFİ - <?php echo esc_html($user_data['kayit_numarasi']); ?></h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th style="width: 45%;">ÜRÜN AÇIKLAMA</th>
                    <th class="text-right" style="width: 10%;">MİKTAR</th>
                    <th style="width: 10%;">BİRİM</th>
                    <th class="text-right" style="width: 15%;">BİRİM FİYATI</th>
                    <th class="text-right" style="width: 15%;">TUTAR</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $item_no = 1;
                $grand_total_items_price = 0; // Sadece kalemlerin toplamı
                $kdv_rate = 0.20; // %20 KDV oranı

                if (!empty($offer_details['products']) && is_array($offer_details['products'])):
                    foreach($offer_details['products'] as $idx => $item):
                        $product_name = esc_html($item['selectedProduct'] ?? 'N/A');
                        $brand_name = esc_html($item['selectedBrand'] ?? 'N/A');
                        $color_name = esc_html($item['selectedColor'] ?? 'N/A');
                        $h_value_label = esc_html($item['selectedHValue']['label'] ?? 'N/A');
                        $base_price = (float)($item['selectedHValue']['price'] ?? 0);

                        // Ürün ana kalemi (Tezgah Kalınlığı)
                        ?>
                        <tr>
                            <td><?php echo $item_no++; ?></td>
                            <td>
                                <?php echo $product_name . ' - ' . $brand_name . ' - ' . $color_name; ?><br>
                                <small>Tezgah Kalınlığı: <?php echo $h_value_label; ?></small>
                            </td>
                            <td class="text-right"></td>
                            <td></td>
                            <td class="text-right"><?php echo number_format($base_price, 2, ',', '.'); ?>₺</td>
                            <td class="text-right"></td>
                        </tr>
                        <?php

                        // Derinlik satırları
                        if (!empty($item['depthSection']['rows']) && is_array($item['depthSection']['rows'])) {
                            foreach ($item['depthSection']['rows'] as $row) {
                                $derinlik = htmlspecialchars($row['depthOption'] ?? '');
                                $mtul = (float)($row['mtul'] ?? 0);
                                $unit_price = (float)($row['unitPrice'] ?? 0);
                                $total_price = (float)($row['totalPrice'] ?? 0);
                                $grand_total_items_price += $total_price;
                                ?>
                                <tr>
                                    <td></td>
                                    <td>- Tezgah Derinliği: <?php echo $derinlik; ?></td>
                                    <td class="text-right"><?php echo number_format($mtul, 2, ',', '.'); ?></td>
                                    <td>mtül</td>
                                    <td class="text-right"><?php echo number_format($unit_price, 2, ',', '.'); ?>₺</td>
                                    <td class="text-right"><?php echo number_format($total_price, 2, ',', '.'); ?>₺</td>
                                </tr>
                                <?php
                            }
                        }

                        // Panel
                        if (!empty($item['panelSection']['enabled']) && $item['panelSection']['enabled'] === 'Evet') {
                            $panel_m2 = (float)($item['panelSection']['m2'] ?? 0);
                            $panel_unit_price = (float)($item['panelSection']['unitPrice'] ?? 0);
                            $panel_total_price = (float)($item['panelSection']['totalPrice'] ?? 0);
                            $grand_total_items_price += $panel_total_price;
                            ?>
                            <tr>
                                <td></td>
                                <td>- Panel</td>
                                <td class="text-right"><?php echo number_format($panel_m2, 2, ',', '.'); ?></td>
                                <td>m²</td>
                                <td class="text-right"><?php echo number_format($panel_unit_price, 2, ',', '.'); ?>₺</td>
                                <td class="text-right"><?php echo number_format($panel_total_price, 2, ',', '.'); ?>₺</td>
                            </tr>
                            <?php
                        }

                        // Davlumbaz Panel
                        if (!empty($item['hoodPanelSection']['enabled']) && $item['hoodPanelSection']['enabled'] === 'Evet') {
                            $hood_panel_m2 = (float)($item['hoodPanelSection']['m2'] ?? 0);
                            $hood_panel_unit_price = (float)($item['hoodPanelSection']['unitPrice'] ?? 0);
                            $hood_panel_total_price = (float)($item['hoodPanelSection']['totalPrice'] ?? 0);
                            $grand_total_items_price += $hood_panel_total_price;
                            ?>
                            <tr>
                                <td></td>
                                <td>- Davlumbaz Panel</td>
                                <td class="text-right"><?php echo number_format($hood_panel_m2, 2, ',', '.'); ?></td>
                                <td>m²</td>
                                <td class="text-right"><?php echo number_format($hood_panel_unit_price, 2, ',', '.'); ?>₺</td>
                                <td class="text-right"><?php echo number_format($hood_panel_total_price, 2, ',', '.'); ?>₺</td>
                            </tr>
                            <?php
                        }

                        // Süpürgelik
                        if (!empty($item['skirtingSection']['option'])) {
                            $skirting_option = htmlspecialchars($item['skirtingSection']['option']);
                            $skirting_mtul = (float)($item['skirtingSection']['mtul'] ?? 0);
                            $skirting_unit_price = (float)($item['skirtingSection']['unitPrice'] ?? 0);
                            $skirting_total_price = (float)($item['skirtingSection']['totalPrice'] ?? 0);
                            $grand_total_items_price += $skirting_total_price;
                            ?>
                            <tr>
                                <td></td>
                                <td>- Süpürgelik (<?php echo $skirting_option; ?>)</td>
                                <td class="text-right"><?php echo number_format($skirting_mtul, 2, ',', '.'); ?></td>
                                <td>mtül</td>
                                <td class="text-right"><?php echo number_format($skirting_unit_price, 2, ',', '.'); ?>₺</td>
                                <td class="text-right"><?php echo number_format($skirting_total_price, 2, ',', '.'); ?>₺</td>
                            </tr>
                            <?php
                        }
                    endforeach;
                else:
                    ?>
                    <tr>
                        <td colspan="6">Teklif kalemleri bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <table class="total-summary-table">
        <tr>
            <td class="total-label">TOPLAM</td>
            <td class="total-value"><?php echo number_format($grand_total_items_price, 2, ',', '.'); ?>₺</td>
        </tr>
        <tr>
            <td class="total-label">KDV % <?php echo ($kdv_rate * 100); ?></td>
            <td class="total-value"><?php echo number_format($grand_total_items_price * $kdv_rate, 2, ',', '.'); ?>₺</td>
        </tr>
        <tr>
            <td class="total-label grand-total-final">TOPLAM (KDV 'Lİ)</td>
            <td class="total-value grand-total-final"><?php echo number_format((float)$offer_details['grand_total_price'], 2, ',', '.'); ?>₺</td>
        </tr>
    </table>

    <div class="notes-section">
        <h4>AÇIKLAMALAR:</h4>
        <ul>
            <li>Ölçü, İşçilik, Nakliye ve Montaj dahil fiyat teklifidir. (İstanbul il sınırlarında (Şile, Adalar, Silivri vb. hariç)</li>
            <li>Ocak ve eviye yerlerinin açılması fiyat teklifine dahildir. (flash-tezgaha sıfır eviye ve ocak yerleri hariç)</li>
            <li>Eviye-Lavabo-Ocak tesisat bağlantıları firmamıza ait değildir.</li>
            <li>Eski tezgahların değişiminde; cihaz sökümü ve cihazların tekrar yerine montajı firmamıza ait değildir.</li>
            <li>Taşıyıcı profil ve karkas fiyat teklifine dahil değildir. (Banyo tezgahı, TV ünitesi vb. alanlarda)</li>
            <li>Yüksek katlı montajlarda tezgah asansöre sığmıyorsa & asansör yoksa katın durumuna göre ekstra ücret talep edilir.</li>
            <li>Fiyat teklifinde verilen metrajlar tahmini çıkarılan metrajlardır. Net ölçüler yerinde alınacaktır.</li>
            <li>Proje ve malzeme seçimlerinde olacak değişiklikler fiyatlara (+/-) yansıtılır.</li>
            <li>Belirtilen işlemlerden fazlası talep edilirse ek fiyat uygulanır.</li>
            <li>Fiyat teklifimiz talebin tamamını kapsamaktadır. Kısmi sipariş için yeni teklif alınmalıdır.</li>
        </ul>

        <h4>ÖDEME SEÇENEĞİ</h4>
        <ul>
            <li>ÖDEME: %50 peşinat ile sipariş kesinleşir. Kalan bakiye teslimattan hemen sonra tahsil edilir.</li>
            <li>Kalan bakiye döviz birimi ise ödeme yapılacağı gündeki Garanti Bankası satış kuru geçerlidir.</li>
            <li>İşbu fiyat teklifi Taraflar arasında yazılı sözleşmenin yerine geçer.</li>
        </ul>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>Siparişi alan kaşe / imza - (ATÖLYE)</p>
            <br><br><br>
            <p>_________________________</p>
        </div>
        <div class="signature-box right">
            <p>Siparişi onaylayan kaşe / imza - (MÜŞTERİ)</p>
            <br><br><br>
            <p>_________________________</p>
        </div>
    </div>

    <div class="footer">
        <p>Bu teklif <b><?php echo date_i18n('d.m.Y H:i'); ?></b> tarihinde otomatik olarak oluşturulmuştur.</p>
        <p>Egemer - <a href="https://www.egemermer.com.tr" target="_blank">www.egemermer.com.tr</a> | <a href="https://www.instagram.com/egemer_tezgah/" target="_blank">Instagram</a></p>
    </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
