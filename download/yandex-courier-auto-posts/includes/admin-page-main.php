<?php
/**
 * Главная страница админки плагина
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = array(
    'total_articles' => 0,
    'articles_today' => 0,
    'last_generation' => get_option('ycap_last_generation', 'Никогда'),
    'next_scheduled' => 'Не запланировано'
);

// Получаем статистику
$total = count(get_posts(array(
    'post_type' => 'post',
    'meta_key' => '_ycap_generated',
    'meta_value' => '1',
    'numberposts' => -1,
    'fields' => 'ids'
)));

$today = count(get_posts(array(
    'post_type' => 'post',
    'meta_key' => '_ycap_generated',
    'meta_value' => '1',
    'date_query' => array(array('after' => 'today')),
    'numberposts' => -1,
    'fields' => 'ids'
)));

$next_scheduled = wp_next_scheduled('ycap_daily_generation');
$next_scheduled_str = $next_scheduled ? get_date_from_gmt(date('Y-m-d H:i:s', $next_scheduled)) : 'Не запланировано';

$settings = get_option('ycap_settings', array());
$api_configured = !empty($settings['api_endpoint']);
?>

<div class="wrap ycap-admin-wrap">
    <h1>
        <span class="dashicons dashicons-migrate"></span>
        Яндекс Курьер - АвтоПостинг
    </h1>

    <?php if (!$api_configured): ?>
    <div class="notice notice-warning is-dismissible">
        <p>
            <strong>Требуется настройка!</strong>
            Перейдите в <a href="?page=yandex-courier-auto-posts-settings">Настройки</a> и укажите API endpoint.
        </p>
    </div>
    <?php endif; ?>

    <div class="ycap-dashboard">
        <!-- Статистика -->
        <div class="ycap-stats-grid">
            <div class="ycap-stat-card">
                <div class="ycap-stat-icon dashicons dashicons-analytics"></div>
                <div class="ycap-stat-content">
                    <div class="ycap-stat-number"><?php echo esc_html($total); ?></div>
                    <div class="ycap-stat-label">Всего статей</div>
                </div>
            </div>

            <div class="ycap-stat-card">
                <div class="ycap-stat-icon dashicons dashicons-clock"></div>
                <div class="ycap-stat-content">
                    <div class="ycap-stat-number"><?php echo esc_html($today); ?></div>
                    <div class="ycap-stat-label">Сегодня</div>
                </div>
            </div>

            <div class="ycap-stat-card">
                <div class="ycap-stat-icon dashicons dashicons-calendar-alt"></div>
                <div class="ycap-stat-content">
                    <div class="ycap-stat-value"><?php echo esc_html($next_scheduled_str); ?></div>
                    <div class="ycap-stat-label">Следующая генерация</div>
                </div>
            </div>

            <div class="ycap-stat-card">
                <div class="ycap-stat-icon dashicons dashicons-yes-alt"></div>
                <div class="ycap-stat-content">
                    <div class="ycap-stat-value"><?php echo esc_html(get_option('ycap_last_generation', 'Никогда')); ?></div>
                    <div class="ycap-stat-label">Последняя генерация</div>
                </div>
            </div>
        </div>

        <!-- Действия -->
        <div class="ycap-actions-panel">
            <h2>Быстрые действия</h2>

            <div class="ycap-actions-grid">
                <div class="ycap-action-card">
                    <h3>Генерация статей</h3>
                    <p>Сгенерировать статьи вручную прямо сейчас.</p>
                    <button type="button"
                            class="button button-primary button-hero ycap-generate-btn"
                            <?php disabled(!$api_configured); ?>>
                        <span class="dashicons dashicons-plus-alt2"></span>
                        Сгенерировать статью
                    </button>
                    <div class="ycap-progress" style="display: none;">
                        <div class="ycap-progress-bar"></div>
                        <span class="ycap-progress-text">Генерация...</span>
                    </div>
                </div>

                <div class="ycap-action-card">
                    <h3>Проверка API</h3>
                    <p>Проверить соединение с API сервером.</p>
                    <button type="button"
                            class="button button-secondary ycap-test-api-btn"
                            <?php disabled(!$api_configured); ?>>
                        <span class="dashicons dashicons-admin-links"></span>
                        Проверить соединение
                    </button>
                    <div class="ycap-api-status"></div>
                </div>

                <div class="ycap-action-card">
                    <h3>Настройки</h3>
                    <p>Изменить параметры генерации статей.</p>
                    <a href="?page=yandex-courier-auto-posts-settings" class="button button-secondary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Открыть настройки
                    </a>
                </div>

                <div class="ycap-action-card">
                    <h3>Журнал</h3>
                    <p>Просмотр логов генерации статей.</p>
                    <a href="?page=yandex-courier-auto-posts-logs" class="button button-secondary">
                        <span class="dashicons dashicons-list-view"></span>
                        Открыть журнал
                    </a>
                </div>
            </div>
        </div>

        <!-- Последние статьи -->
        <div class="ycap-recent-panel">
            <h2>Последние сгенерированные статьи</h2>

            <?php
            $recent_posts = get_posts(array(
                'post_type' => 'post',
                'meta_key' => '_ycap_generated',
                'meta_value' => '1',
                'numberposts' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if ($recent_posts):
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Заголовок</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_posts as $post): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html(get_the_date('d.m.Y H:i', $post->ID)); ?></td>
                        <td>
                            <?php
                            $status = get_post_status($post->ID);
                            $status_labels = array(
                                'publish' => '<span class="ycap-status publish">Опубликована</span>',
                                'draft' => '<span class="ycap-status draft">Черновик</span>',
                                'pending' => '<span class="ycap-status pending">На модерации</span>'
                            );
                            echo isset($status_labels[$status]) ? $status_labels[$status] : $status;
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small">
                                Редактировать
                            </a>
                            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="button button-small" target="_blank">
                                Просмотр
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Пока нет сгенерированных статей. Нажмите кнопку "Сгенерировать статью" для начала работы.</p>
            <?php endif; ?>
        </div>

        <!-- Информация -->
        <div class="ycap-info-panel">
            <h2>Информация о плагине</h2>
            <div class="ycap-info-grid">
                <div class="ycap-info-item">
                    <strong>Версия:</strong> <?php echo esc_html(YCAP_VERSION); ?>
                </div>
                <div class="ycap-info-item">
                    <strong>Статей в день:</strong> <?php echo esc_html(isset($settings['articles_per_day']) ? $settings['articles_per_day'] : 3); ?>
                </div>
                <div class="ycap-info-item">
                    <strong>Время публикации:</strong> <?php echo esc_html(isset($settings['publication_time']) ? $settings['publication_time'] : '10:00'); ?>
                </div>
                <div class="ycap-info-item">
                    <strong>Автопубликация:</strong> <?php echo (!empty($settings['auto_publish'])) ? 'Включена' : 'Выключена'; ?>
                </div>
            </div>
        </div>
    </div>
</div>
