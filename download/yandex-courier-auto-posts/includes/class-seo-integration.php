<?php
/**
 * Интеграция с SEO плагинами
 */

if (!defined('ABSPATH')) {
    exit;
}

class YCAP_SEO_Integration {

    /**
     * Конструктор
     */
    public function __construct() {
        // Хуки для различных SEO плагинов
        add_filter('rank_math/frontend/title', array($this, 'filter_rankmath_title'), 10, 1);
        add_filter('rank_math/frontend/description', array($this, 'filter_rankmath_description'), 10, 1);

        // Sitemap интеграция
        add_filter('wp_sitemaps_posts_query_args', array($this, 'filter_sitemap_args'), 10, 2);
    }

    /**
     * Фильтр заголовка Rank Math
     */
    public function filter_rankmath_title($title) {
        if (is_single()) {
            $custom_title = get_post_meta(get_the_ID(), 'rank_math_title', true);
            if ($custom_title) {
                return $custom_title;
            }
        }
        return $title;
    }

    /**
     * Фильтр описания Rank Math
     */
    public function filter_rankmath_description($description) {
        if (is_single()) {
            $custom_desc = get_post_meta(get_the_ID(), 'rank_math_description', true);
            if ($custom_desc) {
                return $custom_desc;
            }
        }
        return $description;
    }

    /**
     * Фильтр аргументов sitemap
     */
    public function filter_sitemap_args($args, $post_type) {
        if ($post_type === 'post') {
            // Включаем сгенерированные статьи в sitemap
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_ycap_generated',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_ycap_generated',
                    'value' => '1'
                )
            );
        }
        return $args;
    }

    /**
     * Генерация Schema.org разметки
     */
    public static function generate_schema($post_id, $article_data) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article_data['title'],
            'description' => $article_data['metaDescription'],
            'image' => isset($article_data['image']['url']) ? $article_data['image']['url'] : '',
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            ),
            'keywords' => isset($article_data['keywords']) ? implode(', ', $article_data['keywords']) : ''
        );

        return $schema;
    }

    /**
     * Вывод Schema разметки в head
     */
    public static function output_schema() {
        if (!is_single()) {
            return;
        }

        $post_id = get_the_ID();
        $is_generated = get_post_meta($post_id, '_ycap_generated', true);

        if (!$is_generated) {
            return;
        }

        $schema_json = get_post_meta($post_id, '_ycap_schema', true);

        if ($schema_json) {
            echo '<script type="application/ld+json">' . $schema_json . '</script>';
        }
    }

    /**
     * Генерация Open Graph тегов
     */
    public static function generate_open_graph($post_id, $article_data) {
        return array(
            'og:title' => $article_data['title'],
            'og:description' => $article_data['metaDescription'],
            'og:type' => 'article',
            'og:url' => get_permalink($post_id),
            'og:image' => isset($article_data['image']['url']) ? $article_data['image']['url'] : '',
            'og:site_name' => get_bloginfo('name'),
            'og:locale' => 'ru_RU',
            'article:published_time' => get_the_date('c', $post_id),
            'article:modified_time' => get_the_modified_date('c', $post_id),
            'article:author' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
        );
    }

    /**
     * Генерация Twitter Card тегов
     */
    public static function generate_twitter_card($post_id, $article_data) {
        return array(
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $article_data['title'],
            'twitter:description' => $article_data['metaDescription'],
            'twitter:image' => isset($article_data['image']['url']) ? $article_data['image']['url'] : ''
        );
    }
}

// Инициализация
add_action('wp_head', array('YCAP_SEO_Integration', 'output_schema'));
