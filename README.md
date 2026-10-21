# Chiri for Typecho

将 [astro-chiri](https://github.com/the3ash/astro-chiri) 移植为 Typecho 主题。

原项目是基于 Astro 的静态博客主题，以极简排版和克制的交互著称。本主题使用 PHP 模板和原生 JavaScript 实现对应的阅读体验，部分构建期能力改为运行期处理，功能边界见下文。

完整配置、渲染流程、已知问题和验证范围见 [主题使用与实现分析](THEME-GUIDE.md)。

> **主题信息（后台「控制台 → 外观」显示的作者/版本/网站）** 在 `index.php`
> 顶部的 docblock 中声明，Typecho 用 `Plugin::parseInfo()` 解析：
> `@package` 主题名、`@author` 作者、`@version` 版本、`@link` 作者网站。
> 当前作者为 **歪叉**（<https://waix.cc>）。

## 特性

- 极简单行式文章列表，悬停时其余条目淡出
- 明暗双主题，跟随系统偏好并支持手动切换，切换无闪烁
- 侧边悬浮目录，宽度足够时自动显示，滚动高亮当前章节
- 代码块复制按钮（悬停浮现）
- 正文图片点击放大
- 阅读时长、部分 KaTeX 数学公式渲染（范围见详细说明）
- 顶部渐变遮罩，正文滚动时提供视觉分隔

## 安装

将 `typecho-chiri` 目录上传到 Typecho 的 `usr/themes/` 下，在「控制台 → 外观」中启用即可。

启用后建议进入「控制台 → 设置外观 → 主题设置」按需调整配置。

## 目录结构

```
typecho-chiri/
├── index.php          首页 / 文章列表
├── post.php           文章详情
├── page.php           独立页面
├── archive.php        分类 / 标签 / 搜索归档
├── 404.php            404 页面
├── comments.php       评论列表
├── header.php         公共头部
├── footer.php         公共页脚
├── functions.php      主题入口与后台设置项
├── core/
│   ├── config.php     配置中心（含默认值与后台映射）
│   └── utils.php      日期格式化、阅读时长、目录提取等
└── assets/
    ├── css/
    │   ├── global.css   设计令牌与基础排版（源自 global.css）
    │   ├── post.css     正文排版（源自 post.css）
    │   ├── layout.css   头部、页脚、遮罩、返回按钮
    │   ├── components.css  文章列表、目录、图片查看器
    │   └── code.css     代码高亮配色
    ├── js/
    │   ├── theme-head.js  首屏防闪烁（需在 head 中同步加载）
    │   ├── page-transition.js 页面转场与降级动画
    │   ├── toc.js         目录滚动与高亮（定位由 CSS 处理）
    │   ├── copy-code.js   代码复制
    │   ├── image-viewer.js 图片查看器
    │   ├── gradient-mask.js 顶部遮罩
    │   └── katex-loader.js  KaTeX 按需加载
    └── fonts/
        ├── Inter.woff2
        └── Besley-Italic.woff2
```

## 与原主题的差异

原项目是静态站点，所有 Markdown 处理都在构建期完成。Typecho 是运行期渲染，因此部分能力需要重新实现：

| 能力 | 原实现 | 本主题实现 |
|------|--------|-----------|
| 文章数据 | Astro Content Collections | Typecho `Widget` |
| 目录生成 | remark-toc 插件 | `chiri_extract_toc()` 运行期提取 |
| 代码高亮 | Shiki 构建期着色 | Highlight.js 运行期着色 + 灰阶配色，与复制开关关联 |
| 代码复制 | rehype-copy-code 插件 | `chiri_decorate_code_blocks()` 注入容器 |
| 阅读时长 | 构建期统计 | `chiri_reading_time()` 运行期估算 |
| 数学公式 | rehype-katex 预渲染 | 检测到部分公式标记时加载 KaTeX，未实现通用 auto-render |
| 链接卡片 | 构建期抓取元数据 | 未移植 |
| OG 图片 | 构建期生成 | 主题未生成，具体输出取决于内核与插件 |
| MDX 组件 | MDX 集成 | 未移植 |

样式与交互以原主题为参考，但 Typecho 运行期渲染、资源加载和配置方式不同，不能据此认定全部行为与原版完全一致。

## 配置

主题默认配置集中在 `core/config.php` 的 `chiri_default_config()`，通过 `chiri_option_map()` 与后台字段关联。后台表单使用 `chiri_default()` 读取初值；已保存的后台值优先于默认值。新增配置时需要同步默认值、映射与 `functions.php` 中的表单。

模板中统一通过 `chiri_config('a.b')` 读取，例如：

```php
chiri_config('general.contentWidth')   // 内容区宽度
chiri_config('date.dateFormat')        // 日期格式
chiri_has_toc()                        // 是否启用目录
```

### 站点描述不在这里配

主题不自行生成 `meta description` 或 `keywords`，而是调用 Typecho 的 `$this->header()`，由内核及插件输出。站点描述和关键词应在 Typecho 相应设置中配置，文章标签也会参与内核 SEO 输出；具体标签以实际部署版本与插件为准。

## 首页简介

首页 About 区块读取主题设置中的**首页简介内容**（`aboutContent`），支持 HTML。需同时开启**首页显示个人简介**；内容为空时不显示。它不会自动读取默认分类文章或独立页面。

## 已知内核 bug 与应对（Typecho 1.3 实测）

Typecho 1.3 内置的 HyperDown 渲染器（`var/Utils/HyperDown.php` 约第 490 行）处理带 title 的内联图片时输出畸形属性，且丢弃 alt 文本：`![alt](/x.png "t")` 会渲染为 `alt=" title="t"`（引号未闭合）。本沙箱实测该 bug 在 2025-10 与 2026-10 的版本均存在，属上游问题。

主题针对此做了两层防御：

- `chiri_decorate_images()` 已重写为引号感知的属性解析（`chiri_parse_img_attrs()` / `chiri_build_img_tag()`），任意属性顺序、单双引号、实体值均可正确处理，并自动补写 `data-chiri-wrapped` 标记；
- 检测到引号未闭合的畸形 img 标签时整体跳过装饰，原样输出，避免二次破坏。

要让带 title 的图片恢复正常（alt 显示为图片说明），还需给内核打补丁。将：

```php
[$url, $title] = $this->cleanUrl($url, true);
$title = empty($title) ? $escaped : " title=\"{$title}\"";

return $this->makeHolder(
    "<img src=\"{$url}\" alt=\"{$title}\" title=\"{$title}\">"
);
```

替换为：

```php
[$url, $title] = $this->cleanUrl($url, true);
$title = empty($title)
    ? ''
    : ' title="' . htmlspecialchars($this->escapeBracket($title), ENT_QUOTES, 'UTF-8') . '"';

return $this->makeHolder(
    "<img src=\"{$url}\" alt=\"{$escaped}\"{$title}>"
);
```

内核升级会覆盖此改动，建议同时向 Typecho 上游反馈。

## 验证范围

当前目录仅包含主题。静态分析发现目录定位、数学公式、代码块属性处理等仍有需要验证或改进的边界，详见 [主题使用与实现分析](THEME-GUIDE.md)。7 个本地 JavaScript 文件通过语法检查；尚未在完整 Typecho 环境中验证 PHP、后台保存与页面效果。

## 许可

本主题沿用 astro-chiri 的 MIT 协议，版权与许可文本见根目录 [LICENSE](LICENSE)。Inter 与 Besley 字体采用 SIL Open Font License 1.1，许可文本见 [assets/fonts/OFL.txt](assets/fonts/OFL.txt)。
