# WP Cloudflare Static Push

自动推送 WordPress 静态内容到 Cloudflare Workers 和 Pages 的强大插件。

**作者**: 泥人传说  
**作者主页**: https://nirenchuanshuo.com  
**项目地址**: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
**插件下载地址**：https://github.com/cyberxsboy/WP-Cloudflare-Static-Push/releases/download/1.0/wp-cloudflare-static-push.zip

## 核心功能

- 5步引导式配置，5分钟完成设置
- 支持 Cloudflare Pages 和 Workers 两种部署方式
- 自动推送：发布/更新文章时自动推送到 Cloudflare
- 手动推送：单篇、批量、全站推送
- 静态生成：HTML、RSS、Sitemap 自动生成
- 广告植入：支持 Google AdSense 和自定义HTML/JS广告
- 推送日志：详细记录每次推送操作

## 系统要求

- WordPress 6.0 或更高版本
- PHP 7.4 或更高版本
- Cloudflare 账户（免费或付费）

## 快速开始

### 1. 安装插件

将 `wp-cloudflare-static-push` 文件夹上传到 `/wp-content/plugins/`

在 WordPress 后台激活插件

### 2. 获取 Cloudflare 凭证

**API Token:**
1. 访问 https://dash.cloudflare.com/profile/api-tokens
2. 点击 Create Token
3. 选择 Edit Cloudflare Workers 模板
4. 复制生成的 Token

**Account ID:**
1. 访问 https://dash.cloudflare.com/
2. 选择任意域名
3. 在右侧找到 Account ID 并复制

### 3. 配置插件（5步向导）

进入 WordPress 后台 → CF 静态推送

**步骤 1: API 配置**
- 输入 API Token 和 Account ID
- 点击"测试连接"确认配置正确

**步骤 2: 项目选择**
- 选择 Cloudflare Pages（推荐新手）或 Workers
- 输入项目名称（只能包含小写字母、数字、连字符）

**步骤 3: 推送设置**
- 勾选"发布新文章时自动推送"
- 勾选"更新文章时自动推送"

**步骤 4: 广告设置（可选）**
- 启用广告插入功能
- 配置广告位（顶部、底部、侧边栏等）
- 粘贴 Google AdSense 或自定义广告代码
- 或点击"跳过"

**步骤 5: 完成**
- 配置成功，开始使用！

### 4. 开始推送

**自动推送:** 发布或更新文章时自动推送

**手动推送:**
- 进入"推送管理"页面
- 选择推送首页、全站或特定内容

**编辑器推送:**
- 在文章编辑页面
- 点击"推送到Cloudflare"按钮

## 推送方式

### 自动推送
发布或更新文章时自动推送到 Cloudflare（可在设置中启用/禁用）

### 手动推送
- **推送首页**: 更新网站首页
- **推送全站**: 推送所有文章、页面、RSS、Sitemap
- **推送特定内容**: 选择一篇或多篇文章推送

### 编辑器推送
在文章编辑页面的发布面板中直接推送当前文章

## 生成的静态内容

- 所有文章和页面的 HTML
- 网站首页（index.html）
- 归档页面
- RSS Feed（feed.xml）
- Sitemap（sitemap.xml）
- 自动转换 URL 为相对路径
- 自动插入配置的广告代码

## 查看推送日志

进入 CF 静态推送 → 推送日志

可以查看：
- 推送时间
- 推送的文章/页面
- 推送类型
- 推送状态（成功/失败）
- 详细错误信息

## Cloudflare Pages vs Workers

### Cloudflare Pages（推荐）
- 适合静态网站和博客
- 简单易用，自动部署
- 提供预览 URL
- 免费 SSL 证书

### Cloudflare Workers
- 适合需要边缘计算的场景
- 可以添加动态逻辑
- 更灵活的路由控制
- 全球 CDN 分发

## 常见问题

### 测试连接失败？
- 检查 API Token 是否正确（没有多余空格）
- 确认 Account ID 是否正确
- 验证 Token 权限包含 Pages 和 Workers
- 检查服务器网络连接

### 推送失败？
- 查看推送日志中的错误信息
- 确认项目名称格式正确（小写字母、数字、连字符）
- 检查 Cloudflare 账户状态
- 尝试推送较小的内容测试

### 自动推送不工作？
- 确认已在设置中启用自动推送
- 检查文章状态是否为"已发布"
- 查看推送日志是否有错误
- 尝试手动推送测试

### 推送需要多长时间？
- 单篇文章：1-5 秒
- 首页：1-3 秒
- 50 篇文章：约 2 分钟
- 500 篇文章：约 10 分钟

### 推送后多久生效？
Cloudflare 的全球 CDN 通常在几秒内完成分发，完全生效需要 1-2 分钟。

### 如何配置广告？
1. 进入"广告位管理"页面
2. 启用广告功能
3. 选择广告位置（顶部、底部、侧边栏等）
4. 粘贴广告代码（支持 HTML 和 JavaScript）
5. 保存后重新推送内容即可

### 支持哪些广告？
- Google AdSense（推荐）
- 自定义 HTML 广告
- JavaScript 广告代码
- 任何第三方广告平台

## 文件结构

```
wp-cloudflare-static-push/
├── wp-cloudflare-static-push.php    主文件
├── includes/                         核心类
│   ├── class-wp-cf-static-push.php
│   ├── class-cloudflare-api.php
│   ├── class-static-generator.php
│   └── class-admin-interface.php
├── assets/                           前端资源
│   ├── css/admin-style.css
│   └── js/admin-script.js
└── languages/                        翻译文件
```

## 安全说明

- API Token 加密存储在数据库中
- 只有管理员可以配置插件
- 所有操作都有权限检查
- 推荐定期轮换 API Token

## 性能优化建议

- 避免在高峰期推送全站内容
- 使用自动推送减少手动操作
- 大量内容可以分批推送
- 合理使用 Cloudflare 缓存策略

## 兼容性

已测试兼容：
- WordPress 6.0 - 6.4+
- PHP 7.4 - 8.2
- 经典编辑器和区块编辑器

## 更新日志

### 1.0.0 (2025-11-06)
- 首次发布
- 支持 Cloudflare Pages 和 Workers
- 4步引导式设置向导
- 自动和手动推送功能
- 推送日志记录
- 静态内容生成（HTML、RSS、Sitemap）

## 技术支持

- 文档：查看插件目录中的其他文档
- GitHub：https://github.com/cyberxsboy/WP-Cloudflare-Static-Push
- 作者主页：https://nirenchuanshuo.com

## 许可证

GPL v2 or later

完整许可证文本：LICENSE.txt

## 致谢

感谢 Cloudflare 提供强大的边缘计算平台。
感谢 WordPress 社区的支持。

