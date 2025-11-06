<?php
/**
 * 管理界面类
 * 
 * @author 泥人传说
 * @link https://nirenchuanshuo.com
 * @link https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CF_Admin_Interface {
    
    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_menu_page(
            'Cloudflare 静态推送',
            'CF 静态推送',
            'manage_options',
            'wp-cf-static-push',
            array($this, 'render_main_page'),
            'dashicons-cloud-upload',
            30
        );
        
        add_submenu_page(
            'wp-cf-static-push',
            '设置向导',
            '设置向导',
            'manage_options',
            'wp-cf-static-push',
            array($this, 'render_main_page')
        );
        
        add_submenu_page(
            'wp-cf-static-push',
            '推送管理',
            '推送管理',
            'manage_options',
            'wp-cf-push-manager',
            array($this, 'render_push_manager')
        );
        
        add_submenu_page(
            'wp-cf-static-push',
            '推送日志',
            '推送日志',
            'manage_options',
            'wp-cf-push-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'wp-cf-static-push',
            '广告位管理',
            '广告位管理',
            'manage_options',
            'wp-cf-ads-manager',
            array($this, 'render_ads_manager')
        );
    }
    
    /**
     * 加载管理页面资源
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-cf') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wp-cf-admin-style',
            WP_CF_STATIC_PUSH_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WP_CF_STATIC_PUSH_VERSION
        );
        
        wp_enqueue_script(
            'wp-cf-admin-script',
            WP_CF_STATIC_PUSH_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            WP_CF_STATIC_PUSH_VERSION,
            true
        );
        
        wp_localize_script('wp-cf-admin-script', 'wpCfStaticPush', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_cf_static_push_nonce'),
            'strings' => array(
                'testing' => '测试连接中...',
                'saving' => '保存中...',
                'pushing' => '推送中...',
                'success' => '成功！',
                'error' => '错误',
                'confirmPushAll' => '确定要推送所有内容吗？这可能需要一些时间。'
            )
        ));
    }
    
    /**
     * 渲染主页面（设置向导）
     */
    public function render_main_page() {
        $settings = get_option('wp_cf_static_push_settings');
        $current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
        
        ?>
        <div class="wrap wp-cf-static-push-wrapper">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (!$settings['setup_completed']) : ?>
                <div class="notice notice-info">
                    <p>欢迎使用 Cloudflare 静态推送插件！请按照以下步骤完成设置。</p>
                </div>
            <?php endif; ?>
            
            <div class="cf-setup-wizard">
                <!-- 步骤指示器 -->
                <div class="cf-steps-indicator">
                    <div class="cf-step <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                        <span class="step-number">1</span>
                        <span class="step-title">API 配置</span>
                    </div>
                    <div class="cf-step <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                        <span class="step-number">2</span>
                        <span class="step-title">项目选择</span>
                    </div>
                    <div class="cf-step <?php echo $current_step >= 3 ? 'active' : ''; ?> <?php echo $current_step > 3 ? 'completed' : ''; ?>">
                        <span class="step-number">3</span>
                        <span class="step-title">推送设置</span>
                    </div>
                    <div class="cf-step <?php echo $current_step >= 4 ? 'active' : ''; ?> <?php echo $current_step > 4 ? 'completed' : ''; ?>">
                        <span class="step-number">4</span>
                        <span class="step-title">广告设置</span>
                    </div>
                    <div class="cf-step <?php echo $current_step >= 5 ? 'active' : ''; ?>">
                        <span class="step-number">5</span>
                        <span class="step-title">完成</span>
                    </div>
                </div>
                
                <!-- 步骤内容 -->
                <div class="cf-step-content">
                    <?php
                    switch ($current_step) {
                        case 1:
                            $this->render_step_api_config($settings);
                            break;
                        case 2:
                            $this->render_step_project_selection($settings);
                            break;
                        case 3:
                            $this->render_step_push_settings($settings);
                            break;
                        case 4:
                            $this->render_step_ads_settings($settings);
                            break;
                        case 5:
                            $this->render_step_complete($settings);
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 步骤1: API配置
     */
    private function render_step_api_config($settings) {
        ?>
        <div class="cf-step-panel">
            <h2>配置 Cloudflare API</h2>
            <p>请输入您的 Cloudflare API Token 和 Account ID。</p>
            
            <div class="cf-help-box">
                <h3>如何获取 API Token？</h3>
                <ol>
                    <li>登录到 <a href="https://dash.cloudflare.com/" target="_blank">Cloudflare 控制台</a></li>
                    <li>进入 "My Profile" → "API Tokens"</li>
                    <li>点击 "Create Token"</li>
                    <li>选择 "Edit Cloudflare Workers" 模板或创建自定义 Token</li>
                    <li>确保包含 Account.Cloudflare Pages 和 Workers Scripts 权限</li>
                </ol>
                
                <h3>如何获取 Account ID？</h3>
                <ol>
                    <li>在 Cloudflare 控制台中选择任意域名</li>
                    <li>在右侧栏找到 "Account ID"</li>
                    <li>点击复制图标</li>
                </ol>
            </div>
            
            <form id="cf-api-config-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cf_api_token">API Token</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="cf_api_token" 
                                   name="api_token" 
                                   value="<?php echo esc_attr($settings['cloudflare_api_token']); ?>" 
                                   class="regular-text"
                                   placeholder="your-cloudflare-api-token">
                            <p class="description">您的 Cloudflare API Token</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cf_account_id">Account ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="cf_account_id" 
                                   name="account_id" 
                                   value="<?php echo esc_attr($settings['cloudflare_account_id']); ?>" 
                                   class="regular-text"
                                   placeholder="your-account-id">
                            <p class="description">您的 Cloudflare Account ID</p>
                        </td>
                    </tr>
                </table>
                
                <div class="cf-form-actions">
                    <button type="button" class="button button-secondary" id="cf-test-connection">
                        测试连接
                    </button>
                    <button type="button" class="button button-primary" id="cf-next-step" data-next="2" disabled>
                        下一步
                    </button>
                </div>
                
                <div id="cf-test-result" class="cf-test-result" style="display:none;"></div>
            </form>
        </div>
        <?php
    }
    
    /**
     * 步骤2: 项目选择
     */
    private function render_step_project_selection($settings) {
        ?>
        <div class="cf-step-panel">
            <h2>选择项目类型和名称</h2>
            <p>选择您要推送到的 Cloudflare 项目类型。</p>
            
            <form id="cf-project-config-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cf_project_type">项目类型</label>
                        </th>
                        <td>
                            <select id="cf_project_type" name="project_type" class="regular-text">
                                <option value="">-- 请选择 --</option>
                                <option value="pages" <?php selected($settings['project_type'], 'pages'); ?>>
                                    Cloudflare Pages
                                </option>
                                <option value="workers" <?php selected($settings['project_type'], 'workers'); ?>>
                                    Cloudflare Workers
                                </option>
                            </select>
                            <p class="description">
                                <strong>Pages:</strong> 适合静态网站，支持自动部署和预览<br>
                                <strong>Workers:</strong> 适合需要动态处理的边缘计算场景
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cf_project_name">项目名称</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="cf_project_name" 
                                   name="project_name" 
                                   value="<?php echo esc_attr($settings['project_name']); ?>" 
                                   class="regular-text"
                                   placeholder="my-wordpress-site">
                            <p class="description">
                                您的项目名称（如果项目不存在，将自动创建）<br>
                                只能包含小写字母、数字和连字符
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="cf-form-actions">
                    <a href="?page=wp-cf-static-push&step=1" class="button button-secondary">
                        上一步
                    </a>
                    <button type="button" class="button button-primary" id="cf-next-step" data-next="3">
                        下一步
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * 步骤3: 推送设置
     */
    private function render_step_push_settings($settings) {
        ?>
        <div class="cf-step-panel">
            <h2>配置自动推送</h2>
            <p>选择何时自动推送内容到 Cloudflare。</p>
            
            <form id="cf-push-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">自动推送触发</th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" 
                                           name="auto_push_on_publish" 
                                           value="1" 
                                           <?php checked($settings['auto_push_on_publish'], true); ?>>
                                    发布新文章/页面时自动推送
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" 
                                           name="auto_push_on_update" 
                                           value="1" 
                                           <?php checked($settings['auto_push_on_update'], true); ?>>
                                    更新文章/页面时自动推送
                                </label>
                            </fieldset>
                            <p class="description">
                                启用自动推送后，内容变更会立即同步到 Cloudflare
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="cf-form-actions">
                    <a href="?page=wp-cf-static-push&step=2" class="button button-secondary">
                        上一步
                    </a>
                    <button type="button" class="button button-primary" id="cf-next-step" data-next="4">
                        下一步
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * 步骤4: 广告设置
     */
    private function render_step_ads_settings($settings) {
        ?>
        <div class="cf-step-panel">
            <h2>配置广告位（可选）</h2>
            <p>在推送的静态页面中自动插入广告代码，支持 Google AdSense 和自定义 HTML 广告。</p>
            
            <form id="cf-ads-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">启用广告</th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="enable_ads" 
                                       value="1" 
                                       <?php checked(!empty($settings['enable_ads']), true); ?>>
                                启用自动广告插入
                            </label>
                            <p class="description">勾选后将在推送的静态页面中自动插入广告</p>
                        </td>
                    </tr>
                </table>
                
                <div id="ads-config-section" style="<?php echo empty($settings['enable_ads']) ? 'display:none;' : ''; ?>">
                    <h3>广告位配置</h3>
                    
                    <div class="cf-ad-position">
                        <h4>文章顶部广告</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row">启用</th>
                                <td>
                                    <input type="checkbox" name="ad_top_enabled" value="1">
                                    在文章标题下方显示广告
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告类型</th>
                                <td>
                                    <select name="ad_top_type" class="regular-text">
                                        <option value="html">HTML 代码</option>
                                        <option value="adsense">Google AdSense</option>
                                        <option value="script">JavaScript 代码</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告代码</th>
                                <td>
                                    <textarea name="ad_top_code" rows="5" class="large-text code" placeholder="粘贴您的广告代码..."></textarea>
                                    <p class="description">支持 HTML 或 JavaScript 代码</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="cf-ad-position">
                        <h4>文章底部广告</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row">启用</th>
                                <td>
                                    <input type="checkbox" name="ad_bottom_enabled" value="1">
                                    在文章内容结束后显示广告
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告类型</th>
                                <td>
                                    <select name="ad_bottom_type" class="regular-text">
                                        <option value="html">HTML 代码</option>
                                        <option value="adsense">Google AdSense</option>
                                        <option value="script">JavaScript 代码</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告代码</th>
                                <td>
                                    <textarea name="ad_bottom_code" rows="5" class="large-text code" placeholder="粘贴您的广告代码..."></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="cf-ad-position">
                        <h4>侧边栏广告</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row">启用</th>
                                <td>
                                    <input type="checkbox" name="ad_sidebar_enabled" value="1">
                                    在侧边栏显示广告
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告类型</th>
                                <td>
                                    <select name="ad_sidebar_type" class="regular-text">
                                        <option value="html">HTML 代码</option>
                                        <option value="adsense">Google AdSense</option>
                                        <option value="script">JavaScript 代码</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告代码</th>
                                <td>
                                    <textarea name="ad_sidebar_code" rows="5" class="large-text code" placeholder="粘贴您的广告代码..."></textarea>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="cf-help-box">
                        <h3>Google AdSense 示例</h3>
                        <pre><code>&lt;script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXX"
     crossorigin="anonymous"&gt;&lt;/script&gt;
&lt;ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXX"
     data-ad-slot="XXXXX"
     data-ad-format="auto"&gt;&lt;/ins&gt;
&lt;script&gt;
     (adsbygoogle = window.adsbygoogle || []).push({});
&lt;/script&gt;</code></pre>
                    </div>
                </div>
                
                <div class="cf-form-actions">
                    <a href="?page=wp-cf-static-push&step=3" class="button button-secondary">
                        上一步
                    </a>
                    <button type="button" class="button button-secondary" id="cf-skip-ads">
                        跳过广告设置
                    </button>
                    <button type="button" class="button button-primary" id="cf-save-settings">
                        保存并完成设置
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * 步骤5: 完成
     */
    private function render_step_complete($settings) {
        ?>
        <div class="cf-step-panel">
            <div class="cf-success-box">
                <span class="dashicons dashicons-yes-alt"></span>
                <h2>设置完成！</h2>
                <p>您已成功配置 Cloudflare 静态推送插件。</p>
            </div>
            
            <div class="cf-next-actions">
                <h3>接下来您可以：</h3>
                <ul>
                    <li>
                        <a href="?page=wp-cf-push-manager" class="button button-primary button-large">
                            前往推送管理页面
                        </a>
                        <p>手动推送内容或查看推送状态</p>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="button button-secondary button-large">
                            创建新文章
                        </a>
                        <p>创建并发布文章，内容将自动推送到 Cloudflare</p>
                    </li>
                    <li>
                        <a href="?page=wp-cf-push-logs" class="button button-secondary button-large">
                            查看推送日志
                        </a>
                        <p>查看所有推送记录和状态</p>
                    </li>
                </ul>
            </div>
            
            <div class="cf-form-actions">
                <a href="?page=wp-cf-static-push&step=1" class="button button-secondary">
                    重新配置
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染推送管理页面
     */
    public function render_push_manager() {
        ?>
        <div class="wrap wp-cf-static-push-wrapper">
            <h1>推送管理</h1>
            
            <div class="cf-push-manager">
                <div class="cf-push-actions-box">
                    <h2>快速推送</h2>
                    
                    <div class="cf-push-action">
                        <h3>推送首页</h3>
                        <p>推送网站首页到 Cloudflare</p>
                        <button type="button" class="button button-primary cf-push-btn" data-type="homepage">
                            推送首页
                        </button>
                    </div>
                    
                    <div class="cf-push-action">
                        <h3>推送全部内容</h3>
                        <p>推送所有已发布的文章、页面和归档页面</p>
                        <button type="button" class="button button-primary cf-push-btn" data-type="all">
                            推送全部
                        </button>
                    </div>
                    
                    <div class="cf-push-action">
                        <h3>推送特定内容</h3>
                        <p>选择特定的文章或页面进行推送</p>
                        <select id="cf-post-selector" multiple size="10" style="width: 100%; margin-bottom: 10px;">
                            <?php
                            $args = array(
                                'post_type' => array('post', 'page'),
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'orderby' => 'date',
                                'order' => 'DESC'
                            );
                            $query = new WP_Query($args);
                            
                            if ($query->have_posts()) {
                                while ($query->have_posts()) {
                                    $query->the_post();
                                    printf(
                                        '<option value="%d">[%s] %s</option>',
                                        get_the_ID(),
                                        get_post_type(),
                                        esc_html(get_the_title())
                                    );
                                }
                                wp_reset_postdata();
                            }
                            ?>
                        </select>
                        <button type="button" class="button button-primary cf-push-btn" data-type="selected">
                            推送选中项
                        </button>
                    </div>
                </div>
                
                <div class="cf-push-status-box">
                    <h2>推送状态</h2>
                    <div id="cf-push-status">
                        <p class="description">点击上方按钮开始推送</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染日志页面
     */
    public function render_logs_page() {
        ?>
        <div class="wrap wp-cf-static-push-wrapper">
            <h1>推送日志</h1>
            
            <div class="cf-logs-container">
                <button type="button" class="button" id="cf-refresh-logs">刷新日志</button>
                <button type="button" class="button" id="cf-clear-logs">清空日志</button>
                
                <table class="wp-list-table widefat fixed striped" id="cf-logs-table">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>文章/页面</th>
                            <th>推送类型</th>
                            <th>状态</th>
                            <th>消息</th>
                        </tr>
                    </thead>
                    <tbody id="cf-logs-body">
                        <tr>
                            <td colspan="5" class="text-center">加载中...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染广告位管理页面
     */
    public function render_ads_manager() {
        $settings = get_option('wp_cf_static_push_settings');
        $ad_positions = !empty($settings['ad_positions']) ? $settings['ad_positions'] : array();
        ?>
        <div class="wrap wp-cf-static-push-wrapper">
            <h1>广告位管理</h1>
            
            <div class="cf-ads-manager">
                <div class="notice notice-info">
                    <p><strong>提示：</strong>配置广告位后，重新推送内容即可在静态页面中看到广告。</p>
                </div>
                
                <form method="post" id="cf-ads-form">
                    <?php wp_nonce_field('cf_save_ads', 'cf_ads_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">启用广告</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_ads" value="1" 
                                           <?php checked(!empty($settings['enable_ads']), true); ?>>
                                    在推送的静态页面中自动插入广告
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <h2>广告位配置</h2>
                    
                    <?php
                    $positions = array(
                        'top' => array('label' => '文章顶部', 'desc' => '在文章标题下方显示'),
                        'bottom' => array('label' => '文章底部', 'desc' => '在文章内容结束后显示'),
                        'sidebar' => array('label' => '侧边栏', 'desc' => '在侧边栏显示'),
                        'before_content' => array('label' => '内容前', 'desc' => '在第一段前显示'),
                        'after_first_paragraph' => array('label' => '首段后', 'desc' => '在第一段后显示'),
                    );
                    
                    foreach ($positions as $key => $position) :
                        $enabled = !empty($ad_positions[$key]['enabled']);
                        $type = !empty($ad_positions[$key]['type']) ? $ad_positions[$key]['type'] : 'html';
                        $code = !empty($ad_positions[$key]['code']) ? $ad_positions[$key]['code'] : '';
                    ?>
                    <div class="cf-ad-position">
                        <h3><?php echo esc_html($position['label']); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">启用</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ad_positions[<?php echo $key; ?>][enabled]" value="1" <?php checked($enabled, true); ?>>
                                        <?php echo esc_html($position['desc']); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告类型</th>
                                <td>
                                    <select name="ad_positions[<?php echo $key; ?>][type]">
                                        <option value="html" <?php selected($type, 'html'); ?>>HTML 代码</option>
                                        <option value="adsense" <?php selected($type, 'adsense'); ?>>Google AdSense</option>
                                        <option value="script" <?php selected($type, 'script'); ?>>JavaScript 代码</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">广告代码</th>
                                <td>
                                    <textarea name="ad_positions[<?php echo $key; ?>][code]" rows="6" class="large-text code"><?php echo esc_textarea($code); ?></textarea>
                                    <p class="description">粘贴您的广告代码（支持 HTML 和 JavaScript）</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="cf-help-box">
                        <h3>Google AdSense 代码示例</h3>
                        <pre><code>&lt;script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX"
     crossorigin="anonymous"&gt;&lt;/script&gt;
&lt;ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
     data-ad-slot="XXXXXXXXXX"
     data-ad-format="auto"
     data-full-width-responsive="true"&gt;&lt;/ins&gt;
&lt;script&gt;
     (adsbygoogle = window.adsbygoogle || []).push({});
&lt;/script&gt;</code></pre>
                        
                        <h3>自定义 HTML 广告示例</h3>
                        <pre><code>&lt;div class="custom-ad"&gt;
    &lt;a href="https://example.com" target="_blank"&gt;
        &lt;img src="banner.jpg" alt="广告"&gt;
    &lt;/a&gt;
&lt;/div&gt;</code></pre>
                    </div>
                    
                    <?php submit_button('保存广告配置'); ?>
                </form>
            </div>
        </div>
        <?php
    }
}


