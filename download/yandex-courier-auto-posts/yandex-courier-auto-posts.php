<?php
/**
 * Plugin Name: Яндекс Курьер - АвтоПостинг
 * Plugin URI: https://eda---yandex.ru/
 * Description: Автоматическая генерация и публикация SEO-оптимизированных статей о работе курьером в Яндекс Еда.
 * Version: 1.0.0
 * Author: Yandex Courier Team
 * Author URI: https://eda---yandex.ru/
 * License: GPL v2 or later
 * Text Domain: yandex-courier-auto-posts
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Запрещаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('YCAP_VERSION', '1.0.0');
define('YCAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YCAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YCAP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Главный класс плагина
 */
class Yandex_Courier_Auto_Posts {

    /**
     * Единственный экземпляр класса
     */
    private static $instance = null;

    /**
     * Настройки плагина
     */
    private $settings;

    /**
     * Получить экземпляр класса
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->load_settings();
    }

    /**
     * Загрузка зависимостей
     */
    private function load_dependencies() {
        require_once YCAP_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once YCAP_PLUGIN_DIR . 'includes/class-article-creator.php';
        require_once YCAP_PLUGIN_DIR . 'includes/class-scheduler.php';
        require_once YCAP_PLUGIN_DIR . 'includes/class-seo-integration.php';
        require_once YCAP_PLUGIN_DIR . 'includes/class-logger.php';
        require_once YCAP_PLUGIN_DIR . 'includes/admin-settings.php';
    }

    /**
     * Инициализация хуков
     */
    private function init_hooks() {
        // Активация/деактивация
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Админка
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX обработчики
        add_action('wp_ajax_ycap_generate_article', array($this, 'ajax_generate_article'));
        add_action('wp_ajax_ycap_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_ycap_test_api', array($this, 'ajax_test_api'));

        // Планировщик
        add_action('ycap_daily_generation', array($this, 'generate_daily_articles'));

        // Логирование
        add_action('ycap_log', array('YCAP_Logger', 'log'), 10, 2);
    }

    /**
     * Загрузка настроек
     */
    private function load_settings() {
        $this->settings = get_option('ycap_settings', array(
            'api_endpoint' => '',
            'articles_per_day' => 3,
            'publication_time' => '10:00',
            'referral_link' => 'https://reg.eda.yandex.ru/?advertisement_campaign=forms_for_agents&user_invite_code=7dc31006022f4ab4bfa385dbfcc893b2&utm_content=blank',
            'default_category' => 1,
            'default_author' => 1,
            'auto_publish' => true,
            'image_optimization' => true,
        ));
    }

    /**
     * Получить настройку
     */
    public function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Активация плагина
     */
    public function activate() {
        // Создаём таблицы логов
        YCAP_Logger::create_tables();

        // Планируем ежедневную генерацию
        YCAP_Scheduler::schedule_daily();

        // Логируем активацию
        do_action('ycap_log', 'info', 'Плагин активирован');

        // Сохраняем версию
        update_option('ycap_version', YCAP_VERSION);
    }

    /**
     * Деактивация плагина
     */
    public function deactivate() {
        // Удаляем запланированные задачи
        YCAP_Scheduler::clear_scheduled();

        do_action('ycap_log', 'info', 'Плагин деактивирован');
    }

