<?php
/**
 * Логирование событий плагина
 */

if (!defined('ABSPATH')) {
    exit;
}

class YCAP_Logger {

    /**
     * Имя таблицы логов
     */
    private static $table_name = 'ycap_logs';

    /**
     * Логирование события
     */
    public static function log($level, $message, $context = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // Проверяем существование таблицы
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_tables();
        }

        $data = array(
            'level' => sanitize_text_field($level),
            'message' => sanitize_text_field($message),
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($table_name, $data);
    }

    /**
     * Создание таблицы логов
     */
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Получение логов
     */
    public static function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'level' => '',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . self::$table_name;

        $where = '1=1';
        if (!empty($args['level'])) {
            $where .= $wpdb->prepare(' AND level = %s', $args['level']);
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Получение количества логов
     */
    public static function get_count($level = '') {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        if (!empty($level)) {
            return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE level = %s", $level));
        }

        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Очистка старых логов
     */
    public static function cleanup($days = 30) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE created_at < %s", $date));
    }

    /**
     * Получение статистики по уровням
     */
    public static function get_stats() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $stats = $wpdb->get_results("SELECT level, COUNT(*) as count FROM $table_name GROUP BY level");

        $result = array(
            'info' => 0,
            'warning' => 0,
            'error' => 0
        );

        foreach ($stats as $stat) {
            $result[$stat->level] = $stat->count;
        }

        return $result;
    }
}
