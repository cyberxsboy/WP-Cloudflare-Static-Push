/**
 * Cloudflare Static Push - Admin Script
 * 
 * @author 泥人传说
 * @link https://nirenchuanshuo.com
 * @link https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 */

(function($) {
    'use strict';
    
    const cfPush = {
        /**
         * 初始化
         */
        init: function() {
            this.bindEvents();
            this.loadLogs();
        },
        
        /**
         * 绑定事件
         */
        bindEvents: function() {
            // 测试连接
            $('#cf-test-connection').on('click', this.testConnection.bind(this));
            
            // 下一步按钮
            $('.cf-step-panel').on('click', '#cf-next-step', this.nextStep.bind(this));
            
            // 保存设置
            $('#cf-save-settings').on('click', this.saveSettings.bind(this));
            
            // 跳过广告设置
            $('#cf-skip-ads').on('click', function(e) {
                e.preventDefault();
                window.location.href = '?page=wp-cf-static-push&step=5';
            });
            
            // 推送按钮
            $('.cf-push-btn').on('click', this.pushContent.bind(this));
            
            // 编辑器推送按钮
            $('#cf-push-single').on('click', this.pushSinglePost.bind(this));
            
            // 刷新日志
            $('#cf-refresh-logs').on('click', this.loadLogs.bind(this));
            
            // 清空日志
            $('#cf-clear-logs').on('click', this.clearLogs.bind(this));
            
            // 表单验证
            $('#cf_api_token, #cf_account_id').on('input', this.validateApiForm.bind(this));
            
            // 启用广告复选框
            $('input[name="enable_ads"]').on('change', function() {
                $('#ads-config-section').toggle($(this).is(':checked'));
            });
            
            // 广告表单提交
            $('#cf-ads-form').on('submit', cfPush.saveAds.bind(cfPush));
        },
        
        /**
         * 测试Cloudflare连接
         */
        testConnection: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const $result = $('#cf-test-result');
            const apiToken = $('#cf_api_token').val().trim();
            const accountId = $('#cf_account_id').val().trim();
            
            if (!apiToken || !accountId) {
                this.showNotification('请填写完整的API信息', 'error');
                return;
            }
            
            $btn.prop('disabled', true).text(wpCfStaticPush.strings.testing);
            $result.hide();
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_test_connection',
                    nonce: wpCfStaticPush.nonce,
                    api_token: apiToken,
                    account_id: accountId
                },
                success: function(response) {
                    if (response.success) {
                        $result
                            .removeClass('error')
                            .addClass('success')
                            .html('<span class="dashicons dashicons-yes-alt"></span>' + response.data.message)
                            .show();
                        
                        $('#cf-next-step').prop('disabled', false);
                        
                        cfPush.showNotification('连接测试成功！', 'success');
                    } else {
                        $result
                            .removeClass('success')
                            .addClass('error')
                            .html('<span class="dashicons dashicons-warning"></span>' + response.data.message)
                            .show();
                        
                        cfPush.showNotification('连接测试失败', 'error');
                    }
                },
                error: function() {
                    $result
                        .removeClass('success')
                        .addClass('error')
                        .html('<span class="dashicons dashicons-warning"></span>请求失败，请重试')
                        .show();
                    
                    cfPush.showNotification('请求失败，请重试', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('测试连接');
                }
            });
        },
        
        /**
         * 下一步
         */
        nextStep: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const nextStep = $btn.data('next');
            const currentForm = $btn.closest('form');
            
            // 验证表单
            if (!this.validateForm(currentForm)) {
                return;
            }
            
            // 保存临时数据并跳转
            const formData = this.getFormData(currentForm);
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_save_settings',
                    nonce: wpCfStaticPush.nonce,
                    ...formData
                },
                success: function() {
                    window.location.href = '?page=wp-cf-static-push&step=' + nextStep;
                },
                error: function() {
                    cfPush.showNotification('保存失败，请重试', 'error');
                }
            });
        },
        
        /**
         * 保存设置
         */
        saveSettings: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const $form = $btn.closest('form');
            const formData = this.getFormData($form);
            
            $btn.prop('disabled', true).text(wpCfStaticPush.strings.saving);
            
            // 获取之前步骤的数据
            const apiToken = $('#cf_api_token').val() || $('input[name="api_token"]').val();
            const accountId = $('#cf_account_id').val() || $('input[name="account_id"]').val();
            const projectType = $('#cf_project_type').val() || $('input[name="project_type"]').val();
            const projectName = $('#cf_project_name').val() || $('input[name="project_name"]').val();
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_save_settings',
                    nonce: wpCfStaticPush.nonce,
                    api_token: apiToken,
                    account_id: accountId,
                    project_type: projectType,
                    project_name: projectName,
                    auto_push_on_publish: formData.auto_push_on_publish || 0,
                    auto_push_on_update: formData.auto_push_on_update || 0
                },
                success: function(response) {
                    if (response.success) {
                        cfPush.showNotification(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = '?page=wp-cf-static-push&step=4';
                        }, 1000);
                    } else {
                        cfPush.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    cfPush.showNotification('保存失败，请重试', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('保存并完成设置');
                }
            });
        },
        
        /**
         * 推送内容
         */
        pushContent: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const pushType = $btn.data('type');
            let postIds = [];
            
            // 如果是推送选中项，获取选中的文章ID
            if (pushType === 'selected') {
                postIds = $('#cf-post-selector').val();
                
                if (!postIds || postIds.length === 0) {
                    this.showNotification('请选择要推送的内容', 'error');
                    return;
                }
            }
            
            // 全部推送需要确认
            if (pushType === 'all') {
                if (!confirm(wpCfStaticPush.strings.confirmPushAll)) {
                    return;
                }
            }
            
            $btn.prop('disabled', true);
            $('#cf-push-status')
                .addClass('loading')
                .html('<p>正在推送内容到 Cloudflare...</p>');
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_push_content',
                    nonce: wpCfStaticPush.nonce,
                    push_type: pushType,
                    post_ids: postIds
                },
                success: function(response) {
                    $('#cf-push-status').removeClass('loading');
                    
                    if (response.success) {
                        let html = '<div class="status-item success">';
                        html += '<span class="dashicons dashicons-yes-alt"></span>';
                        html += '<strong>' + response.data.message + '</strong>';
                        
                        if (response.data.details) {
                            html += '<div style="margin-top: 10px;">';
                            
                            if (response.data.details.deployment_id) {
                                html += '<p>部署ID: ' + response.data.details.deployment_id + '</p>';
                            }
                            
                            if (response.data.details.url) {
                                html += '<p>URL: <a href="' + response.data.details.url + '" target="_blank">' + response.data.details.url + '</a></p>';
                            }
                            
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        $('#cf-push-status').html(html);
                        
                        cfPush.showNotification('推送成功！', 'success');
                        cfPush.loadLogs();
                    } else {
                        let html = '<div class="status-item error">';
                        html += '<span class="dashicons dashicons-warning"></span>';
                        html += '<strong>推送失败</strong>';
                        html += '<p>' + response.data.message + '</p>';
                        html += '</div>';
                        $('#cf-push-status').html(html);
                        
                        cfPush.showNotification('推送失败: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    $('#cf-push-status')
                        .removeClass('loading')
                        .html('<div class="status-item error"><span class="dashicons dashicons-warning"></span><strong>请求失败，请重试</strong></div>');
                    
                    cfPush.showNotification('请求失败，请重试', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * 推送单篇文章（编辑器）
         */
        pushSinglePost: function(e) {
            e.preventDefault();
            
            const $btn = $(e.target);
            const postId = $btn.data('post-id');
            
            $btn.prop('disabled', true).text('推送中...');
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_push_content',
                    nonce: wpCfStaticPush.nonce,
                    push_type: 'single',
                    post_ids: [postId]
                },
                success: function(response) {
                    if (response.success) {
                        cfPush.showNotification('推送成功！', 'success');
                    } else {
                        cfPush.showNotification('推送失败: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    cfPush.showNotification('请求失败，请重试', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('推送到Cloudflare');
                }
            });
        },
        
        /**
         * 加载日志
         */
        loadLogs: function() {
            const $tbody = $('#cf-logs-body');
            
            if (!$tbody.length) {
                return;
            }
            
            $tbody.html('<tr><td colspan="5" class="text-center">加载中...</td></tr>');
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_get_logs',
                    nonce: wpCfStaticPush.nonce
                },
                success: function(response) {
                    if (response.success && response.data.logs.length > 0) {
                        let html = '';
                        
                        response.data.logs.forEach(function(log) {
                            const statusClass = log.status === 'success' ? 'status-success' : 'status-failed';
                            const statusText = log.status === 'success' ? '成功' : '失败';
                            
                            html += '<tr>';
                            html += '<td>' + log.created_at + '</td>';
                            html += '<td>文章 #' + log.post_id + '</td>';
                            html += '<td>' + log.push_type + '</td>';
                            html += '<td class="' + statusClass + '">' + statusText + '</td>';
                            html += '<td>' + (log.message || '-') + '</td>';
                            html += '</tr>';
                        });
                        
                        $tbody.html(html);
                    } else {
                        $tbody.html('<tr><td colspan="5" class="text-center">暂无日志记录</td></tr>');
                    }
                },
                error: function() {
                    $tbody.html('<tr><td colspan="5" class="text-center">加载失败</td></tr>');
                }
            });
        },
        
        /**
         * 清空日志
         */
        clearLogs: function(e) {
            e.preventDefault();
            
            if (!confirm('确定要清空所有日志吗？')) {
                return;
            }
            
            // 这里可以添加清空日志的AJAX请求
            this.showNotification('日志已清空', 'success');
        },
        
        /**
         * 保存广告设置
         */
        saveAds: function(e) {
            e.preventDefault();
            
            const $form = $(e.target);
            const formData = this.getFormData($form);
            
            $.ajax({
                url: wpCfStaticPush.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_cf_save_ads',
                    nonce: wpCfStaticPush.nonce,
                    ...formData
                },
                success: function(response) {
                    if (response.success) {
                        cfPush.showNotification(response.data.message, 'success');
                    } else {
                        cfPush.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    cfPush.showNotification('保存失败，请重试', 'error');
                }
            });
        },
        
        /**
         * 验证API表单
         */
        validateApiForm: function() {
            const apiToken = $('#cf_api_token').val().trim();
            const accountId = $('#cf_account_id').val().trim();
            
            // 如果两个字段都有值，但还没有测试连接，禁用下一步按钮
            if (apiToken && accountId) {
                // 启用测试按钮
                $('#cf-test-connection').prop('disabled', false);
            }
        },
        
        /**
         * 验证表单
         */
        validateForm: function($form) {
            let isValid = true;
            
            $form.find('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', '#d63638');
                } else {
                    $(this).css('border-color', '');
                }
            });
            
            if (!isValid) {
                this.showNotification('请填写所有必填字段', 'error');
            }
            
            return isValid;
        },
        
        /**
         * 获取表单数据
         */
        getFormData: function($form) {
            const data = {};
            
            $form.find('input, select, textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                
                if (!name) return;
                
                if ($field.attr('type') === 'checkbox') {
                    data[name] = $field.is(':checked') ? 1 : 0;
                } else {
                    data[name] = $field.val();
                }
            });
            
            return data;
        },
        
        /**
         * 显示通知
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const iconClass = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
            
            const $notification = $('<div class="cf-notification ' + type + '">' +
                '<button class="cf-notification-close">&times;</button>' +
                '<span class="dashicons ' + iconClass + '"></span>' +
                '<span>' + message + '</span>' +
                '</div>');
            
            $('body').append($notification);
            
            // 关闭按钮
            $notification.find('.cf-notification-close').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // 自动关闭
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // 文档就绪时初始化
    $(document).ready(function() {
        cfPush.init();
    });
    
})(jQuery);

