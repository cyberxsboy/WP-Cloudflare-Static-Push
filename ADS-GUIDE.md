# 广告位管理使用指南

**作者**: 泥人传说  
**作者主页**: https://nirenchuanshuo.com  
**项目地址**: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push

本插件支持在推送到 Cloudflare 的静态页面中自动插入广告代码。

## 支持的广告类型

- **Google AdSense** - 最流行的广告平台
- **自定义 HTML 广告** - 图片banner、文字广告等
- **JavaScript 广告** - 任何第三方广告平台

## 广告位置

插件提供5个广告位置：

1. **文章顶部** - 在文章标题下方显示
2. **文章底部** - 在文章内容结束后显示
3. **侧边栏** - 在侧边栏显示
4. **内容前** - 在文章第一段前显示
5. **首段后** - 在文章第一段后显示

## 配置步骤

### 方式 1：在设置向导中配置

1. 完成步骤 1-3 的基础配置
2. 在步骤 4"广告设置"中：
   - 勾选"启用自动广告插入"
   - 选择要使用的广告位
   - 粘贴广告代码
   - 点击"保存并完成设置"
3. 或点击"跳过广告设置"以后再配置

### 方式 2：在广告位管理页面配置

1. 进入 WordPress 后台 → CF 静态推送 → 广告位管理
2. 勾选"启用自动广告插入"
3. 为每个广告位配置：
   - 勾选"启用"
   - 选择广告类型
   - 粘贴广告代码
4. 点击"保存广告配置"
5. 重新推送内容使广告生效

## Google AdSense 配置示例

### 1. 获取 AdSense 代码

1. 登录 https://www.google.com/adsense/
2. 进入"广告" → "按广告单元"
3. 点击"新建广告单元"
4. 选择广告类型（展示广告、信息流广告等）
5. 自定义广告设置
6. 点击"创建" → 复制广告代码

### 2. 粘贴代码

复制类似以下的代码到广告位：

```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX"
     crossorigin="anonymous"></script>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
     data-ad-slot="XXXXXXXXXX"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>
```

### 3. 推送内容

保存广告配置后，重新推送内容：
- 推送首页
- 推送全站
- 或推送特定文章

## 自定义 HTML 广告示例

### 图片 Banner

```html
<div style="text-align: center; margin: 20px 0;">
    <a href="https://example.com" target="_blank">
        <img src="https://example.com/banner.jpg" 
             alt="广告" 
             style="max-width: 100%; height: auto;">
    </a>
</div>
```

### 文字广告

```html
<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h4>推荐产品</h4>
    <p>这是一个很棒的产品描述...</p>
    <a href="https://example.com" target="_blank" style="color: #0066cc;">
        了解更多 →
    </a>
</div>
```

## 第三方广告平台

### 示例：百度联盟

```html
<script type="text/javascript">
    (window.slotbydup=window.slotbydup || []).push({
        id: 'XXXXXXXX',
        container: '_container_id',
        size: '300,250',
        display: 'inlay-fix'
    });
</script>
```

### 示例：其他广告网络

任何提供 HTML 或 JavaScript 代码的广告平台都支持。

## 广告位置选择建议

### 文章顶部
- **优点**：可见度高
- **缺点**：可能影响阅读体验
- **适合**：重要的广告内容

### 文章底部
- **优点**：不干扰阅读
- **缺点**：可见度较低
- **适合**：相关推荐、次要广告

### 侧边栏
- **优点**：始终可见
- **缺点**：移动端效果差
- **适合**：长期推广内容

### 首段后
- **优点**：用户已进入阅读状态
- **缺点**：需要合适的广告类型
- **适合**：信息流广告

## 注意事项

### 广告数量

不建议在单个页面放置过多广告：
- 最多 3-4 个广告位
- 避免影响用户体验
- 遵守广告平台政策

### Google AdSense 政策

- 每页最多 3 个内容广告单元
- 不能点击自己的广告
- 不能诱导用户点击
- 内容需符合 AdSense 政策

### 响应式设计

确保广告在移动设备上正常显示：
```html
<ins class="adsbygoogle"
     style="display:block"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
```

### 广告加载性能

- 使用异步加载（async）
- 避免阻塞页面渲染
- Google AdSense 已自动优化

## 测试广告

### 1. 推送测试内容

配置广告后，先推送单篇文章测试：
1. 编辑一篇文章
2. 点击"推送到Cloudflare"
3. 访问 Cloudflare URL 查看效果

### 2. 检查广告显示

- 广告是否正确显示？
- 位置是否合适？
- 移动端是否正常？
- 加载速度如何？

### 3. 调整优化

根据测试结果调整：
- 修改广告位置
- 调整广告样式
- 优化广告数量

## 禁用广告

如需临时禁用广告：

1. 进入"广告位管理"
2. 取消勾选"启用自动广告插入"
3. 保存设置
4. 重新推送内容

或者单独禁用某个广告位：
- 取消勾选该广告位的"启用"
- 保存并重新推送

## 常见问题

### 广告不显示？

1. 确认已启用广告功能
2. 检查广告代码是否正确
3. 确认已重新推送内容
4. 查看浏览器控制台错误

### Google AdSense 显示空白？

- AdSense 需要审核时间
- 新站点可能需要等待
- 检查是否违反政策
- 确认代码正确无误

### 如何查看广告收入？

- Google AdSense：登录 AdSense 后台
- 其他平台：登录对应的广告平台

### 广告影响 SEO 吗？

- 合理的广告不影响 SEO
- 避免过多广告
- 确保内容质量优先
- Google 推荐内容与广告比例合理

## 高级技巧

### 条件显示广告

可以通过修改代码实现：
- 仅在特定分类显示
- 仅在特定时间显示
- 根据访问来源显示不同广告

参考 EXAMPLES.md 中的代码示例。

### A/B 测试

测试不同广告位置和类型的效果：
1. 配置版本 A
2. 推送并记录数据
3. 修改为版本 B
4. 对比收益和用户体验

### 多语言广告

如果网站支持多语言，可以为不同语言配置不同广告。

## 获取帮助

- 查看 README.md 了解基本功能
- 查看 EXAMPLES.md 获取代码示例
- Google AdSense 帮助：https://support.google.com/adsense
- 插件问题：https://github.com/cyberxsboy/WP-Cloudflare-Static-Push/issues

---

**提示**：合理使用广告功能可以在不影响用户体验的前提下增加收益。

