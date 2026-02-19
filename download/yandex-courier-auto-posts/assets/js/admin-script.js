/**
 * JavaScript для админки Яндекс Курьер АвтоПостинг
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Генерация статьи
        $('.ycap-generate-btn').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $progress = $btn.siblings('.ycap-progress');
            var $progressText = $progress.find('.ycap-progress-text');

            // Блокируем кнопку
            $btn.prop('disabled', true);
            $progress.show();
            $progressText.text(ycapAjax.messages.generating);

            $.ajax({
                url: ycapAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ycap_generate_article',
                    nonce: ycapAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $progressText.text(ycapAjax.messages.success);
                        $progress.find('.ycap-progress-bar').css('background', '#00a32a');

                        // Показываем ссылку на редактирование
                        if (response.data.edit_link) {
                            setTimeout(function() {
                                window.location.href = response.data.edit_link;
                            }, 1000);
                        } else {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showError($progress, $progressText, response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    showError($progress, $progressText, error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Проверка API
        $('.ycap-test-api-btn').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var $status = $btn.siblings('.ycap-api-status');

            $btn.prop('disabled', true);
            $status.removeClass('success error').text(ycapAjax.messages.testing);

            $.ajax({
                url: ycapAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ycap_test_api',
                    nonce: ycapAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.addClass('success').text(ycapAjax.messages.test_success);
                    } else {
                        $status.addClass('error').text(ycapAjax.messages.test_error + ': ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $status.addClass('error').text(ycapAjax.messages.test_error + ': ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Тоггл контекста в логах
        $('.ycap-toggle-context').on('click', function() {
            var $context = $(this).next('.ycap-context');
            $context.toggle();
            $(this).text($context.is(':visible') ? 'Скрыть' : 'Контекст');
        });

        // Обновление статистики
        if ($('.ycap-stats-grid').length) {
            updateStats();
            setInterval(updateStats, 60000); // Каждую минуту
        }

        function updateStats() {
            $.ajax({
                url: ycapAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ycap_get_stats',
                    nonce: ycapAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('.ycap-stat-number').eq(0).text(data.total_articles);
                        $('.ycap-stat-number').eq(1).text(data.articles_today);
                    }
                }
            });
        }

        function showError($progress, $progressText, message) {
            $progressText.text(ycapAjax.messages.error + ': ' + message);
            $progress.find('.ycap-progress-bar').css('background', '#d63638');
        }
    });

})(jQuery);