    /**
     * Добавление меню в админку
     */
    public function add_admin_menu() {
        add_menu_page(
            'Яндекс Курьер - АвтоПостинг',
            'АвтоПостинг',
            'manage_options',
            'yandex-courier-auto-posts',
            array($this, 'render_admin_page'),
            'dashicons-migrate',
            30
        );

        add_submenu_page(
            'yandex-courier-auto-posts',
            'Настройки',
            'Настройки',
            'manage_options',
            'yandex-courier-auto-posts-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'yandex-courier-auto-posts',
            'Журнал',
            'Журнал',
            'manage_options',
            'yandex-courier-auto-posts-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Регистрация настроек
     */
    public function register_settings() {
        register_setting('ycap_settings_group', 'ycap_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Санитизация настроек
     */
    public function sanitize_settings($input) {
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

        return $sanitized;
    }

    /**
     * Подключение стилей и скриптов админки
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'yandex-courier-auto-posts') === false) {
            return;
        }

        wp_enqueue_style(
            'ycap-admin-style',
            YCAP_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            YCAP_VERSION
        );

        wp_enqueue_script(
            'ycap-admin-script',
            YCAP_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            YCAP_VERSION,
            true
        );

        wp_localize_script('ycap-admin-script', 'ycapAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ycap_nonce'),
            'messages' => array(
                'generating' => 'Генерация статьи...',
                'success' => 'Статья успешно создана!',
                'error' => 'Ошибка при генерации',
                'testing' => 'Проверка соединения...',
                'test_success' => 'API доступен!',
                'test_error' => 'API недоступен'
            )
        ));
    }

    /**
     * Рендер главной страницы админки
     */
    public function render_admin_page() {
        include YCAP_PLUGIN_DIR . 'includes/admin-page-main.php';
    }

    /**
     * Рендер страницы настроек
     */
    public function render_settings_page() {
        include YCAP_PLUGIN_DIR . 'includes/admin-page-settings.php';
    }

    /**
     * Рендер страницы журнала
     */
    public function render_logs_page() {
        include YCAP_PLUGIN_DIR . 'includes/admin-page-logs.php';
    }

    /**
     * AJAX: Генерация статьи
     */
    public function ajax_generate_article() {
        check_ajax_referer('ycap_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостаточно прав'));
        }

        $api_client = new YCAP_API_Client();
        $result = $api_client->generate_article();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        // Создаём статью
        $creator = new YCAP_Article_Creator();
        $post_id = $creator->create($result);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Статья создана',
            'post_id' => $post_id,
            'edit_link' => get_edit_post_link($post_id)
        ));
    }

    /**
     * AJAX: Получение статистики
     */
    public function ajax_get_stats() {
        check_ajax_referer('ycap_nonce', 'nonce');

        $stats = array(
            'total_articles' => $this->get_total_articles(),
            'articles_today' => $this->get_articles_today(),
            'last_generation' => get_option('ycap_last_generation', 'Никогда'),
            'next_scheduled' => $this->get_next_scheduled()
        );

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Проверка API
     */
    public function ajax_test_api() {
        check_ajax_referer('ycap_nonce', 'nonce');

        $api_client = new YCAP_API_Client();
        $result = $api_client->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'API доступен', 'response' => $result));
    }

    /**
     * Ежедневная генерация статей
     */
    public function generate_daily_articles() {
        $articles_per_day = $this->get_setting('articles_per_day', 3);

        do_action('ycap_log', 'info', sprintf('Начало генерации %d статей', $articles_per_day));

        $api_client = new YCAP_API_Client();
        $creator = new YCAP_Article_Creator();

        for ($i = 0; $i < $articles_per_day; $i++) {
            $result = $api_client->generate_article();

            if (is_wp_error($result)) {
                do_action('ycap_log', 'error', 'Ошибка генерации: ' . $result->get_error_message());
                continue;
            }

            $post_id = $creator->create($result);

            if (is_wp_error($post_id)) {
                do_action('ycap_log', 'error', 'Ошибка создания статьи: ' . $post_id->get_error_message());
            } else {
                do_action('ycap_log', 'info', sprintf('Статья #%d создана (ID: %d)', $i + 1, $post_id));
            }

            // Небольшая пауза между генерациями
            sleep(2);
        }

        update_option('ycap_last_generation', current_time('mysql'));

        do_action('ycap_log', 'info', 'Генерация завершена');
    }

    /**
     * Получить общее количество сгенерированных статей
     */
    private function get_total_articles() {
        return count(get_posts(array(
            'post_type' => 'post',
            'meta_key' => '_ycap_generated',
            'meta_value' => '1',
            'numberposts' => -1,
            'fields' => 'ids'
        )));
    }

    /**
     * Получить количество статей за сегодня
     */
    private function get_articles_today() {
        return count(get_posts(array(
            'post_type' => 'post',
            'meta_key' => '_ycap_generated',
            'meta_value' => '1',
            'date_query' => array(
                array('after' => 'today')
            ),
            'numberposts' => -1,
            'fields' => 'ids'
        )));
    }

    /**
     * Получить следующее запланированное время
     */
    private function get_next_scheduled() {
        $timestamp = wp_next_scheduled('ycap_daily_generation');
        return $timestamp ? get_date_from_gmt(date('Y-m-d H:i:s', $timestamp)) : 'Не запланировано';
    }
}

/**
 * Инициализация плагина
 */
function ycap_init() {
    return Yandex_Courier_Auto_Posts::get_instance();
}

// Запуск плагина
add_action('plugins_loaded', 'ycap_init');

// Быстрый доступ к экземпляру
function ycap() {
    return Yandex_Courier_Auto_Posts::get_instance();
}
