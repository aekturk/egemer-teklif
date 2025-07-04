<?php
/**
 * Ürünler yönetim sayfası (admin paneli).
 */
function egemer_offer_products_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_products';
    // ID'ye göre küçükten büyüğe sıralama
    $products = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>Ürün Yönetimi</h1>
        <form id="egemer-product-form" method="post">
            <input type="hidden" name="product_id" id="product_id" value="">
            <table class="form-table">
                <tr>
                    <th><label for="product_name">Ürün Adı</label></th>
                    <td><input type="text" name="product_name" id="product_name" required></td>
                </tr>
                <tr>
                    <th><label for="product_description">Açıklama</label></th>
                    <td><textarea name="product_description" id="product_description"></textarea></td>
                </tr>
                <tr>
                    <th><label for="product_image_url">Görsel URL</label></th>
                    <td>
                        <input type="text" name="product_image_url" id="product_image_url">
                        <button type="button" class="button" id="upload_image_button">Medya Yükle/Seç</button>
                        <br>
                        <img id="product_image_preview" src="" style="max-width:80px; height:auto; margin-top:8px; display:none;">
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary" id="save_product_btn">Kaydet</button>
                <button type="button" class="button" id="cancel_product_btn">İptal</button>
            </p>
        </form>

        <button type="button" class="button" id="toggle_bulk_add_products">Toplu Ürün Ekle (10 Satır)</button>
        <form id="bulk_add_products_form" style="display:none;" method="post">
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th>Açıklama</th>
                        <th>Görsel URL</th>
                        <th>Görsel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i=0;$i<10;$i++): ?>
                    <tr>
                        <td><input type="text" name="bulk_products[<?php echo $i; ?>][name]"></td>
                        <td><input type="text" name="bulk_products[<?php echo $i; ?>][description]"></td>
                        <td>
                            <input type="text" name="bulk_products[<?php echo $i; ?>][image_url]" id="bulk_product_image_url_<?php echo $i; ?>">
                            <button type="button" class="button bulk-upload-image-button" data-row-id="<?php echo $i; ?>">Medya Seç</button>
                        </td>
                        <td>
                            <img id="bulk_product_image_preview_<?php echo $i; ?>" src="" style="max-width:50px; height:auto; display:none;" class="bulk-image-preview">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary">Toplu Kaydet</button></p>
        </form>

        <input type="text" id="product-search" placeholder="Ürünlerde ara..." style="margin-bottom:10px; width:250px;">
        <table class="wp-list-table widefat fixed striped" id="products-table">
            <thead>
                <tr>
                    <th class="sortable">ID</th>
                    <th class="sortable">Adı</th>
                    <th class="sortable">Açıklama</th>
                    <th>Görsel</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $product): ?>
                <tr>
                    <td><?php echo esc_html($product['id']); ?></td>
                    <td><?php echo esc_html($product['name']); ?></td>
                    <td><?php echo esc_html($product['description']); ?></td>
                    <td>
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?php echo esc_url($product['image_url']); ?>" style="max-width:50px; height:auto;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button edit-product-btn" data-id="<?php echo $product['id']; ?>" data-name="<?php echo esc_attr($product['name']); ?>" data-description="<?php echo esc_attr($product['description']); ?>" data-image-url="<?php echo esc_attr($product['image_url']); ?>">Düzenle</button>
                        <button class="button delete-product-btn" data-id="<?php echo $product['id']; ?>">Sil</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <style>
    #products-table th.sortable {
        background: #e6f7ff !important;
        cursor: pointer;
        color: #0073aa;
        transition: background 0.2s;
    }
    #products-table th.sortable:hover {
        background: #bae7ff !important;
        color: #005177;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Tekil ürün için medya yükleyici
        var mediaUploader;
        $('#upload_image_button').on('click', function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media({
                title: 'Görsel Seç',
                button: { text: 'Görseli Kullan' },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#product_image_url').val(attachment.url);
                $('#product_image_preview').attr('src', attachment.url).show();
            });
            mediaUploader.open();
        });

        // Görsel url varsa önizleme göster
        if ($('#product_image_url').val()) {
            $('#product_image_preview').attr('src', $('#product_image_url').val()).show();
        }

        // Toplu ekleme formunu aç/kapat
        $('#toggle_bulk_add_products').on('click', function() {
            $('#bulk_add_products_form').toggle();
        });

        // Toplu eklemede medya seçme
        $('.bulk-upload-image-button').on('click', function(e) {
            e.preventDefault();
            var rowId = $(this).data('row-id');
            var targetInput = $('#bulk_product_image_url_' + rowId);
            var targetPreview = $('#bulk_product_image_preview_' + rowId);

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
        $('.edit-product-btn').on('click', function() {
            $('#product_id').val($(this).data('id'));
            $('#product_name').val($(this).data('name'));
            $('#product_description').val($(this).data('description'));
            $('#product_image_url').val($(this).data('image-url'));
            $('#product_image_preview').attr('src', $(this).data('image-url')).show();
        });

        // İptal butonu: formu temizle
        $('#cancel_product_btn').on('click', function() {
            $('#egemer-product-form')[0].reset();
            $('#product_id').val('');
            $('#product_image_preview').hide();
        });

        // Tekil ürün kaydet (ekle/güncelle)
        $('#egemer-product-form').on('submit', function(e) {
            e.preventDefault();
            var data = {
                action: 'egemer_save_product',
                product_id: $('#product_id').val(),
                name: $('#product_name').val(),
                description: $('#product_description').val(),
                image_url: $('#product_image_url').val(),
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Kayıt başarısız!'); }
            });
        });

        // Sil butonu
        $('.delete-product-btn').on('click', function() {
            if (!confirm('Silmek istediğinize emin misiniz?')) return;
            var data = {
                action: 'egemer_delete_product',
                product_id: $(this).data('id'),
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Silme başarısız!'); }
            });
        });

        // Toplu ekleme formu AJAX
        $('#bulk_add_products_form').on('submit', function(e) {
            e.preventDefault();
            var bulkData = $(this).serializeArray();
            var data = {
                action: 'egemer_bulk_add_products',
                bulk: bulkData,
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Toplu ekleme başarısız!'); }
            });
        });

        // Arama kutusu ile filtreleme
        $('#product-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#products-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Sıralama
        $('#products-table th.sortable').on('click', function() {
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
    if (strpos($hook, 'egemer-offer-products') !== false) {
        wp_enqueue_media();
    }
});