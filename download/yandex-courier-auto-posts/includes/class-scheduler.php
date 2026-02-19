<?php
/**
 * Планировщик задач
 */

if (!defined('ABSPATH')) {
    exit;
}

class YCAP_Scheduler {

    /**
     * Запланировать ежедневную генерацию
     */
    public static function schedule_daily() {
        $settings = get_option('ycap_settings', array());
        $time = isset($settings['publication_time']) ? $settings['publication_time'] : '10:00';

        // Парсим время
        $time_parts = explode(':', $time);
        $hour = isset($time_parts[0]) ? intval($time_parts[0]) : 10;
        $minute = isset($time_parts[1]) ? intval($time_parts[1]) : 0;

        // Создаём timestamp для следующего запуска
        $timestamp = mktime($hour, $minute, 0, date('n'), date('j'), date('Y'));

        // Если время уже прошло сегодня, планируем на завтра
        if ($timestamp <= time()) {
            $timestamp = mktime($hour, $minute, 0, date('n'), date('j') + 1, date('Y'));
        }

        // Очищаем старые расписания
        wp_clear_scheduled_hook('ycap_daily_generation');

        // Планируем новое событие
        wp_schedule_event($timestamp, 'daily', 'ycap_daily_generation');

        // Логируем
        do_action('ycap_log', 'info', sprintf(
            'Запланирована генерация на %s',
            get_date_from_gmt(date('Y-m-d H:i:s', $timestamp))
        ));
    }

    /**
     * Очистить запланированные задачи
     */
    public static function clear_scheduled() {
        wp_clear_scheduled_hook('ycap_daily_generation');
        wp_clear_scheduled_hook('ycap_hourly_check');
    }

    /**
     * Получить следующее запланированное время
     */
    public static function get_next_scheduled() {
        return wp_next_scheduled('ycap_daily_generation');
    }

    /**
     * Проверить, запущена ли генерация
     */
    public static function is_running() {
        return get_transient('ycap_generation_running') !== false;
    }

    /**
     * Установить флаг генерации
     */
    public static function set_running($running = true) {
        if ($running) {
            set_transient('ycap_generation_running', true, 600); // 10 минут максимум
        } else {
            delete_transient('ycap_generation_running');
        }
    }

    /**
     * Ручной запуск генерации
     */
    public static function trigger_manual() {
        if (self::is_running()) {
            return new WP_Error('already_running', 'Генерация уже выполняется');
        }

        self::set_running(true);

        try {
            ycap()->generate_daily_articles();
        } catch (Exception $e) {
            self::set_running(false);
            return new WP_Error('generation_error', $e->getMessage());
        }

        self::set_running(false);
        return true;
    }

    /**
     * Перепланировать на новое время
     */
    public static function reschedule($new_time) {
        $settings = get_option('ycap_settings', array());
        $settings['publication_time'] = sanitize_text_field($new_time);
        update_option('ycap_settings', $settings);

        self::schedule_daily();

        return true;
    }
}
