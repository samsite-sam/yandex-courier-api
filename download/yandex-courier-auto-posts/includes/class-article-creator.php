<?php
/**
 * Создание статей в WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class YCAP_Article_Creator {

    /**
     * Настройки
     */
    private $settings;

    /**
     * Конструктор
     */
    public function __construct() {
        $this->settings = get_option('ycap_settings', array());
    }

    /**
     * Создание статьи из данных API
     */
    public function create($article_data) {
        if (!is_array($article_data)) {
            return new WP_Error('invalid_data', 'Неверные данные статьи');
        }

        // Подготовка данных поста
        $post_data = array(
            'post_title' => sanitize_text_field($article_data['title']),
            'post_content' => $this->prepare_content($article_data['content']),
            'post_excerpt' => sanitize_textarea_field($article_data['excerpt']),
            'post_status' => $this->get_setting('auto_publish', true) ? 'publish' : 'draft',
            'post_author' => $this->get_setting('default_author', 1),
            'post_category' => array($this->get_setting('default_category', 1)),
            'post_type' => 'post',
            'comment_status' => 'open'
        );

        // Создаём пост
        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Добавляем метаданные
        $this->add_metadata($post_id, $article_data);

        // Загружаем и прикрепляем изображение
        if (isset($article_data['image'])) {
            $this->attach_featured_image($post_id, $article_data['image'], $article_data['title']);
        }

        // Интеграция с SEO плагинами
        $this->add_seo_data($post_id, $article_data);

        // Метка сгенерированной статьи
        update_post_meta($post_id, '_ycap_generated', '1');
        update_post_meta($post_id, '_ycap_generated_at', current_time('mysql'));

        // Хук для дополнительных действий
        do_action('ycap_article_created', $post_id, $article_data);

        return $post_id;
    }

    /**
     * Подготовка контента
     */
    private function prepare_content($content) {
        // Очищаем от потенциально опасного контента
        $allowed_html = array(
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'p' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'strong' => array(),
            'em' => array(),
            'b' => array(),
            'i' => array(),
            'br' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
                'rel' => array(),
                'style' => array()
            ),
            'div' => array(
                'style' => array(),
                'class' => array()
            ),
            'span' => array(
                'style' => array(),
                'class' => array()
            ),
            'blockquote' => array(),
            'table' => array(),
            'tr' => array(),
            'td' => array(),
            'th' => array()
        );

        return wp_kses($content, $allowed_html);
    }

    /**
     * Добавление метаданных
     */
    private function add_metadata($post_id, $article_data) {
        // Ключевые слова как метки
        if (isset($article_data['keywords']) && is_array($article_data['keywords'])) {
            $tags = array_map('sanitize_text_field', $article_data['keywords']);
            wp_set_post_tags($post_id, $tags, true);
        }

        // Фокусное ключевое слово
        if (isset($article_data['focusKeyword'])) {
            update_post_meta($post_id, '_ycap_focus_keyword', sanitize_text_field($article_data['focusKeyword']));
        }

        // Количество слов
        if (isset($article_data['metadata']['wordCount'])) {
            update_post_meta($post_id, '_ycap_word_count', intval($article_data['metadata']['wordCount']));
        }
    }

    /**
     * Загрузка и прикрепление изображения
     */
    private function attach_featured_image($post_id, $image_data, $title) {
        if (!isset($image_data['url'])) {
            return false;
        }

        $image_url = esc_url_raw($image_data['url']);
        $alt_text = isset($image_data['alt']) ? sanitize_text_field($image_data['alt']) : $title;

        // Загружаем изображение
        $attachment_id = $this->download_image($image_url, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            do_action('ycap_log', 'warning', 'Не удалось загрузить изображение: ' . $attachment_id->get_error_message());
            return false;
        }

        // Устанавливаем как featured image
        set_post_thumbnail($post_id, $attachment_id);

        // Обновляем alt текст
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

        return $attachment_id;
    }

    /**
     * Загрузка изображения с внешнего URL
     */
    private function download_image($url, $post_id, $title) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Временный файл
        $temp_file = download_url($url, 60);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Подготовка файла для загрузки
        $file_array = array(
            'name' => 'ycap-' . $post_id . '-' . time() . '.jpg',
            'tmp_name' => $temp_file
        );

        // Если включена оптимизация, конвертируем в WebP
        if ($this->get_setting('image_optimization', true)) {
            $file_array = $this->optimize_image($file_array);
        }

        // Загрузка в медиабиблиотеку
        $attachment_id = media_handle_sideload($file_array, $post_id, $title);

        if (is_wp_error($attachment_id)) {
            @unlink($temp_file);
            return $attachment_id;
        }

        return $attachment_id;
    }

    /**
     * Оптимизация изображения (конвертация в WebP)
     */
    private function optimize_image($file_array) {
        // Проверяем поддержку WebP
        if (!function_exists('imagewebp')) {
            return $file_array;
        }

        $temp_path = $file_array['tmp_name'];

        // Получаем информацию об изображении
        $image_info = @getimagesize($temp_path);

        if (!$image_info) {
            return $file_array;
        }

        // Создаём изображение в зависимости от типа
        $image = null;
        switch ($image_info[2]) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($temp_path);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($temp_path);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($temp_path);
                break;
            default:
                return $file_array;
        }

        if (!$image) {
            return $file_array;
        }

        // Конвертируем в WebP
        $webp_path = $temp_path . '.webp';
        $quality = apply_filters('ycap_webp_quality', 85);

        if (@imagewebp($image, $webp_path, $quality)) {
            @unlink($temp_path);
            imagedestroy($image);
            rename($webp_path, $temp_path);
            $file_array['name'] = preg_replace('/\.[^.]+$/', '.webp', $file_array['name']);
        } else {
            imagedestroy($image);
        }

        return $file_array;
    }

    /**
     * Добавление SEO данных
     */
    private function add_seo_data($post_id, $article_data) {
        // Rank Math SEO
        if (class_exists('RankMath')) {
            $this->add_rankmath_data($post_id, $article_data);
        }

        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            $this->add_yoast_data($post_id, $article_data);
        }

        // All in One SEO
        if (class_exists('AIOSEO\Plugin\AIOSEO')) {
            $this->add_aioseo_data($post_id, $article_data);
        }

        // Общие метаданные для любого SEO плагина
        update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($article_data['title']));
        update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($article_data['metaDescription']));
        update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($article_data['focusKeyword']));
    }

    /**
     * Rank Math SEO
     */
    private function add_rankmath_data($post_id, $article_data) {
        update_post_meta($post_id, 'rank_math_title', sanitize_text_field($article_data['title']));
        update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($article_data['metaDescription']));
        update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($article_data['focusKeyword']));

        // Schema данные
        $schema = array(
            '@type' => 'Article',
            'headline' => $article_data['title'],
            'description' => $article_data['metaDescription'],
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            )
        );
        update_post_meta($post_id, 'rank_math_schema_article', wp_json_encode($schema));
    }

    /**
     * Yoast SEO
     */
    private function add_yoast_data($post_id, $article_data) {
        update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($article_data['title']));
        update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field($article_data['metaDescription']));
        update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($article_data['focusKeyword']));
        update_post_meta($post_id, '_yoast_wpseo_linkdex', '100'); // SEO Score
    }

    /**
     * All in One SEO
     */
    private function add_aioseo_data($post_id, $article_data) {
        $aioseo = get_post_meta($post_id, '_aioseo_posts', true);

        if (!is_array($aioseo)) {
            $aioseo = array();
        }

        $aioseo['title'] = sanitize_text_field($article_data['title']);
        $aioseo['description'] = sanitize_textarea_field($article_data['metaDescription']);
        $aioseo['keywords'] = isset($article_data['keywords']) ? implode(',', $article_data['keywords']) : '';

        update_post_meta($post_id, '_aioseo_posts', $aioseo);
    }

    /**
     * Получить настройку
     */
    private function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
}
