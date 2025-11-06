<?php
/**
 * Cloudflare API 集成类
 * 
 * @author 泥人传说
 * @link https://nirenchuanshuo.com
 * @link https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_CF_Cloudflare_API {
    
    private $api_base_url = 'https://api.cloudflare.com/client/v4';
    
    /**
     * 测试Cloudflare连接
     */
    public function test_connection($api_token, $account_id) {
        $response = $this->make_request(
            "/accounts/{$account_id}/pages/projects",
            'GET',
            $api_token
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API请求失败: ' . $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            return array(
                'success' => true,
                'message' => '连接成功',
                'projects' => $body['result']
            );
        } else {
            $error_message = isset($body['errors'][0]['message']) 
                ? $body['errors'][0]['message'] 
                : '未知错误';
            return array(
                'success' => false,
                'message' => 'API错误: ' . $error_message
            );
        }
    }
    
    /**
     * 获取Pages项目列表
     */
    public function get_pages_projects($api_token, $account_id) {
        $response = $this->make_request(
            "/accounts/{$account_id}/pages/projects",
            'GET',
            $api_token
        );
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            return array(
                'success' => true,
                'projects' => $body['result']
            );
        }
        
        return array('success' => false, 'message' => '获取项目列表失败');
    }
    
    /**
     * 推送文件到Cloudflare
     */
    public function push_files($api_token, $account_id, $project_type, $project_name, $files) {
        if ($project_type === 'pages') {
            return $this->push_to_pages($api_token, $account_id, $project_name, $files);
        } elseif ($project_type === 'workers') {
            return $this->push_to_workers($api_token, $account_id, $project_name, $files);
        }
        
        return array('success' => false, 'message' => '未知的项目类型');
    }
    
    /**
     * 推送到Cloudflare Pages
     */
    private function push_to_pages($api_token, $account_id, $project_name, $files) {
        // 首先检查项目是否存在，不存在则创建
        $project_exists = $this->check_pages_project_exists($api_token, $account_id, $project_name);
        
        if (!$project_exists) {
            $create_result = $this->create_pages_project($api_token, $account_id, $project_name);
            if (!$create_result['success']) {
                return $create_result;
            }
        }
        
        // 使用 Direct Upload 方式上传文件
        // 注意：Cloudflare Pages API 需要使用 multipart/form-data
        $boundary = wp_generate_password(24, false);
        $body = '';
        
        // 构建 multipart 数据
        foreach ($files as $path => $content) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$path}\"\r\n";
            $body .= "Content-Type: " . $this->get_content_type($path) . "\r\n\r\n";
            $body .= $content . "\r\n";
        }
        $body .= "--{$boundary}--\r\n";
        
        $response = wp_remote_post(
            $this->api_base_url . "/accounts/{$account_id}/pages/projects/{$project_name}/deployments",
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary
                ),
                'body' => $body,
                'timeout' => 60,
                'sslverify' => true
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Pages部署失败: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 200 && $status_code < 300 && isset($body['success']) && $body['success']) {
            return array(
                'success' => true,
                'message' => '成功推送到Cloudflare Pages',
                'details' => array(
                    'deployment_id' => isset($body['result']['id']) ? $body['result']['id'] : '',
                    'url' => isset($body['result']['url']) ? $body['result']['url'] : "https://{$project_name}.pages.dev"
                )
            );
        } else {
            $error_message = isset($body['errors'][0]['message']) 
                ? $body['errors'][0]['message'] 
                : '未知错误 (HTTP ' . $status_code . ')';
            return array(
                'success' => false,
                'message' => 'Pages部署失败: ' . $error_message
            );
        }
    }
    
    /**
     * 检查 Pages 项目是否存在
     */
    private function check_pages_project_exists($api_token, $account_id, $project_name) {
        $response = $this->make_request(
            "/accounts/{$account_id}/pages/projects/{$project_name}",
            'GET',
            $api_token
        );
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code === 200;
    }
    
    /**
     * 创建 Pages 项目
     */
    private function create_pages_project($api_token, $account_id, $project_name) {
        $response = $this->make_request(
            "/accounts/{$account_id}/pages/projects",
            'POST',
            $api_token,
            array(
                'name' => $project_name,
                'production_branch' => 'main'
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => '创建Pages项目失败: ' . $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success']) {
            return array('success' => true);
        }
        
        return array(
            'success' => false,
            'message' => '创建Pages项目失败: ' . (isset($body['errors'][0]['message']) ? $body['errors'][0]['message'] : '未知错误')
        );
    }
    
    /**
     * 推送到Cloudflare Workers
     */
    private function push_to_workers($api_token, $account_id, $script_name, $files) {
        // 合并所有文件内容为一个Worker脚本
        $worker_script = $this->generate_worker_script($files);
        
        // 使用正确的 Workers API 端点和方法
        $response = wp_remote_request(
            $this->api_base_url . "/accounts/{$account_id}/workers/scripts/{$script_name}",
            array(
                'method' => 'PUT',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type' => 'application/javascript'
                ),
                'body' => $worker_script,
                'timeout' => 60,
                'sslverify' => true
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Workers部署失败: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code >= 200 && $status_code < 300 && isset($body['success']) && $body['success']) {
            // 获取 Worker 的子域名（如果有配置）
            $subdomain = $this->get_workers_subdomain($api_token, $account_id);
            $worker_url = $subdomain ? "https://{$script_name}.{$subdomain}.workers.dev" : '';
            
            return array(
                'success' => true,
                'message' => '成功推送到Cloudflare Workers',
                'details' => array(
                    'script_name' => $script_name,
                    'url' => $worker_url
                )
            );
        } else {
            $error_message = isset($body['errors'][0]['message']) 
                ? $body['errors'][0]['message'] 
                : '未知错误 (HTTP ' . $status_code . ')';
            return array(
                'success' => false,
                'message' => 'Workers部署失败: ' . $error_message
            );
        }
    }
    
    /**
     * 获取 Workers 子域名
     */
    private function get_workers_subdomain($api_token, $account_id) {
        $response = $this->make_request(
            "/accounts/{$account_id}/workers/subdomain",
            'GET',
            $api_token
        );
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['success']) && $body['success'] && isset($body['result']['subdomain'])) {
            return $body['result']['subdomain'];
        }
        
        return '';
    }
    
    /**
     * 生成Workers脚本
     */
    private function generate_worker_script($files) {
        $routes = array();
        
        foreach ($files as $path => $content) {
            $routes[$path] = $content;
        }
        
        $routes_json = json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        return <<<SCRIPT
const STATIC_CONTENT = {$routes_json};

addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
  const url = new URL(request.url);
  let path = url.pathname;
  
  // 默认首页
  if (path === '/') {
    path = '/index.html';
  }
  
  // 查找静态内容
  if (STATIC_CONTENT[path]) {
    const contentType = getContentType(path);
    return new Response(STATIC_CONTENT[path], {
      headers: {
        'content-type': contentType,
        'cache-control': 'public, max-age=3600'
      }
    });
  }
  
  return new Response('Not Found', { status: 404 });
}

