<?php
/**
 * 公共头部：head 内容、明暗主题防闪烁脚本、顶部导航与渐变遮罩
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

// 兼容 PHP 5.6：不使用 ?? 运算符
$chiri_siteName = chiri_config('site.title');
$chiri_author   = chiri_config('site.author');
$chiri_lang     = chiri_config('site.language', 'zh-CN');
$chiri_width    = chiri_content_width();
$chiri_centered = chiri_config('general.centeredLayout') ? 'true' : 'false';
$chiri_favicon  = chiri_config('site.favicon');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($chiri_lang, ENT_QUOTES, 'UTF-8'); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo chiri_page_title($this, $chiri_siteName); ?></title>

<?php
// 内核仅在 single 页输出 canonical，这里补齐其余页面
if (!(isset($this) && method_exists($this, 'is') && $this->is('single'))):
?>
<link rel="canonical" href="<?php echo htmlspecialchars(chiri_current_url(), ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
<?php if ($chiri_favicon): ?>
<link rel="icon" href="<?php echo htmlspecialchars($chiri_favicon, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>

<link rel="preload" href="<?php $this->options->themeUrl('assets/fonts/Inter.woff2'); ?>" as="font" type="font/woff2" crossorigin>
<script src="<?php $this->options->themeUrl('assets/js/theme-head.js'); ?>"></script>
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/global.css'); ?>">
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/layout.css'); ?>">
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/components.css'); ?>">
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/post.css'); ?>">
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/code.css'); ?>">
<?php if (chiri_config('general.showSearch')): ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/search.css'); ?>">
<?php endif; ?>
<?php if (chiri_config('post.katex')): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css" media="print" onload="this.media='all'" crossorigin="anonymous">
<?php endif; ?>
<meta name="author" content="<?php echo htmlspecialchars($chiri_author, ENT_QUOTES, 'UTF-8'); ?>">

<?php
// description / keywords / og:* / canonical 等一律交给内核：它会按页面类型输出
// 正确的值（文章页取标签与摘要、列表页取站点设置），主题再输出一份只会重复
if (method_exists($this, 'header')) {
    $this->header();
}
?>
</head>
<body data-centered="<?php echo $chiri_centered; ?>"
      style="max-width: <?php echo $chiri_width; ?>rem; --content-width: <?php echo $chiri_width; ?>rem;">

<div class="layout-wrapper" id="transition-wrapper">
<?php
// 导航由各模板按需调用（文章页只有返回按钮）；
// gradient-mask 需落在容器第一个子元素上，首页/归档放 page-content，文章页放 prose
if (!function_exists('chiri_render_site_header')) {
function chiri_render_site_header($siteName)
{
    ?>
    <div>
      <header>
        <nav>
          <a href="<?php echo chiri_home_url(); ?>"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></a>
          <div class="nav-actions">
            <?php chiri_render_search_trigger(); ?>
            <?php if (chiri_config('general.themeToggle')): ?>
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
              <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M6 0.5C9.03757 0.5 11.5 2.96243 11.5 6C11.5 9.03757 9.03757 11.5 6 11.5C2.96243 11.5 0.5 9.03757 0.5 6C0.5 2.96243 2.96243 0.5 6 0.5ZM6 1.84961C3.70802 1.84961 1.84961 3.70802 1.84961 6C1.84961 8.29198 3.70802 10.1504 6 10.1504V1.84961Z" fill="currentColor"></path>
              </svg>
            </button>
            <?php endif; ?>
          </div>
        </nav>
      </header>
    </div>
    <?php
}
}

/**
 * 搜索按钮：极简放大镜，与主题切换按钮同一套配色与尺寸
 *
 * 列表页放在页头导航右侧，文章页/独立页放在正文顶部（与返回按钮同一行）。
 */
if (!function_exists('chiri_render_search_trigger')) {
function chiri_render_search_trigger()
{
    if (!chiri_config('general.showSearch')) {
        return;
    }
    ?>
    <button type="button" class="search-trigger" data-search-open
            aria-label="<?php chiri_e('搜索', 'chiri'); ?>"
            aria-haspopup="dialog"
            title="<?php chiri_e('搜索', 'chiri'); ?>">
      <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="5.75" cy="5.75" r="3.6" stroke="currentColor" stroke-width="1.1"></circle>
        <path d="M8.4 8.4L10.8 10.8" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"></path>
      </svg>
    </button>
    <?php
}
}

if (!function_exists('chiri_render_gradient_mask')) {
function chiri_render_gradient_mask()
{
    ?>
    <div class="gradient-mask" aria-hidden="true">
      <div class="gradient-mask__blur"></div>
      <div class="gradient-mask__background"></div>
    </div>
    <?php
}
}