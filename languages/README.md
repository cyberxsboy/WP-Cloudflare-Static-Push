# 翻译文件目录

**作者**: 泥人传说  
**项目地址**: https://github.com/cyberxsboy/WP-Cloudflare-Static-Push

本目录用于存放插件的国际化翻译文件。

## 当前支持的语言

- 🇨🇳 **简体中文** (zh_CN) - 插件默认语言
- 🇺🇸 **English** (en_US) - 欢迎贡献翻译！

## 翻译文件说明

### 文件类型

1. **POT 文件** (`.pot`) - 模板文件
   - 文件名: `wp-cf-static-push.pot`
   - 包含所有可翻译字符串
   - 用作其他语言的翻译模板

2. **PO 文件** (`.po`) - 翻译源文件
   - 文件名格式: `wp-cf-static-push-{locale}.po`
   - 人类可读的翻译文本
   - 可用 Poedit 等工具编辑

3. **MO 文件** (`.mo`) - 编译后的翻译文件
   - 文件名格式: `wp-cf-static-push-{locale}.mo`
   - 机器可读的二进制文件
   - WordPress 实际使用的文件

## 如何添加新语言翻译

### 方法 1: 使用 Poedit (推荐)

1. **下载 Poedit**
   - 访问: https://poedit.net/
   - 下载并安装

2. **创建新翻译**
   ```
   1. 打开 Poedit
   2. 文件 → 从 POT 文件新建
   3. 选择 wp-cf-static-push.pot
   4. 选择目标语言（如 English）
   5. 开始翻译
   ```

3. **翻译字符串**
   - 逐条翻译原文到目标语言
   - 保持格式占位符 (%s, %d 等)
   - 注意上下文和语气

4. **保存文件**
   ```
   文件 → 保存为
   保存为: wp-cf-static-push-en_US.po
   自动生成: wp-cf-static-push-en_US.mo
   ```

5. **测试翻译**
   - 将 .po 和 .mo 文件放入 languages 目录
   - 在 WordPress 中切换语言
   - 检查翻译效果

### 方法 2: 手动创建 (高级)

1. **复制 POT 文件**
   ```bash
   cp wp-cf-static-push.pot wp-cf-static-push-en_US.po
   ```

2. **编辑 PO 文件**
   - 更新文件头信息（语言、团队等）
   - 翻译 msgid 为 msgstr

3. **生成 MO 文件**
   ```bash
   msgfmt wp-cf-static-push-en_US.po -o wp-cf-static-push-en_US.mo
   ```

## 生成 POT 模板文件

如果需要更新 POT 模板文件（代码更新后）：

### 使用 WP-CLI

```bash
wp i18n make-pot . languages/wp-cf-static-push.pot
```

### 使用 Poedit

```
1. 打开 Poedit
2. 文件 → 新建
3. 设置源代码路径
4. 扫描源代码
5. 保存为 .pot 文件
```

### 手动使用 xgettext

```bash
find . -name "*.php" | xgettext \
  --from-code=UTF-8 \
  --language=PHP \
  --keyword=__ \
  --keyword=_e \
  --keyword=_n:1,2 \
  --keyword=_x:1,2c \
  --keyword=_ex:1,2c \
  --keyword=esc_html__ \
  --keyword=esc_html_e \
  --keyword=esc_attr__ \
  --keyword=esc_attr_e \
  --output=languages/wp-cf-static-push.pot \
  --files-from=-
```

## 翻译字符串规范

### 在代码中标记可翻译字符串

```php
<?php
// 简单翻译
__('文本', 'wp-cf-static-push');

// 翻译并输出
_e('文本', 'wp-cf-static-push');

// 带复数形式
_n('单数', '复数', $count, 'wp-cf-static-push');

// 带上下文
_x('文本', '上下文', 'wp-cf-static-push');

// HTML 转义
esc_html__('文本', 'wp-cf-static-push');
esc_html_e('文本', 'wp-cf-static-push');

// 属性转义
esc_attr__('文本', 'wp-cf-static-push');
esc_attr_e('文本', 'wp-cf-static-push');
?>
```

### 翻译注意事项