function getContentType(path) {
  if (path.endsWith('.html')) return 'text/html; charset=utf-8';
  if (path.endsWith('.css')) return 'text/css';
  if (path.endsWith('.js')) return 'application/javascript';
  if (path.endsWith('.json')) return 'application/json';
  if (path.endsWith('.png')) return 'image/png';
  if (path.endsWith('.jpg') || path.endsWith('.jpeg')) return 'image/jpeg';
  if (path.endsWith('.gif')) return 'image/gif';
  if (path.endsWith('.svg')) return 'image/svg+xml';
  return 'text/plain';
}
SCRIPT;
    }
    
    /**
     * 发送API请求
     */
    private function make_request($endpoint, $method = 'GET', $api_token = '', $body = null, $extra_headers = array()) {
        $url = $this->api_base_url . $endpoint;
        
        $headers = array_merge(
            array(
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type' => 'application/json'
            ),
            $extra_headers
        );
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        );
        
        if ($body !== null) {
            if ($method === 'POST' || $method === 'PUT') {
                if (is_array($body)) {
                    $args['body'] = json_encode($body);
                } else {
                    $args['body'] = $body;
                }
            }
        }
        
        return wp_remote_request($url, $args);
    }
    
    /**
     * 获取内容类型
     */
    private function get_content_type($filename) {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $types = array(
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
        );
        
        return isset($types[$extension]) ? $types[$extension] : 'text/plain';
    }
}

