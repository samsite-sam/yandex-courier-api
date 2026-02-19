<?php
/**
 * Страница настроек плагина
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('ycap_settings', array());
?>

<div class="wrap ycap-admin-wrap">
    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        Настройки - Яндекс Курьер АвтоПостинг
    </h1>

    <div class="ycap-settings-container">
        <form method="post" action="options.php">
            <?php
            settings_fields('ycap_settings_group');
            do_settings_sections('yandex-courier-auto-posts');
            submit_button('Сохранить настройки');
            ?>
        </form>

        <div class="ycap-settings-help">
            <h3>Справка по настройке</h3>

            <div class="ycap-help-section">
                <h4>API Endpoint</h4>
                <p>
                    Укажите URL вашего API сервера для генерации статей.
                    Этот сервер будет обрабатывать запросы на генерацию контента.
                </p>
                <p><strong>Пример:</strong> <code>https://your-server.com/api/article-generator</code></p>
            </div>

            <div class="ycap-help-section">
                <h4>Статей в день</h4>
                <p>
                    Количество статей, которые будут генерироваться автоматически каждый день.
                    Рекомендуется 3-5 статей для естественного роста сайта.
                </p>
            </div>

            <div class="ycap-help-section">
                <h4>Время публикации</h4>
                <p>
                    Время, когда будет запускаться генерация статей.
                    Рекомендуется выбирать утренние часы (9-11 утра) для лучшей индексации.
                </p>
            </div>

            <div class="ycap-help-section">
                <h4>Реферальная ссылка</h4>
                <p>
                    Ваша реферальная ссылка Яндекс Еда. Она будет автоматически вставляться
                    в статьи с призывом к действию "Стать курьером".
                </p>
            </div>

            <div class="ycap-help-section">
                <h4>Оптимизация изображений</h4>
                <p>
                    Автоматическая конвертация изображений в формат WebP для ускорения
                    загрузки сайта. Требует поддержки WebP на сервере.
                </p>
            </div>

            <div class="ycap-help-section">
                <h4>SEO интеграция</h4>
                <p>
                    Плагин автоматически интегрируется с популярными SEO плагинами:
                </p>
                <ul>
                    <li><strong>Rank Math</strong> - полная поддержка</li>
                    <li><strong>Yoast SEO</strong> - полная поддержка</li>
                    <li><strong>All in One SEO</strong> - базовая поддержка</li>
                </ul>
            </div>
        </div>
    </div>
</div>