1. **保持占位符**
   ```php
   // 原文
   sprintf(__('推送了 %d 篇文章', 'wp-cf-static-push'), $count);
   
   // 英文翻译应该保持 %d
   "Pushed %d posts"
   ```

2. **注意复数形式**
   ```php
   // 不同语言有不同的复数规则
   _n('%d post', '%d posts', $count, 'wp-cf-static-push');
   ```

3. **保持格式**
   - 保留 HTML 标签
   - 保留换行符 \n
   - 保留特殊字符

4. **使用上下文**
   ```php
   // 当同一单词有不同含义时
   _x('Post', '文章', 'wp-cf-static-push');    // 名词
   _x('Post', '推送', 'wp-cf-static-push');    // 动词
   ```

## 贡献翻译

### 欢迎的语言

- 🇬🇧 English
- 🇯🇵 日本語
- 🇰🇷 한국어
- 🇫🇷 Français
- 🇩🇪 Deutsch
- 🇪🇸 Español
- 🇷🇺 Русский
- 其他语言...

### 提交翻译

1. **Fork 项目**
2. **添加翻译文件**
   - .po 文件
   - .mo 文件
3. **测试翻译**
4. **提交 Pull Request**

### 翻译质量检查清单

- [ ] 所有字符串都已翻译
- [ ] 占位符保持不变
- [ ] 术语翻译一致
- [ ] 语法正确自然
- [ ] 在实际环境中测试
- [ ] .mo 文件已生成

## 本地化（l10n）文件结构

```
languages/
├── wp-cf-static-push.pot          # POT 模板（基础）
├── wp-cf-static-push-zh_CN.po     # 简体中文 PO
├── wp-cf-static-push-zh_CN.mo     # 简体中文 MO
├── wp-cf-static-push-en_US.po     # 英文 PO
├── wp-cf-static-push-en_US.mo     # 英文 MO
└── README.md                       # 本文件
```

## 常用工具

### 翻译工具
- **Poedit** - https://poedit.net/
- **Loco Translate** (WordPress 插件)
- **WPML** (商业)

### 在线协作
- **GlotPress** (WordPress.org 官方)
- **Crowdin**
- **Transifex**

### 命令行工具
- **WP-CLI** - `wp i18n`
- **gettext** - msgfmt, xgettext
- **msginit** - 初始化新翻译

## 语言代码参考

常用 WordPress 语言代码（locale）：

| 语言 | 代码 | 完整代码 |
|------|------|----------|
| 简体中文 | zh_CN | zh_CN |
| 繁体中文 | zh_TW | zh_TW |
| 英语(美国) | en_US | en_US |
| 英语(英国) | en_GB | en_GB |
| 日语 | ja | ja |
| 韩语 | ko_KR | ko_KR |
| 法语 | fr_FR | fr_FR |
| 德语 | de_DE | de_DE |
| 西班牙语 | es_ES | es_ES |
| 俄语 | ru_RU | ru_RU |

完整列表: https://make.wordpress.org/polyglots/teams/

## 在插件中启用翻译

插件已自动加载翻译文件：

```php
// 在 wp-cloudflare-static-push.php 中
load_plugin_textdomain(
    'wp-cf-static-push',
    false,
    dirname(plugin_basename(__FILE__)) . '/languages'
);
```

## 测试翻译

1. **安装语言包**
   ```
   WordPress 设置 → 通用 → 站点语言
   选择目标语言
   ```

2. **检查翻译**
   - 浏览插件所有页面
   - 验证所有文本已翻译
   - 检查排版和格式

3. **调试**
   ```php
   // 在 wp-config.php 中启用
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

## 维护翻译

### 更新流程

1. 代码更新后重新生成 POT
2. 使用 Poedit 更新现有 PO 文件
3. 翻译新增字符串
4. 重新生成 MO 文件
5. 测试并发布

### 版本控制

- ✅ 提交 .pot 文件
- ✅ 提交 .po 文件
- ⚠️ .mo 文件可选（可自动生成）

---

**感谢您为插件国际化做出贡献！** 🌍

需要帮助？在 GitHub Issues 中联系我们。

