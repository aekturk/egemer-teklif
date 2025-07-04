<?php
/**
 * Teklifler listesini gösteren admin paneli sayfası.
 */
function egemer_offer_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'egemer_offers';

    // Sıralama parametreleri
    $allowed_columns = array('id', 'user_name', 'user_surname', 'user_phone', 'user_email', 'created_at', 'grand_total_price');
    $sort_params = egemer_offer_get_current_sort_params($allowed_columns, 'id', 'desc');
    $orderby = esc_sql($sort_params['orderby']);
    $order = esc_sql($sort_params['order']);

    // Arama parametresi
    $search = '';
    $search_sql = '';
    $search_args = [];
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $search = sanitize_text_field($_GET['s']);
        $search_sql = " WHERE user_name LIKE %s OR user_surname LIKE %s OR user_email LIKE %s OR user_phone LIKE %s OR registration_number LIKE %s ";
        $search_args = array_fill(0, 5, '%' . $search . '%');
    }

    // Sayfalama
    $per_page = 20;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    // Toplam kayıt sayısı
    if ($search_sql) {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name $search_sql", ...$search_args));
    } else {
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    // Teklifleri getir
    if ($search_sql) {
        $sql = "SELECT * FROM $table_name $search_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $params = array_merge($search_args, [$per_page, $offset]);
        $prepared_sql = call_user_func_array([$wpdb, 'prepare'], array_merge([$sql], $params));
        $offers = $wpdb->get_results($prepared_sql, ARRAY_A);
    } else {
        $offers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
                $per_page, $offset
            ),
            ARRAY_A
        );
    }

    // Toplam sayfa sayısı
    $total_pages = ceil($total_items / $per_page);

    ?>
    <div class="wrap">
        <h1>Egemer Teklif Listesi</h1>
        <form method="get">
            <input type="hidden" name="page" value="egemer-offer-list" />
            <p class="search-box">
                <label class="screen-reader-text" for="offer-search-input">Tekliflerde Ara:</label>
                <input type="search" id="offer-search-input" name="s" value="<?php echo esc_attr($search); ?>" />
                <input type="submit" id="search-submit" class="button" value="Ara" />
            </p>
        </form>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <?php
                    echo egemer_offer_get_sortable_column_header('id', 'ID', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('user_name', 'Adı', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('user_surname', 'Soyadı', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('user_email', 'E-posta', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('user_phone', 'Telefon', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('created_at', 'Tarih', $orderby, $order, 'egemer-offer-list', $search);
                    echo egemer_offer_get_sortable_column_header('grand_total_price', 'Toplam Tutar', $orderby, $order, 'egemer-offer-list', $search);
                    ?>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($offers)) : ?>
                    <?php foreach ($offers as $offer) : ?>
                        <tr>
                            <td><?php echo esc_html($offer['id']); ?></td>
                            <td><?php echo esc_html($offer['user_name']); ?></td>
                            <td><?php echo esc_html($offer['user_surname']); ?></td>
                            <td><?php echo isset($offer['user_email']) ? esc_html($offer['user_email']) : ''; ?></td>
                            <td><?php echo isset($offer['user_phone']) ? esc_html($offer['user_phone']) : ''; ?></td>
                            <td><?php echo date_i18n('d.m.Y H:i', strtotime($offer['created_at'])); ?></td>
                            <td style="text-align:right;"><?php echo isset($offer['grand_total_price']) ? number_format((float)$offer['grand_total_price'], 2, ',', '.') : '0,00'; ?> ₺</td>
                            <td>
                                <?php if (!empty($offer['pdf_url'])): ?>
                                    <a href="<?php echo esc_url($offer['pdf_url']); ?>" target="_blank" class="button">PDF Görüntüle</a>
                                <?php else: ?>
                                    <span style="color:#888;">Yok</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Kayıt bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $current_url = remove_query_arg(array('paged'), $_SERVER['REQUEST_URI']);
                    for ($i = 1; $i <= $total_pages; $i++): 
                        $url = add_query_arg('paged', $i, $current_url);
                        $class = ($i == $paged) ? ' class="current-page"' : '';
                        ?>
                        <a<?php echo $class; ?> href="<?php echo esc_url($url); ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}