<?php
/**
 * Markalar yönetim sayfası (admin paneli).
 */
function egemer_offer_brands_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_brands';
    $table_products = $wpdb->prefix . 'egemer_products';
    // ID'ye göre küçükten büyüğe sıralama
    $brands = $wpdb->get_results("SELECT b.*, p.name as product_name FROM $table_name b LEFT JOIN $table_products p ON b.product_id = p.id ORDER BY b.id ASC", ARRAY_A);
    $products = $wpdb->get_results("SELECT id, name FROM $table_products ORDER BY name ASC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>Marka Yönetimi</h1>
        <form id="egemer-brand-form" method="post">
            <input type="hidden" name="brand_id" id="brand_id" value="">
            <table class="form-table">
                <tr>
                    <th><label for="brand_name">Marka Adı</label></th>
                    <td><input type="text" name="brand_name" id="brand_name" required></td>
                </tr>
                <tr>
                    <th><label for="brand_product_id">Ürün</label></th>
                    <td>
                        <select name="brand_product_id" id="brand_product_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>"><?php echo esc_html($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="brand_image_url">Görsel URL</label></th>
                    <td>
                        <input type="text" name="brand_image_url" id="brand_image_url">
                        <button type="button" class="button" id="upload_brand_image_button">Medya Yükle/Seç</button>
                        <br>
                        <img id="brand_image_preview" src="" style="max-width:80px; height:auto; margin-top:8px; display:none;">
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary" id="save_brand_btn">Kaydet</button>
                <button type="button" class="button" id="cancel_brand_btn">İptal</button>
            </p>
        </form>

        <button type="button" class="button" id="toggle_bulk_add_brands">Toplu Marka Ekle (10 Satır)</button>
        <form id="bulk_add_brands_form" style="display:none;" method="post">
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Marka Adı</th>
                        <th>Ürün</th>
                        <th>Görsel URL</th>
                        <th>Görsel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i=0;$i<10;$i++): ?>
                    <tr>
                        <td><input type="text" name="bulk_brands[<?php echo $i; ?>][name]"></td>
                        <td>
                            <select name="bulk_brands[<?php echo $i; ?>][product_id]">
                                <option value="">Seçiniz</option>
                                <?php foreach($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo esc_html($product['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="bulk_brands[<?php echo $i; ?>][image_url]" id="bulk_brand_image_url_<?php echo $i; ?>">
                            <button type="button" class="button bulk-upload-brand-image-button" data-row-id="<?php echo $i; ?>">Medya Seç</button>
                        </td>
                        <td>
                            <img id="bulk_brand_image_preview_<?php echo $i; ?>" src="" style="max-width:50px; height:auto; display:none;" class="bulk-image-preview">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary">Toplu Kaydet</button></p>
        </form>

        <input type="text" id="brand-search" placeholder="Markalarda ara..." style="margin-bottom:10px; width:250px;">
        <table class="wp-list-table widefat fixed striped" id="brands-table">
            <thead>
                <tr>
                    <th class="sortable">ID</th>
                    <th class="sortable">Adı</th>
                    <th class="sortable">Ürün</th>
                    <th>Görsel</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($brands as $brand): ?>
                <tr>
                    <td><?php echo esc_html($brand['id']); ?></td>
                    <td><?php echo esc_html($brand['name']); ?></td>
                    <td><?php echo esc_html($brand['product_name']); ?></td>
                    <td>
                        <?php if (!empty($brand['image_url'])): ?>
                            <img src="<?php echo esc_url($brand['image_url']); ?>" style="max-width:50px; height:auto;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button edit-brand-btn" data-id="<?php echo $brand['id']; ?>" data-name="<?php echo esc_attr($brand['name']); ?>" data-product-id="<?php echo esc_attr($brand['product_id']); ?>" data-image-url="<?php echo esc_attr($brand['image_url']); ?>">Düzenle</button>
                        <button class="button delete-brand-btn" data-id="<?php echo $brand['id']; ?>">Sil</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <style>
    #brands-table th.sortable {
        background: #e6f7ff !important;
        cursor: pointer;
        color: #0073aa;
        transition: background 0.2s;
    }
    #brands-table th.sortable:hover {
        background: #bae7ff !important;
        color: #005177;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Medya seçme (tek marka)
        var mediaUploaderBrand;
        $('#upload_brand_image_button').on('click', function(e) {
            e.preventDefault();
            if (mediaUploaderBrand) {
                mediaUploaderBrand.open();
                return;
            }
            mediaUploaderBrand = wp.media({
                title: 'Görsel Seç',
                button: { text: 'Görseli Kullan' },
                multiple: false
            });
            mediaUploaderBrand.on('select', function() {
                var attachment = mediaUploaderBrand.state().get('selection').first().toJSON();
                $('#brand_image_url').val(attachment.url);
                $('#brand_image_preview').attr('src', attachment.url).show();
            });
            mediaUploaderBrand.open();
        });

        // Toplu eklemede medya seçme
        $('.bulk-upload-brand-image-button').on('click', function(e) {
            e.preventDefault();
            var rowId = $(this).data('row-id');
            var targetInput = $('#bulk_brand_image_url_' + rowId);
            var targetPreview = $('#bulk_brand_image_preview_' + rowId);

            var bulkMediaUploader = wp.media({
                title: 'Görsel Seç',
                button: { text: 'Görseli Kullan' },
                multiple: false
            });
            bulkMediaUploader.on('select', function() {
                var attachment = bulkMediaUploader.state().get('selection').first().toJSON();
                targetInput.val(attachment.url);
                targetPreview.attr('src', attachment.url).show();
            });
            bulkMediaUploader.open();
        });

        // Düzenle butonu: formu doldur
        $('.edit-brand-btn').on('click', function() {
            $('#brand_id').val($(this).data('id'));
            $('#brand_name').val($(this).data('name'));
            $('#brand_product_id').val($(this).data('product-id'));
            $('#brand_image_url').val($(this).data('image-url'));
            $('#brand_image_preview').attr('src', $(this).data('image-url')).show();
        });

        $('#cancel_brand_btn').on('click', function() {
            $('#egemer-brand-form')[0].reset();
            $('#brand_id').val('');
            $('#brand_image_preview').hide();
        });

        $('#egemer-brand-form').on('submit', function(e) {
            e.preventDefault();
            var data = {
                action: 'egemer_save_brand',
                brand_id: $('#brand_id').val(),
                name: $('#brand_name').val(),
                product_id: $('#brand_product_id').val(),
                image_url: $('#brand_image_url').val(),
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Kayıt başarısız!'); }
            });
        });

        $('.delete-brand-btn').on('click', function() {
            if (!confirm('Silmek istediğinize emin misiniz?')) return;
            var data = {
                action: 'egemer_delete_brand',
                brand_id: $(this).data('id'),
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Silme başarısız!'); }
            });
        });

        // Toplu ekleme formu AJAX
        $('#bulk_add_brands_form').on('submit', function(e) {
            e.preventDefault();
            var bulkData = $(this).serializeArray();
            var data = {
                action: 'egemer_bulk_add_brands',
                bulk: bulkData,
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Toplu ekleme başarısız!'); }
            });
        });

        $('#toggle_bulk_add_brands').on('click', function() {
            $('#bulk_add_brands_form').toggle();
        });

        $('#brand-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#brands-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        $('#brands-table th.sortable').on('click', function() {
            var table = $(this).parents('table').eq(0);
            var rows = table.find('tbody > tr').toArray().sort(comparer($(this).index()));
            this.asc = !this.asc;
            if (!this.asc){rows = rows.reverse();}
            for (var i = 0; i < rows.length; i++){table.children('tbody').append(rows[i]);}
        });
        function comparer(index) {
            return function(a, b) {
                var valA = getCellValue(a, index), valB = getCellValue(b, index);
                return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB);
            }
        }
        function getCellValue(row, index){ return $(row).children('td').eq(index).text(); }
    });
    </script>
    <?php
}

add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'egemer-offer-brands') !== false) {
        wp_enqueue_media();
    }
});