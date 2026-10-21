<?php
/**
 * Chiri 主题配置中心（对应 Astro 源主题的 src/config.ts）
 *
 * 统一通过 chiri_config($key) 读取，取值优先级：后台「主题设置」 → 此处默认值。
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/** 默认配置 */
function chiri_default_config()
{
    return array(
        'site' => array(
            'title'       => 'CHIRI',
            'author'      => '歪叉',
            // 站点描述不在此配置：meta description 由 Typecho「设置 → 站点描述」提供
            'language'    => 'zh-CN',
            'favicon'     => '',
            'icp'         => '', // ICP 备案号，留空则不显示
        ),
        'general' => array(
            'contentWidth'          => '35rem', // 内容区宽度，支持 rem 单位
            'centeredLayout'        => true,     // true 居中，false 左对齐
            'themeToggle'           => false,    // 显示明暗切换按钮
            'showSearch'            => true,     // 显示搜索按钮（点击弹出搜索框）
            'postListDottedDivider' => false,    // 列表使用点状分隔线
            'footer'                => true,     // 显示页脚
            'fadeAnimation'         => true,     // 页面淡入动画
            'showAbout'             => true,     // 首页显示 About 区块
            'showReadingTime'       => true,     // 首页列表显示阅读时长
            'aboutContent'          => '',       // 首页简介（HTML）
        ),
        'date' => array(
            'dateFormat'    => 'YYYY-MM-DD', // YYYY-MM-DD / MM-DD-YYYY / DD-MM-YYYY / MONTH DAY YYYY / DAY MONTH YYYY
            'dateSeparator' => '.',          // 分隔符：. - /
            'dateOnRight'   => true,         // 列表中日期位置，true 在右
        ),
        'post' => array(
            'readingTime' => true, // 文章页显示阅读时长
            'showCategory' => true, // 文章页显示所属分类（隐式文本风格）
            'toc'         => true,  // 显示侧边目录
            'tocDepth'    => 3,     // 目录层级深度
            'imageViewer' => true,  // 图片查看器
            'copyCode'    => true,  // 代码块复制按钮
            'katex'       => true,  // 加载 KaTeX
            'comments'    => true,  // 显示评论区（文章页与独立页）
        ),
    );
}

/** 配置键到后台表单项的映射 */
function chiri_option_map()
{
    return array(
        'site.title'                    => 'siteTitle',
        'site.author'                   => 'siteAuthor',
        'site.favicon'                  => 'siteFavicon',
        'site.icp'                      => 'siteIcp',
        'general.contentWidth'          => 'contentWidth',
        'general.centeredLayout'        => 'centeredLayout',
        'general.themeToggle'           => 'themeToggle',
        'general.showSearch'            => 'showSearch',
        'general.postListDottedDivider' => 'postListDottedDivider',
        'general.footer'                => 'footer',
        'general.showAbout'             => 'showAbout',
        'general.showReadingTime'       => 'showReadingTime',
        'general.aboutContent'          => 'aboutContent',
        'date.dateFormat'               => 'dateFormat',
        'date.dateSeparator'            => 'dateSeparator',
        'date.dateOnRight'              => 'dateOnRight',
        'post.readingTime'              => 'readingTime',
        'post.showCategory'             => 'showCategory',
        'post.toc'                      => 'toc',
        'post.tocDepth'                 => 'tocDepth',
        'post.copyCode'                 => 'copyCode',
        'post.imageViewer'              => 'imageViewer',
        'post.katex'                    => 'katex',
        'post.comments'                 => 'comments',
    );
}

/** 按点号路径读取嵌套值 */
function chiri_pluck(array $config, $key, $default = null)
{
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/**
 * 读取配置默认值，供 themeConfig() 生成表单初值。
 *
 * ⚠️ 必须保持无副作用：主题设置页渲染时会调用它，此处访问 Helper::options() 会递归加载。
 */
function chiri_default($key, $default = null)
{
    static $defaults = null;

    if ($defaults === null) {
        $defaults = chiri_default_config();
    }

    return chiri_pluck($defaults, $key, $default);
}

/**
 * 读取主题配置（仅供模板渲染阶段；表单生成请用 chiri_default()）
 *
 * @param string|null $key 支持 'a.b'；传 null 返回完整配置
 */
function chiri_config($key = null, $default = null)
{
    $fallback = chiri_default($key, $default);

    if ($key === null) {
        return $fallback;
    }

    $map = chiri_option_map();
    if (!isset($map[$key])) {
        return $fallback;
    }

    // Helper 在主题安装等早期上下文不可用，一律降级为默认值，不让配置读取致命
    if (!class_exists('Helper')) {
        return $fallback;
    }

    $field = $map[$key];

    try {
        $options = Helper::options();

        if (!isset($options->$field) && !property_exists($options, $field)) {
            return $fallback;
        }

        $value = $options->$field;
    } catch (Exception $e) {
        return $fallback;
    } catch (Error $e) {
        return $fallback;
    }

    if ($value === null || $value === '') {
        return $fallback;
    }

    // 后台复选框存的是数组
    if (is_array($value)) {
        return in_array('1', $value, true);
    }

    switch ($field) {
        case 'centeredLayout':
        case 'dateOnRight':
            return ($value === '1' || $value === true || $value === 'true');

        case 'tocDepth':
            return (int) $value;

        default:
            return $value;
    }
}

/** 计算生效的内容宽度（rem），与 Astro BaseLayout 同一套规则：上限 50rem */
function chiri_content_width()
{
    $raw = chiri_config('general.contentWidth', '35rem');

    if (is_string($raw) && !is_numeric(trim($raw))) {
        // 允许直接写 "35rem" 这类带单位的值
        if (preg_match('/([0-9.]+)\s*rem/i', $raw, $m)) {
            $raw = $m[1];
        }
    }

    $clean = is_numeric($raw) ? (float) $raw : 35.0;
    if ($clean <= 0) {
        $clean = 35.0;
    }

    return min($clean, 50.0);
}

/** 内容区宽度对应的像素值，供前端定位逻辑使用 */
function chiri_content_width_px()
{
    return (int) round(chiri_content_width() * 16);
}

/** 是否启用目录 */
function chiri_has_toc()
{
    return (bool) chiri_config('post.toc', true);
}

/**
 * 是否显示评论区（主题级开关，关闭后文章页与独立页都不输出评论）
 *
 * 单篇文章自身的「允许评论」仍由 comments.php 内部控制。
 */
function chiri_has_comments()
{
    return (bool) chiri_config('post.comments', true);
}

/**
 * 获取站点根 URL（末尾带斜杠）
 *
 * 模板间通过 need() 引入时变量不共享作用域，统一走本函数而不是 header.php 的局部变量。
 */
function chiri_home_url()
{
    static $home = null;

    if ($home !== null) {
        return $home;
    }

    $home = '';

    if (class_exists('Helper')) {
        try {
            $options = Helper::options();
            if (isset($options->siteUrl)) {
                $home = rtrim((string) $options->siteUrl, '/') . '/';
            }
        } catch (Exception $e) {
            $home = '';
        } catch (Error $e) {
            $home = '';
        }
    }

    return $home;
}