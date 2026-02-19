<?php
/**
 * Страница журнала логов
 */

if (!defined('ABSPATH')) {
    exit;
}

// Получаем логи
global $wpdb;
$table_name = $wpdb->prefix . 'ycap_logs';

// Проверяем существование таблицы
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

$logs = array();
if ($table_exists) {
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
}

$stats = YCAP_Logger::get_stats();
?>

<div class="wrap ycap-admin-wrap">
    <h1>
        <span class="dashicons dashicons-list-view"></span>
        Журнал событий
    </h1>

    <div class="ycap-logs-stats">
        <div class="ycap-stat-badge info">
            <span class="dashicons dashicons-info"></span>
            Информация: <?php echo esc_html($stats['info']); ?>
        </div>
        <div class="ycap-stat-badge warning">
            <span class="dashicons dashicons-warning"></span>
            Предупреждения: <?php echo esc_html($stats['warning']); ?>
        </div>
        <div class="ycap-stat-badge error">
            <span class="dashicons dashicons-dismiss"></span>
            Ошибки: <?php echo esc_html($stats['error']); ?>
        </div>
    </div>

    <?php if (!empty($logs)): ?>
    <table class="wp-list-table widefat fixed striped ycap-logs-table">
        <thead>
            <tr>
                <th class="ycap-col-id">ID</th>
                <th class="ycap-col-level">Уровень</th>
                <th class="ycap-col-message">Сообщение</th>
                <th class="ycap-col-date">Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr class="ycap-log-level-<?php echo esc_attr($log->level); ?>">
                <td><?php echo esc_html($log->id); ?></td>
                <td>
                    <span class="ycap-log-badge ycap-log-<?php echo esc_attr($log->level); ?>">
                        <?php echo esc_html($log->level); ?>
                    </span>
                </td>
                <td>
                    <?php echo esc_html($log->message); ?>
                    <?php if (!empty($log->context)): ?>
                    <button type="button" class="button button-small ycap-toggle-context">
                        Контекст
                    </button>
                    <pre class="ycap-context" style="display: none;"><?php echo esc_html($log->context); ?></pre>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($log->created_at); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form method="post" action="" class="ycap-logs-actions">
        <?php wp_nonce_field('ycap_clear_logs', 'ycap_nonce'); ?>
        <button type="submit"
                name="ycap_clear_logs"
                class="button button-secondary"
                onclick="return confirm('Очистить старые логи (старше 30 дней)?');">
            Очистить старые логи
        </button>
    </form>

    <?php else: ?>
    <div class="ycap-no-logs">
        <span class="dashicons dashicons-clipboard"></span>
        <p>Журнал пуст. Логи появятся после первой генерации статей.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// Обработка очистки логов
if (isset($_POST['ycap_clear_logs']) && wp_verify_nonce($_POST['ycap_nonce'], 'ycap_clear_logs')) {
    $deleted = YCAP_Logger::cleanup(30);
    echo '<div class="notice notice-success"><p>Удалено ' . esc_html($deleted) . ' записей.</p></div>';
}
?>
