# 安装指南

**作者**: 泥人传说  
**项目地址**: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push

---

## 安装方法

### 方法 1：WordPress 后台上传

1. 将插件文件夹压缩为 zip 文件
2. 登录 WordPress 后台
3. 进入 插件 → 安装插件 → 上传插件
4. 选择 zip 文件并上传
5. 点击"立即安装"
6. 安装完成后点击"启用插件"

### 方法 2：FTP 上传

1. 使用 FTP 客户端连接到服务器
2. 将 `wp-cloudflare-static-push` 文件夹上传到 `/wp-content/plugins/`
3. 确保文件权限正确（通常是 755）
4. 登录 WordPress 后台
5. 进入 插件 → 已安装的插件
6. 找到"WP Cloudflare Static Push"并点击"启用"

## 配置步骤

### 1. 获取 Cloudflare API Token

1. 登录 Cloudflare：https://dash.cloudflare.com/
2. 点击右上角头像 → My Profile
3. 选择 API Tokens 标签
4. 点击 Create Token 按钮
5. 选择 Edit Cloudflare Workers 模板
6. 或创建自定义 Token，确保包含以下权限：
   - Account → Cloudflare Pages → Edit
   - Account → Workers Scripts → Edit
7. 点击 Continue to summary
8. 点击 Create Token
9. **重要**：复制并保存 Token（只显示一次）

### 2. 获取 Account ID

1. 在 Cloudflare Dashboard 中选择任意域名
2. 向下滚动到页面右侧栏
3. 找到 Account ID
4. 点击复制图标

### 3. 配置插件

1. 在 WordPress 后台找到"CF 静态推送"菜单
2. 进入设置向导

**第 1 步：API 配置**
- 粘贴 API Token
- 粘贴 Account ID
- 点击"测试连接"（必须成功才能继续）
- 点击"下一步"

**第 2 步：项目选择**
- 选择项目类型：
  - Cloudflare Pages（推荐新手）
  - Cloudflare Workers（高级功能）
- 输入项目名称（格式：小写字母、数字、连字符）
- 点击"下一步"

**第 3 步：推送设置**
- 勾选需要的自动推送选项：
  - 发布新文章/页面时自动推送
  - 更新文章/页面时自动推送
- 点击"保存并完成设置"

**第 4 步：完成**
- 查看成功提示
- 记下配置信息
- 开始使用插件

## 验证安装

### 测试推送

1. 进入"推送管理"页面
2. 点击"推送首页"
3. 等待推送完成
4. 查看推送日志确认成功

### 验证 Cloudflare

1. 登录 Cloudflare Dashboard
2. 进入 Workers & Pages
3. 找到您的项目
4. 查看最新部署状态
5. 访问项目 URL 确认内容显示

## 常见安装问题

### 插件激活失败

**原因**：PHP 或 WordPress 版本不符合要求

**解决**：
- 检查 PHP 版本（需要 7.4+）
- 检查 WordPress 版本（需要 6.0+）
- 查看错误提示信息

### 无法看到菜单

**原因**：权限不足

**解决**：
- 确保使用管理员账户登录
- 检查用户角色权限

### 测试连接失败

**原因**：API Token 或 Account ID 错误

**解决**：
- 重新复制 Token（确保没有多余空格）
- 确认 Account ID 正确
- 检查 Token 权限是否足够
- 测试服务器网络连接

## 系统要求检查

运行以下命令检查系统信息：

### PHP 版本
```bash
php -v
```
需要：7.4 或更高

### WordPress 版本
在 WordPress 后台：仪表盘 → 概览 → 右下角查看版本

需要：6.0 或更高

### 网络连接测试
```bash
curl https://api.cloudflare.com/client/v4/
```
应该返回 JSON 响应

## 卸载

如需卸载插件：

1. 停用插件
2. 删除插件
3. 数据库表和选项会自动清理

**注意**：卸载后所有配置和日志都会被删除，无法恢复。

## 获取帮助

- 查看 README.md 了解功能说明
- 查看 EXAMPLES.md 获取使用示例
- 查看推送日志中的错误信息
- GitHub Issues：https://github.com/cyberxsboy/WP-Cloudflare-Static-Push/issues
- 作者主页：https://nirenchuanshuo.com
