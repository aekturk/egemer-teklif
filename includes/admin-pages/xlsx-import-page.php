<?php
use PhpOffice\PhpSpreadsheet\IOFactory;

function egemer_offer_xlsx_bulk_import_page() {
    ?>
    <div class="wrap">
        <h1>XLSX Toplu Ekleme</h1>
        <a href="<?php echo admin_url('admin-post.php?action=egemer_download_xlsx_template_dummy'); ?>" class="button button-secondary" style="margin-bottom:15px;">Şablon Excel İndir</a>
        <input type="file" id="xlsx-file-input" accept=".xlsx,.xls" style="margin-bottom:15px;">
        <div id="xlsx-table-container" style="margin-bottom:15px;"></div>
        <button type="button" class="button button-primary" id="xlsx-save-btn" style="display:none;">Kaydet</button>
        <div id="xlsx-import-message"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
    let xlsxData = {};
    let xlsxHeaders = {};

    document.getElementById('xlsx-file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            workbook.SheetNames.forEach((sheetName) => {
                const worksheet = workbook.Sheets[sheetName];
                const json = XLSX.utils.sheet_to_json(worksheet, {header:1});
                if (json.length < 2) {
                    document.getElementById('xlsx-table-container').innerHTML = '<p>Excel dosyası boş veya hatalı.</p>';
                    return;
                }
                xlsxHeaders[sheetName] = json[0];
                xlsxData[sheetName] = json.slice(1);
            });
            renderTable();
            document.getElementById('xlsx-save-btn').style.display = 'inline-block';
        };
        reader.readAsArrayBuffer(file);
    });

    function renderTable() {
        let html = '';
        Object.keys(xlsxData).forEach(sheetName => {
            html += `<h2>${sheetName}</h2><table class="widefat fixed striped" id="xlsx-edit-table-${sheetName.replace(/ /g, '_')}"><thead><tr>`;
            xlsxHeaders[sheetName].forEach(h => html += `<th>${h}</th>`);
            html += '</tr></thead><tbody>';
            xlsxData[sheetName].forEach((row, i) => {
                html += '<tr>';
                xlsxHeaders[sheetName].forEach((h, j) => {
                    const val = typeof row[j] !== 'undefined' ? row[j] : '';
                    html += `<td contenteditable="true" data-sheet="${sheetName}" data-row="${i}" data-col="${j}">${val}</td>`;
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
        });
        document.getElementById('xlsx-table-container').innerHTML = html;
    }

    document.addEventListener('input', function(e) {
        if (e.target.closest('td[contenteditable="true"]')) {
            const td = e.target;
            const sheet = td.getAttribute('data-sheet');
            const row = parseInt(td.getAttribute('data-row'));
            const col = parseInt(td.getAttribute('data-col'));
            xlsxData[sheet][row][col] = td.innerText;
        }
    });

    document.getElementById('xlsx-save-btn').addEventListener('click', function() {
        if (!Object.keys(xlsxHeaders).length || !Object.keys(xlsxData).length) return;
        let error = '';
        const productIdx = xlsxHeaders['Ürünler'] ? xlsxHeaders['Ürünler'].findIndex(h => h.toLowerCase().includes('ürün')) : -1;
        const brandIdx = xlsxHeaders['Markalar'] ? xlsxHeaders['Markalar'].findIndex(h => h.toLowerCase().includes('marka')) : -1;
        xlsxData['Ürünler'].forEach((row, i) => {
            if (brandIdx > -1 && row[brandIdx] && (!row[productIdx] || row[productIdx]==='')) {
                error = `Ürünler Satır ${i+2}: Marka için Ürün sütunu boş olamaz.`;
            }
            if (xlsxHeaders['Markalar'] && xlsxHeaders['Markalar'].findIndex(h => h.toLowerCase().includes('renk')) > -1 && row[brandIdx] && (!row[brandIdx] || row[brandIdx]==='')) {
                error = `Markalar Satır ${i+2}: Renk için Marka sütunu boş olamaz.`;
            }
        });
        if (error) {
            document.getElementById('xlsx-import-message').innerHTML = '<div style="color:red;">'+error+'</div>';
            return;
        }
        jQuery.post(egemerAdminData.ajaxUrl, {
            action: 'egemer_xlsx_bulk_import',
            sheets: xlsxData,
            _wpnonce: egemerAdminData.nonce
        }, function(resp) {
            if (resp.success) {
                document.getElementById('xlsx-import-message').innerHTML = '<div style="color:green;">Başarıyla eklendi.</div>';
            } else {
                document.getElementById('xlsx-import-message').innerHTML = '<div style="color:red;">'+(resp.data || 'Hata oluştu')+'</div>';
            }
        });
    });
    </script>
    <style>
    #xlsx-edit-table td[contenteditable="true"] { background: #fffbe6; min-width: 80px; }
    #xlsx-edit-table th { background: #f1f1f1; }
    </style>
    <?php
}

// Şablon Excel dosyasını indirmek için endpoint
add_action('admin_post_egemer_download_xlsx_template_dummy', function() {
    $template_path = EGEMER_OFFER_PLUGIN_DIR . 'assets/egemer-xlsx-sablon.xlsx';
    if (file_exists($template_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="egemer-xlsx-sablon.xlsx"');
        header('Content-Length: ' . filesize($template_path));
        readfile($template_path);
        exit;
    } else {
        wp_die('Şablon dosyası bulunamadı.');
    }
});

// Backend handler ekle (örnek):
add_action('wp_ajax_egemer_xlsx_bulk_import', function() {
    // $_FILES['excel_file'] ile dosya yüklenmişse:
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['excel_file']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($tmpPath);
            // ...sheet okuma ve kayıt işlemleri...
        } catch (\Exception $e) {
            wp_send_json_error('Excel okuma hatası: ' . $e->getMessage());
        }
    } else {
        wp_send_json_error('Excel dosyası yüklenemedi.');
    }
});