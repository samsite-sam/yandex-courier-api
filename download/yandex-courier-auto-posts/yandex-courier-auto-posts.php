<?php
/**
 * Plugin Name: Яндекс Курьер - АвтоПостинг
 * Plugin URI: https://eda---yandex.ru/
 * Description: Автоматическая генерация и публикация SEO-оптимизированных статей о работе курьером в Яндекс Еда.
 * Version: 1.0.3
 * Author: Yandex Courier Team
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YCAP_VERSION', '1.0.3');

// Логирование
function ycap_log($message) {
    $log_file = WP_CONTENT_DIR . '/ycap_debug.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$time}] {$message}\n", FILE_APPEND);
}

// Активация плагина
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('ycap_daily_generation')) {
        wp_schedule_event(time(), 'daily', 'ycap_daily_generation');
    }
    ycap_log('Plugin activated');
});

// Деактивация плагина
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('ycap_daily_generation');
    ycap_log('Plugin deactivated');
});

// Добавление меню
add_action('admin_menu', function() {
    add_menu_page(
        'АвтоПостинг',
        'АвтоПостинг',
        'manage_options',
        'yandex-courier-auto-posts',
        'ycap_admin_page',
        'dashicons-migrate',
        30
    );
});

// Страница админки
function ycap_admin_page() {
    $settings = get_option('ycap_settings', array());

    // Обработка формы
    if (isset($_POST['ycap_save_settings']) && check_admin_referer('ycap_settings_nonce')) {
        $settings = array(
            'api_endpoint' => esc_url_raw($_POST['api_endpoint']),
            'articles_per_day' => absint($_POST['articles_per_day']),
            'publication_time' => sanitize_text_field($_POST['publication_time']),
            'referral_link' => esc_url_raw($_POST['referral_link']),
            'default_category' => absint($_POST['default_category']),
            'default_author' => absint($_POST['default_author']),
            'auto_publish' => isset($_POST['auto_publish']),
        );
        update_option('ycap_settings', $settings);
        echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
    }

    // Ручная генерация
    if (isset($_POST['ycap_generate_now']) && check_admin_referer('ycap_settings_nonce')) {
        ycap_log('Manual generation started');
        $result = ycap_generate_article();
        if (is_wp_error($result)) {
            ycap_log('Error: ' . $result->get_error_message());
            echo '<div class="notice notice-error"><p>Ошибка: ' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            ycap_log('Article created: ' . $result);
            echo '<div class="notice notice-success"><p>✅ Статья создана! <a href="' . esc_url(get_edit_post_link($result)) . '">Редактировать</a> | <a href="' . esc_url(get_permalink($result)) . '" target="_blank">Просмотр</a></p></div>';
        }
    }

    // Тест API
    $api_test = null;
    if (isset($_POST['ycap_test_api']) && check_admin_referer('ycap_settings_nonce')) {
        $api_test = ycap_test_api($settings['api_endpoint'] ?? '');
    }
    ?>
    <div class="wrap">
        <h1>🛵 Яндекс Курьер - АвтоПостинг <small>v<?php echo YCAP_VERSION; ?></small></h1>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>⚙️ Настройки</h2>
            <form method="post">
                <?php wp_nonce_field('ycap_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th>API Endpoint</th>
                        <td>
                            <input type="url" name="api_endpoint" class="regular-text"
                                   value="<?php echo esc_attr($settings['api_endpoint'] ?? ''); ?>"
                                   placeholder="https://your-app.vercel.app/api/article-generator">
                            <p class="description">URL API сервера для генерации статей</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Статей в день</th>
                        <td>
                            <input type="number" name="articles_per_day" min="1" max="10"
                                   value="<?php echo esc_attr($settings['articles_per_day'] ?? 3); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Время публикации</th>
                        <td>
                            <input type="time" name="publication_time"
                                   value="<?php echo esc_attr($settings['publication_time'] ?? '10:00'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Реферальная ссылка</th>
                        <td>
                            <input type="url" name="referral_link" class="large-text"
                                   value="<?php echo esc_attr($settings['referral_link'] ?? 'https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>Категория</th>
                        <td>
                            <?php wp_dropdown_categories(array(
                                'name' => 'default_category',
                                'selected' => $settings['default_category'] ?? 1,
                                'show_option_none' => 'Выберите',
                                'hide_empty' => false
                            )); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Автор</th>
                        <td>
                            <?php wp_dropdown_users(array(
                                'name' => 'default_author',
                                'selected' => $settings['default_author'] ?? 1
                            )); ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Автопубликация</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_publish"
                                    <?php checked(!empty($settings['auto_publish'])); ?>>
                                Публиковать автоматически
                            </label>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="ycap_save_settings" class="button button-primary">Сохранить настройки</button>
                    <button type="submit" name="ycap_test_api" class="button">Проверить API</button>
                    <button type="submit" name="ycap_generate_now" class="button" style="background: #ffd500; border-color: #e6c200; color: #000; font-weight: bold; font-size: 14px; padding: 8px 15px;">🚀 Сгенерировать статью</button>
                </p>

                <?php if ($api_test !== null): ?>
                    <?php if ($api_test['success']): ?>
                        <div class="notice notice-success inline"><p>✅ API доступен! Провайдер: <?php echo esc_html($api_test['provider'] ?? 'YandexGPT'); ?> | Год: <?php echo esc_html($api_test['year'] ?? '2026'); ?></p></div>
                    <?php else: ?>
                        <div class="notice notice-error inline"><p>❌ Ошибка: <?php echo esc_html($api_test['error']); ?></p></div>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>

        <?php
        // Статистика
        $total = count(get_posts(array('meta_key' => '_ycap_generated', 'meta_value' => '1', 'numberposts' => -1, 'fields' => 'ids')));
        $today = count(get_posts(array('meta_key' => '_ycap_generated', 'meta_value' => '1', 'date_query' => array(array('after' => 'today')), 'numberposts' => -1, 'fields' => 'ids')));
        $next = wp_next_scheduled('ycap_daily_generation');

        // Последние статьи
        $recent = get_posts(array(
            'meta_key' => '_ycap_generated',
            'meta_value' => '1',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        ?>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📊 Статистика</h2>
            <table class="form-table">
                <tr><th>Всего статей</th><td><strong><?php echo esc_html($total); ?></strong></td></tr>
                <tr><th>Сегодня</th><td><strong><?php echo esc_html($today); ?></strong></td></tr>
                <tr><th>Следующая генерация</th><td><?php echo $next ? esc_html(get_date_from_gmt(date('Y-m-d H:i:s', $next))) : 'Не запланировано'; ?></td></tr>
            </table>
        </div>

        <?php if ($recent): ?>
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📝 Последние статьи</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Заголовок</th>
                        <th>Изображение</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $post): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                        </td>
                        <td>
                            <?php if (has_post_thumbnail($post->ID)): ?>
                                <span style="color: green;">✅ Есть</span>
                            <?php else: ?>
                                <span style="color: red;">❌ Нет</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(get_the_date('d.m.Y H:i', $post->ID)); ?></td>
                        <td>
                            <?php
                            $status = get_post_status($post->ID);
                            echo $status === 'publish' ? '<span style="color: green;">✅ Опубликовано</span>' : '<span style="color: orange;">📝 Черновик</span>';
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small">Редактировать</a>
                            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="button button-small" target="_blank">Просмотр</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>📋 Лог отладки</h2>
            <?php
            $log_file = WP_CONTENT_DIR . '/ycap_debug.log';
            if (file_exists($log_file)) {
                $log = file_get_contents($log_file);
                echo '<pre style="background: #f0f0f1; padding: 10px; max-height: 200px; overflow: auto; font-size: 12px;">' . esc_html($log) . '</pre>';
                echo '<form method="post"><button type="submit" name="ycap_clear_log" class="button">Очистить лог</button></form>';
            } else {
                echo '<p>Лог пуст</p>';
            }
            if (isset($_POST['ycap_clear_log'])) {
                file_put_contents($log_file, '');
                echo '<script>location.reload();</script>';
            }
            ?>
        </div>
    </div>
    <?php
}

// Тест API
function ycap_test_api($endpoint) {
    if (empty($endpoint)) {
        return array('success' => false, 'error' => 'API Endpoint не указан');
    }

    $response = wp_remote_get(rtrim($endpoint, '/') . '/generate', array('timeout' => 30, 'sslverify' => false));

    if (is_wp_error($response)) {
        return array('success' => false, 'error' => $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return array('success' => false, 'error' => "HTTP {$code}");
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return array(
        'success' => true,
        'provider' => $body['provider'] ?? 'Unknown',
        'year' => $body['year'] ?? 'Unknown'
    );
}

// Генерация статьи
function ycap_generate_article() {
    $settings = get_option('ycap_settings', array());
    $endpoint = $settings['api_endpoint'] ?? '';

    if (empty($endpoint)) {
        return new WP_Error('no_endpoint', 'API Endpoint не настроен');
    }

    ycap_log('Calling API: ' . rtrim($endpoint, '/') . '/full');

    $response = wp_remote_post(rtrim($endpoint, '/') . '/full', array(
        'timeout' => 180,
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode(array(
            'referralLink' => $settings['referral_link'] ?? ''
        )),
        'sslverify' => false
    ));

    if (is_wp_error($response)) {
        ycap_log('API Error: ' . $response->get_error_message());
        return new WP_Error('api_error', $response->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        $body = wp_remote_retrieve_body($response);
        ycap_log("API HTTP Error: {$code} - {$body}");
        return new WP_Error('http_error', "HTTP {$code}: {$body}");
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($body['success']) || empty($body['article'])) {
        ycap_log('Invalid API response: ' . print_r($body, true));
        return new WP_Error('invalid_response', 'Неверный ответ API');
    }

    $article = $body['article'];
    ycap_log('Article received: ' . $article['title']);
    ycap_log('Image URL: ' . ($article['image']['url'] ?? 'No image'));

    // Создаём пост
    $post_id = wp_insert_post(array(
        'post_title' => $article['title'],
        'post_content' => wp_kses_post($article['content']),
        'post_excerpt' => sanitize_textarea_field($article['excerpt'] ?? ''),
        'post_status' => !empty($settings['auto_publish']) ? 'publish' : 'draft',
        'post_author' => $settings['default_author'] ?? 1,
        'post_category' => array($settings['default_category'] ?? 1),
    ));

    if (is_wp_error($post_id)) {
        ycap_log('Post creation failed: ' . $post_id->get_error_message());
        return $post_id;
    }

    ycap_log('Post created: ' . $post_id);

    // Мета-данные
    update_post_meta($post_id, '_ycap_generated', '1');

    // SEO данные
    if (!empty($article['metaDescription'])) {
        update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($article['metaDescription']));
        update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($article['metaDescription']));
    }
    if (!empty($article['focusKeyword'])) {
        update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($article['focusKeyword']));
        update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($article['focusKeyword']));
    }

    // Загрузка изображения
    if (!empty($article['image']['url'])) {
        ycap_log('Starting image upload from: ' . $article['image']['url']);
        $image_result = ycap_upload_featured_image($post_id, $article['image']['url'], $article['title']);
        ycap_log('Image upload result: ' . ($image_result ? 'Success (' . $image_result . ')' : 'Failed'));
    } else {
        ycap_log('No image URL provided');
    }

    return $post_id;
}

// Загрузка изображения
function ycap_upload_featured_image($post_id, $image_url, $title) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    ycap_log('Downloading image from: ' . $image_url);

    // Скачиваем изображение
    $temp_file = download_url($image_url, 120);

    if (is_wp_error($temp_file)) {
        ycap_log('Download failed: ' . $temp_file->get_error_message());
        return false;
    }

    ycap_log('Image downloaded to: ' . $temp_file);

    // Проверяем размер файла
    $file_size = filesize($temp_file);
    ycap_log('File size: ' . $file_size . ' bytes');

    if ($file_size < 1000) {
        ycap_log('File too small, probably error');
        @unlink($temp_file);
        return false;
    }

    // Определяем расширение
    $file_type = wp_check_filetype($image_url);
    $ext = $file_type['ext'] ?: 'jpg';

    $file_array = array(
        'name' => 'ycap-' . $post_id . '-' . time() . '.' . $ext,
        'tmp_name' => $temp_file
    );

    ycap_log('Uploading to media library: ' . $file_array['name']);

    // Загрузка в медиабиблиотеку
    $attach_id = media_handle_sideload($file_array, $post_id, $title);

    if (is_wp_error($attach_id)) {
        ycap_log('Media upload failed: ' . $attach_id->get_error_message());
        @unlink($temp_file);
        return false;
    }

    ycap_log('Attachment created: ' . $attach_id);

    // Устанавливаем как featured image
    set_post_thumbnail($post_id, $attach_id);
    ycap_log('Featured image set for post ' . $post_id);

    // Alt текст
    update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($title));

    return $attach_id;
}

// Ежедневная генерация
add_action('ycap_daily_generation', function() {
    $settings = get_option('ycap_settings', array());
    $count = $settings['articles_per_day'] ?? 3;

    ycap_log('Daily generation started: ' . $count . ' articles');

    for ($i = 0; $i < $count; $i++) {
        ycap_generate_article();
        sleep(3);
    }

    ycap_log('Daily generation completed');
});
