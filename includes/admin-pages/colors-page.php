<?php
/**
 * Renkler yönetim sayfası (admin paneli).
 */
function egemer_offer_colors_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_colors';
    $table_brands = $wpdb->prefix . 'egemer_brands';
    // ID'ye göre küçükten büyüğe sıralama
    $colors = $wpdb->get_results("SELECT c.*, b.name as brand_name FROM $table_name c LEFT JOIN $table_brands b ON c.brand_id = b.id ORDER BY c.id ASC", ARRAY_A);
    $brands = $wpdb->get_results("SELECT id, name FROM $table_brands ORDER BY name ASC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>Renk Yönetimi</h1>
        <form id="egemer-color-form" method="post">
            <input type="hidden" name="color_id" id="color_id" value="">
            <table class="form-table">
                <tr>
                    <th><label for="color_name">Renk Adı</label></th>
                    <td><input type="text" name="color_name" id="color_name" required></td>
                </tr>
                <tr>
                    <th><label for="color_brand_id">Marka</label></th>
                    <td>
                        <select name="color_brand_id" id="color_brand_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo esc_html($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="color_image_url">Görsel URL</label></th>
                    <td>
                        <input type="text" name="color_image_url" id="color_image_url">
                        <button type="button" class="button" id="upload_color_image_button">Medya Yükle/Seç</button>
                        <br>
                        <img id="color_image_preview" src="" style="max-width:80px; height:auto; margin-top:8px; display:none;">
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="button button-primary" id="save_color_btn">Kaydet</button>
                <button type="button" class="button" id="cancel_color_btn">İptal</button>
            </p>
        </form>

        <button type="button" class="button" id="toggle_bulk_add_colors">Toplu Renk Ekle (10 Satır)</button>
        <form id="bulk_add_colors_form" style="display:none;" method="post">
            <table class="form-table">
                <thead>
                    <tr>
                        <th>Renk Adı</th>
                        <th>Marka</th>
                        <th>Görsel URL</th>
                        <th>Görsel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i=0;$i<10;$i++): ?>
                    <tr>
                        <td><input type="text" name="bulk_colors[<?php echo $i; ?>][name]"></td>
                        <td>
                            <select name="bulk_colors[<?php echo $i; ?>][brand_id]">
                                <option value="">Seçiniz</option>
                                <?php foreach($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>"><?php echo esc_html($brand['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="bulk_colors[<?php echo $i; ?>][image_url]" id="bulk_color_image_url_<?php echo $i; ?>">
                            <button type="button" class="button bulk-upload-color-image-button" data-row-id="<?php echo $i; ?>">Medya Seç</button>
                        </td>
                        <td>
                            <img id="bulk_color_image_preview_<?php echo $i; ?>" src="" style="max-width:50px; height:auto; display:none;" class="bulk-image-preview">
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <p><button type="submit" class="button button-primary">Toplu Kaydet</button></p>
        </form>

        <input type="text" id="color-search" placeholder="Renklerde ara..." style="margin-bottom:10px; width:250px;">
        <table class="wp-list-table widefat fixed striped" id="colors-table">
            <thead>
                <tr>
                    <th class="sortable">ID</th>
                    <th class="sortable">Adı</th>
                    <th class="sortable">Marka</th>
                    <th>Görsel</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($colors as $color): ?>
                <tr>
                    <td><?php echo esc_html($color['id']); ?></td>
                    <td><?php echo esc_html($color['name']); ?></td>
                    <td><?php echo esc_html($color['brand_name']); ?></td>
                    <td>
                        <?php if (!empty($color['image_url'])): ?>
                            <img src="<?php echo esc_url($color['image_url']); ?>" style="max-width:50px; height:auto;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button edit-color-btn" data-id="<?php echo $color['id']; ?>" data-name="<?php echo esc_attr($color['name']); ?>" data-brand-id="<?php echo esc_attr($color['brand_id']); ?>" data-image-url="<?php echo esc_attr($color['image_url']); ?>">Düzenle</button>
                        <button class="button delete-color-btn" data-id="<?php echo $color['id']; ?>">Sil</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <style>
    #colors-table th.sortable {
        background: #e6f7ff !important;
        cursor: pointer;
        color: #0073aa;
        transition: background 0.2s;
    }
    #colors-table th.sortable:hover {
        background: #bae7ff !important;
        color: #005177;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Medya seçme (tek renk)
        var mediaUploaderColor;
        $('#upload_color_image_button').on('click', function(e) {
            e.preventDefault();
            if (mediaUploaderColor) {
                mediaUploaderColor.open();
                return;
            }
            mediaUploaderColor = wp.media({
                title: 'Görsel Seç',
                button: { text: 'Görseli Kullan' },
                multiple: false
            });
            mediaUploaderColor.on('select', function() {
                var attachment = mediaUploaderColor.state().get('selection').first().toJSON();
                $('#color_image_url').val(attachment.url);
                $('#color_image_preview').attr('src', attachment.url).show();
            });
            mediaUploaderColor.open();
        });

        // Toplu eklemede medya seçme
        $('.bulk-upload-color-image-button').on('click', function(e) {
            e.preventDefault();
            var rowId = $(this).data('row-id');
            var targetInput = $('#bulk_color_image_url_' + rowId);
            var targetPreview = $('#bulk_color_image_preview_' + rowId);

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

        // Toplu ekleme formu AJAX
        $('#bulk_add_colors_form').on('submit', function(e) {
            e.preventDefault();
            var bulkData = $(this).serializeArray();
            var data = {
                action: 'egemer_bulk_add_colors',
                bulk: bulkData,
                _wpnonce: egemerAdminData.nonce
            };
            $.post(egemerAdminData.ajaxUrl, data, function(resp) {
                if (resp.success) { location.reload(); }
                else { alert(resp.data || 'Toplu ekleme başarısız!'); }
            });
        });

        $('#toggle_bulk_add_colors').on('click', function() {
            $('#bulk_add_colors_form').toggle();
        });

        $('#color-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#colors-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        $('#colors-table th.sortable').on('click', function() {
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
    if (strpos($hook, 'egemer-offer-colors') !== false) {
        wp_enqueue_media();
    }
});
// (Kodun tamamı zaten güncel ve doğru. Sadece izinlerle ilgili bir sorun yoksa, başka bir değişiklik yapmana gerek yok.)