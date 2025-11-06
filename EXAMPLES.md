# 使用示例

**作者**: 泥人传说  
**项目地址**: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push

本文档提供插件的实际使用示例和代码片段。

## 基本使用场景

### 场景 1：个人博客自动部署

**需求**：每次发布文章自动推送到 Cloudflare Pages

**配置**：
1. 项目类型：Cloudflare Pages
2. 项目名称：my-blog
3. 启用：发布时自动推送
4. 启用：更新时自动推送

**效果**：每次发布或更新文章，静态版本自动推送到 `my-blog.pages.dev`

### 场景 2：公司官网手动控制

**需求**：编辑多篇内容后统一推送

**配置**：
1. 项目类型：Cloudflare Pages
2. 关闭：自动推送选项

**使用**：
- 编辑多篇内容
- 前往"推送管理"
- 点击"推送全部"一次性更新

### 场景 3：边缘计算应用

**需求**：使用 Workers 实现动态功能

**配置**：
1. 项目类型：Cloudflare Workers
2. 项目名称：my-edge-app

**说明**：插件生成包含静态内容的 Worker 脚本，可以在 Cloudflare Dashboard 中进一步编辑添加动态逻辑

## 代码扩展示例

### 示例 1：推送前处理

在主题的 `functions.php` 中添加：

```php
<?php
// 推送前添加自定义日志
add_action('wp_cf_before_push', function($post_ids, $push_type) {
    error_log('开始推送到 Cloudflare: ' . implode(', ', $post_ids));
}, 10, 2);
```

### 示例 2：修改生成的 HTML

```php
<?php
// 为静态 HTML 添加自定义 meta 标签
add_filter('wp_cf_generated_html', function($html, $post_id) {
    $custom_meta = '<meta name="generator" content="WP CF Static Push">';
    $html = str_replace('</head>', $custom_meta . '</head>', $html);
    return $html;
}, 10, 2);
```

### 示例 3：推送成功后发送通知

```php
<?php
// 推送成功后发送邮件通知
add_action('wp_cf_after_push', function($post_ids, $push_type, $result) {
    if ($result['success']) {
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            '内容已推送到 Cloudflare',
            '成功推送 ' . count($post_ids) . ' 个项目。'
        );
    }
}, 10, 3);
```

### 示例 4：条件推送

```php
<?php
// 只推送"公开"分类的文章
add_action('publish_post', function($post_id) {
    if (has_category('public', $post_id)) {
        // 手动触发推送
        do_action('wp_cf_manual_push', array($post_id), 'single');
    }
}, 20);
```

### 示例 5：添加自定义静态文件

```php
<?php
// 添加自定义 404 页面和 robots.txt
add_filter('wp_cf_static_files', function($files) {
    // 自定义 404 页面
    $files['404.html'] = '<html><body><h1>页面未找到</h1></body></html>';
    
    // robots.txt
    $files['robots.txt'] = "User-agent: *\nAllow: /\nSitemap: /sitemap.xml";
    
    return $files;
}, 10);
```

### 示例 6：推送前验证

```php
<?php
// 只在生产环境推送
add_filter('wp_cf_should_push', function($should_push, $post_id) {
    if (defined('WP_ENV') && WP_ENV !== 'production') {
        return false;
    }
    return $should_push;
}, 10, 2);
```

## 常见自定义需求

### 计划任务推送

```php
<?php
// 每天凌晨 2 点自动推送全站
add_action('wp', function() {
    if (!wp_next_scheduled('cf_daily_push')) {
        wp_schedule_event(strtotime('02:00:00'), 'daily', 'cf_daily_push');
    }
});

add_action('cf_daily_push', function() {
    $posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'numberposts' => -1
    ));
    
    $post_ids = wp_list_pluck($posts, 'ID');
    do_action('wp_cf_scheduled_push', $post_ids, 'all');
});
```

### 清理旧内容

```php
<?php
// 删除文章时从 Cloudflare 移除
add_action('delete_post', function($post_id) {
    // 这里可以添加从 Cloudflare 删除内容的逻辑
    // 当前版本建议重新推送全站来更新
}, 10);
```

## 调试技巧

### 启用调试日志

在 `wp-config.php` 中添加：

```php
<?php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 查看详细推送信息

```php
<?php
add_action('wp_cf_push_log', function($message, $level) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[CF-PUSH][' . strtoupper($level) . '] ' . $message);
    }
}, 10, 2);
```

## 性能优化

### 异步推送

```php
<?php
// 将推送操作放到后台任务
add_action('publish_post', function($post_id) {
    wp_schedule_single_event(time() + 10, 'cf_async_push', array($post_id));
}, 10);

add_action('cf_async_push', function($post_id) {
    do_action('wp_cf_manual_push', array($post_id), 'single');
});
```

## 注意事项

1. 所有代码示例应放在主题的 `functions.php` 或自定义插件中
2. 不要修改插件核心文件
3. 测试代码前先备份网站
4. 使用钩子和过滤器进行扩展

## 获取更多帮助

- README.md - 基本功能说明
- INSTALL.md - 安装和配置
- 推送日志 - 查看错误信息
- GitHub Issues - https://github.com/cyberxsboy/WP-Cloudflare-Static-Push/issues
