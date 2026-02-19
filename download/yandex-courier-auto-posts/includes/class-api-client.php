<?php
/**
 * API Client для генерации статей
 */

if (!defined('ABSPATH')) {
    exit;
}

class YCAP_API_Client {

    /**
     * URL API эндпоинта
     */
    private $api_endpoint;

    /**
     * Реферальная ссылка
     */
    private $referral_link;

    /**
     * Таймаут запроса
     */
    private $timeout = 120;

    /**
     * Конструктор
     */
    public function __construct() {
        $settings = get_option('ycap_settings', array());
        $this->api_endpoint = isset($settings['api_endpoint']) ? $settings['api_endpoint'] : '';
        $this->referral_link = isset($settings['referral_link']) ? $settings['referral_link'] : '';
    }

    /**
     * Проверка соединения с API
     */
    public function test_connection() {
        if (empty($this->api_endpoint)) {
            return new WP_Error('no_endpoint', 'API endpoint не настроен');
        }

        $test_url = trailingslashit($this->api_endpoint) . '../generate';

        $response = wp_remote_get($test_url, array(
            'timeout' => 30,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return new WP_Error('connection_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new WP_Error('api_error', sprintf('API вернул код %d', $code));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }

    /**
     * Генерация статьи
     */
    public function generate_article($topic = null) {
        if (empty($this->api_endpoint)) {
            return new WP_Error('no_endpoint', 'API endpoint не настроен. Перейдите в Настройки для конфигурации.');
        }

        $endpoint = trailingslashit($this->api_endpoint) . 'full';

        $body = array(
            'referralLink' => $this->referral_link
        );

        if ($topic) {
            $body['topic'] = $topic;
        }

        $response = wp_remote_post($endpoint, array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return new WP_Error('request_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            $error_body = wp_remote_retrieve_body($response);
            $error_data = json_decode($error_body, true);
            $error_message = isset($error_data['error']) ? $error_data['error'] : "HTTP {$code}";
            return new WP_Error('api_error', $error_message);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Ошибка парсинга ответа API');
        }

        if (!isset($data['success']) || !$data['success']) {
            return new WP_Error('api_response', 'Некорректный ответ API');
        }

        return $data['article'];
    }

    /**
     * Генерация нескольких статей
     */
    public function generate_articles($count = 3) {
        $endpoint = trailingslashit($this->api_endpoint) . 'full';
        $endpoint = add_query_arg('count', $count, $endpoint);
        $endpoint = add_query_arg('referralLink', urlencode($this->referral_link), $endpoint);

        $response = wp_remote_get($endpoint, array(
            'timeout' => $this->timeout * $count,
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return new WP_Error('request_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new WP_Error('api_error', "HTTP {$code}");
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['success']) || !$data['success']) {
            return new WP_Error('api_response', 'Некорректный ответ API');
        }

        return $data['articles'];
    }

    /**
     * Поиск изображения
     */
    public function search_image($query = null) {
        if (empty($this->api_endpoint)) {
            return new WP_Error('no_endpoint', 'API endpoint не настроен');
        }

        $endpoint = trailingslashit($this->api_endpoint) . 'image';

        $body = array();
        if ($query) {
            $body['query'] = $query;
        }

        $response = wp_remote_post($endpoint, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'sslverify' => false
        ));

        if (is_wp_error($response)) {
            return new WP_Error('request_error', $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new WP_Error('api_error', "HTTP {$code}");
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }
}
