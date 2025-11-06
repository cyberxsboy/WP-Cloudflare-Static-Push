<?php
/**
 * 主插件类
 * 
 * @author 泥人传说
 * @link https://nirenchuanshuo.com
 * @link https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CF_Static_Push {
    
    private $admin_interface;
    private $cloudflare_api;
    private $static_generator;
    
    public function __construct() {
        $this->admin_interface = new WP_CF_Admin_Interface();
        $this->cloudflare_api = new WP_CF_Cloudflare_API();
        $this->static_generator = new WP_CF_Static_Generator();
    }
    
    public function run() {
        // 加载文本域
        add_action('init', array($this, 'load_textdomain'));
        
        // 初始化管理界面
        add_action('admin_menu', array($this->admin_interface, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin_interface, 'enqueue_admin_assets'));
        
        // 注册AJAX处理器
        add_action('wp_ajax_wp_cf_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_wp_cf_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_wp_cf_push_content', array($this, 'ajax_push_content'));
        add_action('wp_ajax_wp_cf_get_logs', array($this, 'ajax_get_logs'));
        add_action('wp_ajax_wp_cf_save_ads', array($this, 'ajax_save_ads'));
        
        // 自动推送钩子
        $settings = get_option('wp_cf_static_push_settings');
        if (!empty($settings['auto_push_on_publish'])) {
            add_action('publish_post', array($this, 'auto_push_on_publish'), 10, 2);
            add_action('publish_page', array($this, 'auto_push_on_publish'), 10, 2);
        }
        
        if (!empty($settings['auto_push_on_update'])) {
            add_action('post_updated', array($this, 'auto_push_on_update'), 10, 3);
        }
        
        // 添加编辑器快捷推送按钮
        add_action('post_submitbox_misc_actions', array($this, 'add_push_button_to_editor'));
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('wp-cf-static-push', false, dirname(WP_CF_STATIC_PUSH_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * AJAX: 测试Cloudflare连接
     */
    public function ajax_test_connection() {
        check_ajax_referer('wp_cf_static_push_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
        }
        
        $api_token = sanitize_text_field($_POST['api_token']);
        $account_id = sanitize_text_field($_POST['account_id']);
        
        $result = $this->cloudflare_api->test_connection($api_token, $account_id);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => '连接成功！'));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * AJAX: 保存设置
     */
    public function ajax_save_settings() {
        check_ajax_referer('wp_cf_static_push_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
        }
        
        $current_settings = get_option('wp_cf_static_push_settings');
        
        $settings = array(
            'setup_completed' => true,
            'cloudflare_api_token' => sanitize_text_field($_POST['api_token']),
            'cloudflare_account_id' => sanitize_text_field($_POST['account_id']),
            'project_type' => sanitize_text_field($_POST['project_type']),
            'project_name' => sanitize_text_field($_POST['project_name']),
            'auto_push_on_publish' => !empty($_POST['auto_push_on_publish']),
            'auto_push_on_update' => !empty($_POST['auto_push_on_update']),
            'enable_ads' => !empty($_POST['enable_ads']),
            'ad_positions' => !empty($current_settings['ad_positions']) ? $current_settings['ad_positions'] : array(),
        );
        
        // 如果有广告位数据，也保存
        if (isset($_POST['ad_positions']) && is_array($_POST['ad_positions'])) {
            $settings['ad_positions'] = $this->sanitize_ad_positions($_POST['ad_positions']);
        }
        
        update_option('wp_cf_static_push_settings', $settings);
        
        wp_send_json_success(array('message' => '设置已保存！'));
    }
    
    /**
     * AJAX: 保存广告设置
     */
    public function ajax_save_ads() {
        check_ajax_referer('wp_cf_static_push_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
        }
        
        $settings = get_option('wp_cf_static_push_settings');
        $settings['enable_ads'] = !empty($_POST['enable_ads']);
        
        if (isset($_POST['ad_positions']) && is_array($_POST['ad_positions'])) {
            $settings['ad_positions'] = $this->sanitize_ad_positions($_POST['ad_positions']);
        }
        
        update_option('wp_cf_static_push_settings', $settings);
        
        wp_send_json_success(array('message' => '广告设置已保存！'));
    }
    
    /**
     * 清理广告位数据
     */
    private function sanitize_ad_positions($positions) {
        $sanitized = array();
        
        foreach ($positions as $key => $data) {
            $sanitized[$key] = array(
                'enabled' => !empty($data['enabled']),
                'type' => sanitize_text_field($data['type']),
                'code' => wp_kses_post($data['code']), // 允许HTML和脚本标签
            );
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX: 推送内容
     */
    public function ajax_push_content() {
        check_ajax_referer('wp_cf_static_push_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => '权限不足'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        $push_type = sanitize_text_field($_POST['push_type']); // 'single', 'all', 'homepage'
        
        $result = $this->push_content($post_ids, $push_type);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => '推送成功！',
                'details' => $result['details']
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * AJAX: 获取推送日志
     */
    public function ajax_get_logs() {
        check_ajax_referer('wp_cf_static_push_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '权限不足'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf_push_logs';
        
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50",
            ARRAY_A
        );
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    /**
     * 推送内容到Cloudflare
     */
    private function push_content($post_ids, $push_type) {
        $settings = get_option('wp_cf_static_push_settings');
        
        if (empty($settings['setup_completed'])) {
            return array('success' => false, 'message' => '请先完成设置向导');
        }
        
        // 生成静态内容
        $static_files = $this->static_generator->generate($post_ids, $push_type);
        
        if (empty($static_files)) {
            return array('success' => false, 'message' => '没有内容需要推送');
        }
        
        // 推送到Cloudflare
        $result = $this->cloudflare_api->push_files(
            $settings['cloudflare_api_token'],
            $settings['cloudflare_account_id'],
            $settings['project_type'],
            $settings['project_name'],
            $static_files
        );
        
        // 记录日志
        $this->log_push($post_ids, $push_type, $result);
        
        return $result;
    }
    
    /**
     * 自动推送（发布时）
     */
    public function auto_push_on_publish($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        $this->push_content(array($post_id), 'single');
    }
    
    /**
     * 自动推送（更新时）
     */
    public function auto_push_on_update($post_id, $post_after, $post_before) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        if ($post_after->post_status === 'publish') {
            $this->push_content(array($post_id), 'single');
        }
    }
    
    /**
     * 在编辑器中添加推送按钮
     */
    public function add_push_button_to_editor() {
        global $post;
        
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        ?>
        <div class="misc-pub-section misc-pub-cf-push">
            <span class="dashicons dashicons-cloud-upload"></span>
            <a href="#" id="cf-push-single" class="button" data-post-id="<?php echo $post->ID; ?>">
                推送到Cloudflare
            </a>
        </div>
        <?php
    }
    
    /**
     * 记录推送日志
     */
    private function log_push($post_ids, $push_type, $result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf_push_logs';
        
        foreach ($post_ids as $post_id) {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'push_type' => $push_type,
                    'status' => $result['success'] ? 'success' : 'failed',
                    'message' => $result['message'],
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
        }
    }
}

