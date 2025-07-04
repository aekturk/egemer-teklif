<?php
/**
 * XLSX Toplu Ekleme yönetim sayfası (admin paneli).
 */
function egemer_offer_xlsx_bulk_import_page() {
    ?>
    <div class="wrap">
        <h1>XLSX Toplu Ekleme</h1>
        <p>
            Buradan ürün, marka ve renkleri <b>Excel (.xlsx)</b> dosyası ile topluca ekleyebilirsiniz.<br>
            <b>Not:</b> Dosyanın örnek formatını aşağıdaki bağlantıdan indirebilirsiniz.
        </p>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(plugins_url('assets/sample.xlsx', dirname(__FILE__, 2))); ?>">Örnek XLSX Formatı İndir</a>
        </p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('egemer_xlsx_import', 'egemer_xlsx_import_nonce'); ?>
            <input type="file" name="xlsx_file" accept=".xlsx" required />
            <input type="submit" name="egemer_xlsx_import_submit" class="button button-secondary" value="Yükle ve Aktar" />
        </form>
        <hr>
        <?php
        if (isset($_POST['egemer_xlsx_import_submit']) && check_admin_referer('egemer_xlsx_import', 'egemer_xlsx_import_nonce')) {
            if (!empty($_FILES['xlsx_file']['tmp_name'])) {
                require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
                $file = $_FILES['xlsx_file']['tmp_name'];
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray(null, true, true, true);

                    // Burada örnek: başlık satırı atlanır, her satırda ürün/marka/renk eklenir
                    $success = 0;
                    $fail = 0;
                    for ($i = 2; $i <= count($rows); $i++) {
                        $row = $rows[$i];
                        // Kolon harfleri örnek: A=Ürün, B=Marka, C=Renk, D-G=Fiyatlar, H=Resim
                        $product = trim($row['A']);
                        $brand = trim($row['B']);
                        $color = trim($row['C']);
                        $image_url = trim($row['H']);
                        $h1_2_cm = floatval(str_replace(',', '.', $row['D']));
                        $h4_cm_default = floatval(str_replace(',', '.', $row['E']));
                        $h5_6_cm = floatval(str_replace(',', '.', $row['F']));
                        $h7_8_cm = floatval(str_replace(',', '.', $row['G']));

                        // Ürün ekle veya bul
                        $product_id = egemer_offer_get_or_create_product($product);

                        // Marka ekle veya bul
                        $brand_id = egemer_offer_get_or_create_brand($brand, $product_id);

                        // Renk ekle
                        if ($color) {
                            global $wpdb;
                            $colors_table = $wpdb->prefix . 'egemer_colors';
                            $existing = $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM $colors_table WHERE name=%s AND brand_id=%d",
                                $color, $brand_id
                            ));
                            if (!$existing) {
                                $wpdb->insert($colors_table, array(
                                    'brand_id' => $brand_id,
                                    'name' => $color,
                                    'image_url' => $image_url,
                                    'h1_2_cm' => $h1_2_cm,
                                    'h4_cm_default' => $h4_cm_default,
                                    'h5_6_cm' => $h5_6_cm,
                                    'h7_8_cm' => $h7_8_cm,
                                ));
                                $success++;
                            } else {
                                $fail++;
                            }
                        }
                    }
                    echo '<div class="notice notice-success"><p>Aktarım tamamlandı! Başarılı: <b>' . $success . '</b>, Atlanan (zaten var): <b>' . $fail . '</b></p></div>';
                } catch (Exception $e) {
                    echo '<div class="notice notice-error"><p>Dosya okuma hatası: ' . esc_html($e->getMessage()) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Lütfen bir .xlsx dosyası seçiniz.</p></div>';
            }
        }
        ?>
        <ul>
            <li>Excel dosyasında sütun sırası önemlidir. <b>A: Ürün, B: Marka, C: Renk, D: 1-2cm fiyatı, E: 4cm fiyatı, F: 5-6cm fiyatı, G: 7-8cm fiyatı, H: Renk Resmi URL</b></li>
            <li>Her bir satır yeni bir renk kaydıdır. Aynı ürün/marka tekrar eklenirse atlanır.</li>
        </ul>
    </div>
    <?php
}

/**
 * Ürün adından ürün ID'si döndürür, yoksa yeni ürün ekler.
 */
function egemer_offer_get_or_create_product($name) {
    global $wpdb;
    $products_table = $wpdb->prefix . 'egemer_products';
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $products_table WHERE name = %s",
        $name
    ));
    if ($id) return $id;
    $wpdb->insert($products_table, array('name' => $name));
    return $wpdb->insert_id;
}

/**
 * Marka adından marka ID'si döndürür, yoksa yeni marka ekler.
 */
function egemer_offer_get_or_create_brand($name, $product_id) {
    global $wpdb;
    $brands_table = $wpdb->prefix . 'egemer_brands';
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $brands_table WHERE name = %s AND product_id = %d",
        $name, $product_id
    ));
    if ($id) return $id;
    $wpdb->insert($brands_table, array('name' => $name, 'product_id' => $product_id));
    return $wpdb->insert_id;
}