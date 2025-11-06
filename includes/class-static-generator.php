<?php
/**
 * 静态内容生成器类
 * 
 * @author 泥人传说
 * @link https://nirenchuanshuo.com
 * @link https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CF_Static_Generator {
    
    /**
     * 生成静态文件
     */
    public function generate($post_ids, $push_type) {
        $files = array();
        
        switch ($push_type) {
            case 'single':
                foreach ($post_ids as $post_id) {
                    $files = array_merge($files, $this->generate_post($post_id));
                }
                break;
                
            case 'homepage':
                $files = array_merge($files, $this->generate_homepage());
                break;
                
            case 'all':
                $files = array_merge($files, $this->generate_all_content());
                break;
        }
        
        return $files;
    }
    
    /**
     * 生成单篇文章/页面的静态内容
     */
    private function generate_post($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'publish') {
            return array();
        }
        
        $files = array();
        
        // 获取文章URL路径
        $permalink = get_permalink($post_id);
        $path = $this->url_to_path($permalink);
        
        // 生成HTML内容
        $html = $this->render_post_html($post);
        $files[$path] = $html;
        
        // 如果是首页文章，也生成index.html
        if (is_front_page()) {
            $files['index.html'] = $html;
        }
        
        return $files;
    }
    
    /**
     * 生成首页
     */
    private function generate_homepage() {
        $files = array();
        
        // 获取首页内容
        $front_page_id = get_option('page_on_front');
        
        if ($front_page_id) {
            // 静态首页
            $html = $this->render_post_html(get_post($front_page_id));
        } else {
            // 博客首页
            $html = $this->render_blog_index();
        }
        
        $files['index.html'] = $html;
        
        return $files;
    }
    
    /**
     * 生成所有内容
     */
    private function generate_all_content() {
        $files = array();
        
        // 生成首页
        $files = array_merge($files, $this->generate_homepage());
        
        // 获取所有已发布的文章和页面
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
                $post_id = get_the_ID();
                $files = array_merge($files, $this->generate_post($post_id));
            }
            wp_reset_postdata();
        }
        
        // 生成文章列表页
        $files = array_merge($files, $this->generate_archive_pages());
        
        // 生成RSS
        $files['feed.xml'] = $this->generate_rss();
        
        // 生成sitemap
        $files['sitemap.xml'] = $this->generate_sitemap();
        
        return $files;
    }
    
    /**
     * 渲染文章HTML
     */
    private function render_post_html($post) {
        // 设置全局post
        global $wp_query;
        $original_query = $wp_query;
        
        $wp_query = new WP_Query(array('p' => $post->ID));
        $wp_query->the_post();
        
        // 开始输出缓冲
        ob_start();
        
        // 加载主题模板
        if ($post->post_type === 'page') {
            $template = get_page_template();
        } else {
            $template = get_single_template();
        }
        
        if ($template && file_exists($template)) {
            include $template;
        } else {
            // 使用默认模板
            $this->render_default_template($post);
        }
        
        $html = ob_get_clean();
        
        // 恢复原始查询
        $wp_query = $original_query;
        wp_reset_postdata();
        
        // 转换URL为相对路径
        $html = $this->convert_urls_to_relative($html);
        
        // 插入广告
        $html = $this->insert_ads($html, $post);
        
        return $html;
    }
    
    /**
     * 在 HTML 中插入广告
     */
    private function insert_ads($html, $post) {
        $settings = get_option('wp_cf_static_push_settings');
        
        // 如果未启用广告，直接返回
        if (empty($settings['enable_ads'])) {
            return $html;
        }
        
        $ad_positions = !empty($settings['ad_positions']) ? $settings['ad_positions'] : array();
        
        // 文章顶部广告 - 在标题后插入
        if (!empty($ad_positions['top']['enabled']) && !empty($ad_positions['top']['code'])) {
            $ad_code = $this->wrap_ad_code($ad_positions['top']['code'], 'ad-top');
            // 在 </h1> 后插入
            $html = preg_replace('#(</h1>)#i', '$1' . $ad_code, $html, 1);
        }
        
        // 内容前广告 - 在 <div class="content"> 后插入
        if (!empty($ad_positions['before_content']['enabled']) && !empty($ad_positions['before_content']['code'])) {
            $ad_code = $this->wrap_ad_code($ad_positions['before_content']['code'], 'ad-before-content');
            $html = preg_replace('#(<div[^>]*class=["\'][^"\']*content[^"\']*["\'][^>]*>)#i', '$1' . $ad_code, $html, 1);
        }
        
        // 首段后广告 - 在第一个 </p> 后插入
        if (!empty($ad_positions['after_first_paragraph']['enabled']) && !empty($ad_positions['after_first_paragraph']['code'])) {
            $ad_code = $this->wrap_ad_code($ad_positions['after_first_paragraph']['code'], 'ad-after-first-paragraph');
            $html = preg_replace('#(</p>)#i', '$1' . $ad_code, $html, 1);
        }
        
        // 文章底部广告 - 在内容结束前插入
        if (!empty($ad_positions['bottom']['enabled']) && !empty($ad_positions['bottom']['code'])) {
            $ad_code = $this->wrap_ad_code($ad_positions['bottom']['code'], 'ad-bottom');
            // 在 </article> 或 </div class="content"> 前插入
            $html = preg_replace('#(</article>|</div>(?=\s*</main>))#i', $ad_code . '$1', $html, 1);
        }
        
        // 侧边栏广告 - 在 sidebar 中插入
        if (!empty($ad_positions['sidebar']['enabled']) && !empty($ad_positions['sidebar']['code'])) {
            $ad_code = $this->wrap_ad_code($ad_positions['sidebar']['code'], 'ad-sidebar');
            // 在 <aside> 或 sidebar 开始处插入
            $html = preg_replace('#(<aside[^>]*>|<div[^>]*class=["\'][^"\']*sidebar[^"\']*["\'][^>]*>)#i', '$1' . $ad_code, $html, 1);
        }
        
        return $html;
    }
    
    /**
     * 包装广告代码
     */
    private function wrap_ad_code($code, $class = '') {
        $wrapper = '<div class="cf-ad-container ' . esc_attr($class) . '">';
        $wrapper .= $code;
        $wrapper .= '</div>';
        return $wrapper;
    }
    
    /**
     * 渲染博客索引页
     */
    private function render_blog_index() {
        global $wp_query;
        $original_query = $wp_query;
        
        $wp_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page', 10)
        ));
        
        ob_start();
        
        $template = get_home_template();
        if ($template && file_exists($template)) {
            include $template;
        } else {
            $this->render_default_blog_index();
        }
        
        $html = ob_get_clean();
        
        $wp_query = $original_query;
        wp_reset_postdata();
        
        return $this->convert_urls_to_relative($html);
    }
    
    /**
     * 生成归档页面
     */
    private function generate_archive_pages() {
        $files = array();
        
        // 文章归档
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page', 10),
            'paged' => 1
        );
        
        $query = new WP_Query($args);
        $total_pages = $query->max_num_pages;
        
        for ($page = 1; $page <= $total_pages; $page++) {
            $args['paged'] = $page;
            $query = new WP_Query($args);
            
            ob_start();
            
            if ($query->have_posts()) {
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
                echo '<title>' . get_bloginfo('name') . ' - 第' . $page . '页</title>';
                echo '</head><body><div class="archive-page">';
                
                while ($query->have_posts()) {
                    $query->the_post();
                    echo '<article>';
                    echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
                    echo '<div class="excerpt">' . get_the_excerpt() . '</div>';
                    echo '<time>' . get_the_date() . '</time>';
                    echo '</article>';
                }
                
                echo '</div></body></html>';
            }
            
            $html = ob_get_clean();
            wp_reset_postdata();
            
            $path = $page === 1 ? 'page/index.html' : "page/{$page}/index.html";
            $files[$path] = $this->convert_urls_to_relative($html);
        }
        
        return $files;
    }
    
    /**
     * 生成RSS
     */
    private function generate_rss() {
        $rss = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $rss .= '<channel>' . "\n";
        $rss .= '<title>' . esc_html(get_bloginfo('name')) . '</title>' . "\n";
        $rss .= '<link>' . esc_url(home_url('/')) . '</link>' . "\n";
        $rss .= '<description>' . esc_html(get_bloginfo('description')) . '</description>' . "\n";
        $rss .= '<language>' . get_bloginfo('language') . '</language>' . "\n";
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $rss .= '<item>' . "\n";
                $rss .= '<title>' . esc_html(get_the_title()) . '</title>' . "\n";
                $rss .= '<link>' . esc_url(get_permalink()) . '</link>' . "\n";
                $rss .= '<pubDate>' . get_the_date('r') . '</pubDate>' . "\n";
                $rss .= '<description><![CDATA[' . get_the_excerpt() . ']]></description>' . "\n";
                $rss .= '</item>' . "\n";
            }
            wp_reset_postdata();
        }
        
        $rss .= '</channel>' . "\n";
        $rss .= '</rss>';
        
        return $rss;
    }
    
    /**
     * 生成Sitemap
     */
    private function generate_sitemap() {
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // 首页
        $sitemap .= '<url>' . "\n";
        $sitemap .= '<loc>' . esc_url(home_url('/')) . '</loc>' . "\n";
        $sitemap .= '<changefreq>daily</changefreq>' . "\n";
        $sitemap .= '<priority>1.0</priority>' . "\n";
        $sitemap .= '</url>' . "\n";
        
        // 文章和页面
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $sitemap .= '<url>' . "\n";
                $sitemap .= '<loc>' . esc_url(get_permalink()) . '</loc>' . "\n";
                $sitemap .= '<lastmod>' . get_the_modified_date('c') . '</lastmod>' . "\n";
                $sitemap .= '<changefreq>weekly</changefreq>' . "\n";
                $sitemap .= '<priority>0.8</priority>' . "\n";
                $sitemap .= '</url>' . "\n";
            }
            wp_reset_postdata();
        }
        
        $sitemap .= '</urlset>';
        
        return $sitemap;
    }
    
    /**
     * 渲染默认模板
     */
    private function render_default_template($post) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($post->post_title); ?> - <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <header>
                <h1><a href="<?php echo home_url('/'); ?>"><?php bloginfo('name'); ?></a></h1>
            </header>
            <main>
                <article>
                    <h1><?php echo esc_html($post->post_title); ?></h1>
                    <div class="content">
                        <?php echo apply_filters('the_content', $post->post_content); ?>
                    </div>
                </article>
            </main>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * 渲染默认博客索引
     */
    private function render_default_blog_index() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php bloginfo('name'); ?> - <?php bloginfo('description'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <header>
                <h1><a href="<?php echo home_url('/'); ?>"><?php bloginfo('name'); ?></a></h1>
            </header>
            <main>
                <?php
                if (have_posts()) {
                    while (have_posts()) {
                        the_post();
                        ?>
                        <article>
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <div class="excerpt"><?php the_excerpt(); ?></div>
                            <time><?php the_date(); ?></time>
                        </article>
                        <?php
                    }
                }
                ?>
            </main>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
    
    /**
     * 将URL转换为路径
     */
    private function url_to_path($url) {
        $home_url = trailingslashit(home_url());
        $path = str_replace($home_url, '', $url);
        $path = trim($path, '/');
        
        if (empty($path)) {
            return 'index.html';
        }
        
        // 移除查询字符串和锚点
        $path = strtok($path, '?');
        $path = strtok($path, '#');
        
        // 如果路径不以文件扩展名结尾，添加/index.html
        if (!preg_match('/\.[a-z0-9]+$/i', $path)) {
            $path = $path . '/index.html';
        }
        
        return $path;
    }
    
    /**
     * 将绝对URL转换为相对路径
     */
    private function convert_urls_to_relative($html) {
        $home_url = trailingslashit(home_url());
        $site_url = trailingslashit(site_url());
        
        // 移除尾部斜杠以便匹配
        $home_url_no_slash = rtrim($home_url, '/');
        $site_url_no_slash = rtrim($site_url, '/');
        
        // 转换 href 和 src 属性中的绝对URL
        $html = preg_replace(
            '#(href|src)=["\']' . preg_quote($home_url_no_slash, '#') . '([^"\']*)["\'#]#i',
            '$1="$2"',
            $html
        );
        
        $html = preg_replace(
            '#(href|src)=["\']' . preg_quote($site_url_no_slash, '#') . '([^"\']*)["\'#]#i',
            '$1="$2"',
            $html
        );
        
        // 转换 CSS 中的 URL
        $html = preg_replace(
            '#url\([\'"]?' . preg_quote($home_url_no_slash, '#') . '([^\'")\s]*)[\'"]?\)#i',
            'url($1)',
            $html
        );
        
        // 确保所有链接以 / 开头
        $html = preg_replace('#(href|src)=["\']((?!http|//|#)[^"\']*)["\'#]#i', '$1="/$2"', $html);
        
        return $html;
    }
}

