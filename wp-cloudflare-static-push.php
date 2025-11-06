<?php
/**
 * Plugin Name: WP Cloudflare Static Push
 * Plugin URI: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 * Description: 自动推送WordPress前端静态内容到Cloudflare Workers和Pages项目，支持引导式配置和一键推送
 * Version: 1.0.0
 * Author: 泥人传说
 * Author URI: https://nirenchuanshuo.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-cf-static-push
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Tested up to: 6.4
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 检查 PHP 版本
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        echo '<strong>WP Cloudflare Static Push:</strong> ';
        echo '需要 PHP 7.4 或更高版本。您当前的 PHP 版本是 ' . PHP_VERSION;
        echo '</p></div>';
    });
    return;
}

// 检查 WordPress 版本
global $wp_version;
if (version_compare($wp_version, '6.0', '<')) {
    add_action('admin_notices', function() {
        global $wp_version;
        echo '<div class="error"><p>';
        echo '<strong>WP Cloudflare Static Push:</strong> ';
        echo '需要 WordPress 6.0 或更高版本。您当前的版本是 ' . $wp_version;
        echo '</p></div>';
    });
    return;
}

// 定义插件常量
define('WP_CF_STATIC_PUSH_VERSION', '1.0.0');
define('WP_CF_STATIC_PUSH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_CF_STATIC_PUSH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_CF_STATIC_PUSH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// 加载核心类
require_once WP_CF_STATIC_PUSH_PLUGIN_DIR . 'includes/class-wp-cf-static-push.php';
require_once WP_CF_STATIC_PUSH_PLUGIN_DIR . 'includes/class-cloudflare-api.php';
require_once WP_CF_STATIC_PUSH_PLUGIN_DIR . 'includes/class-static-generator.php';
require_once WP_CF_STATIC_PUSH_PLUGIN_DIR . 'includes/class-admin-interface.php';

// 初始化插件
function wp_cf_static_push_init() {
    $plugin = new WP_CF_Static_Push();
    $plugin->run();
}
add_action('plugins_loaded', 'wp_cf_static_push_init');

// 激活钩子
register_activation_hook(__FILE__, 'wp_cf_static_push_activate');
function wp_cf_static_push_activate() {
    // 创建必要的数据库表和默认选项
    add_option('wp_cf_static_push_settings', array(
        'setup_completed' => false,
        'cloudflare_api_token' => '',
        'cloudflare_account_id' => '',
        'project_type' => '', // 'workers' 或 'pages'
        'project_name' => '',
        'auto_push_on_publish' => true,
        'auto_push_on_update' => true,
        'enable_ads' => false,
        'ad_positions' => array(), // 广告位配置
    ));
    
    // 创建推送日志表
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf_push_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        push_type varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        message text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 停用钩子
register_deactivation_hook(__FILE__, 'wp_cf_static_push_deactivate');
function wp_cf_static_push_deactivate() {
    // 清理计划任务
    wp_clear_scheduled_hook('wp_cf_static_push_cron');
}

// 卸载钩子
register_uninstall_hook(__FILE__, 'wp_cf_static_push_uninstall');
function wp_cf_static_push_uninstall() {
    // 删除选项
    delete_option('wp_cf_static_push_settings');
    
    // 删除数据库表
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf_push_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

