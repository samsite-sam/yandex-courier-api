<?php
/**
 * Страница настроек плагина
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация настроек
 */
function ycap_register_settings() {
    register_setting('ycap_settings_group', 'ycap_settings', 'ycap_sanitize_settings');

    // Секция основных настроек
    add_settings_section(
        'ycap_main_section',
        'Основные настройки',
        'ycap_main_section_callback',
        'yandex-courier-auto-posts'
    );

    // Поля настроек
    add_settings_field(
        'api_endpoint',
        'API Endpoint',
        'ycap_api_endpoint_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'articles_per_day',
        'Статей в день',
        'ycap_articles_per_day_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'publication_time',
        'Время публикации',
        'ycap_publication_time_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'referral_link',
        'Реферальная ссылка',
        'ycap_referral_link_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'default_category',
        'Категория по умолчанию',
        'ycap_default_category_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'default_author',
        'Автор по умолчанию',
        'ycap_default_author_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'auto_publish',
        'Автопубликация',
        'ycap_auto_publish_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );

    add_settings_field(
        'image_optimization',
        'Оптимизация изображений',
        'ycap_image_optimization_callback',
        'yandex-courier-auto-posts',
        'ycap_main_section'
    );
}
add_action('admin_init', 'ycap_register_settings');

/**
 * Callback функции для полей
 */
function ycap_main_section_callback() {
    echo '<p>Настройте параметры автоматической генерации статей.</p>';
}

function ycap_api_endpoint_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : '';
    ?>
    <input type="url"
           name="ycap_settings[api_endpoint]"
           id="ycap_api_endpoint"
           value="<?php echo esc_url($value); ?>"
           class="regular-text"
           placeholder="https://your-api-server.com/api/article-generator">
    <p class="description">
        URL API сервера для генерации статей. Например: <code>https://your-server.com/api/article-generator</code>
    </p>
    <?php
}

function ycap_articles_per_day_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['articles_per_day']) ? $settings['articles_per_day'] : 3;
    ?>
    <input type="number"
           name="ycap_settings[articles_per_day]"
           id="ycap_articles_per_day"
           value="<?php echo esc_attr($value); ?>"
           min="1"
           max="10"
           class="small-text">
    <span>статей</span>
    <p class="description">Количество статей для генерации ежедневно (от 1 до 10).</p>
    <?php
}

function ycap_publication_time_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['publication_time']) ? $settings['publication_time'] : '10:00';
    ?>
    <input type="time"
           name="ycap_settings[publication_time]"
           id="ycap_publication_time"
           value="<?php echo esc_attr($value); ?>">
    <p class="description">Время ежедневной генерации статей.</p>
    <?php
}

function ycap_referral_link_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['referral_link']) ? $settings['referral_link'] : '';
    ?>
    <input type="url"
           name="ycap_settings[referral_link]"
           id="ycap_referral_link"
           value="<?php echo esc_url($value); ?>"
           class="large-text"
           placeholder="https://reg.eda.yandex.ru/?...">
    <p class="description">Реферальная ссылка Яндекс Еда для вставки в статьи.</p>
    <?php
}

function ycap_default_category_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['default_category']) ? $settings['default_category'] : 1;
    wp_dropdown_categories(array(
        'name' => 'ycap_settings[default_category]',
        'selected' => $value,
        'show_option_none' => 'Выберите категорию',
        'hide_empty' => false
    ));
    ?>
    <p class="description">Категория для публикуемых статей.</p>
    <?php
}

function ycap_default_author_callback() {
    $settings = get_option('ycap_settings', array());
    $value = isset($settings['default_author']) ? $settings['default_author'] : 1;
    wp_dropdown_users(array(
        'name' => 'ycap_settings[default_author]',
        'selected' => $value,
        'show_option_none' => 'Выберите автора'
    ));
    ?>
    <p class="description">Автор публикуемых статей.</p>
    <?php
}

function ycap_auto_publish_callback() {
    $settings = get_option('ycap_settings', array());
    $checked = isset($settings['auto_publish']) && $settings['auto_publish'];
    ?>
    <label>
        <input type="checkbox"
               name="ycap_settings[auto_publish]"
               id="ycap_auto_publish"
               <?php checked($checked); ?>>
        Публиковать статьи автоматически
    </label>
    <p class="description">Если выключено, статьи сохраняются как черновики.</p>
    <?php
}

function ycap_image_optimization_callback() {
    $settings = get_option('ycap_settings', array());
    $checked = isset($settings['image_optimization']) && $settings['image_optimization'];
    ?>
    <label>
        <input type="checkbox"
               name="ycap_settings[image_optimization]"
               id="ycap_image_optimization"
               <?php checked($checked); ?>>
        Конвертировать изображения в WebP
    </label>
    <p class="description">Оптимизация изображений для ускорения загрузки.</p>
    <?php
}

/**
 * Санитизация настроек
 */
function ycap_sanitize_settings($input) {
    $sanitized = array();

    if (isset($input['api_endpoint'])) {
        $sanitized['api_endpoint'] = esc_url_raw($input['api_endpoint']);
    }

    if (isset($input['articles_per_day'])) {
        $sanitized['articles_per_day'] = absint($input['articles_per_day']);
        $sanitized['articles_per_day'] = min(max($sanitized['articles_per_day'], 1), 10);
    }

    if (isset($input['publication_time'])) {
        $sanitized['publication_time'] = sanitize_text_field($input['publication_time']);
    }

    if (isset($input['referral_link'])) {
        $sanitized['referral_link'] = esc_url_raw($input['referral_link']);
    }

    if (isset($input['default_category'])) {
        $sanitized['default_category'] = absint($input['default_category']);
    }

    if (isset($input['default_author'])) {
        $sanitized['default_author'] = absint($input['default_author']);
    }

    $sanitized['auto_publish'] = isset($input['auto_publish']) && $input['auto_publish'];
    $sanitized['image_optimization'] = isset($input['image_optimization']) && $input['image_optimization'];

    // Перепланируем генерацию при изменении времени
    if (isset($input['publication_time'])) {
        YCAP_Scheduler::reschedule($input['publication_time']);
    }

    return $sanitized;
}
