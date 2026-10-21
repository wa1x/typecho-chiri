<?php
/**
 * 404 页面
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');
?>
<div class="page-content">
<?php chiri_render_gradient_mask(); ?>
<?php chiri_render_site_header(isset($chiri_siteName) ? $chiri_siteName : chiri_config('site.title')); ?>
<main>
  <?php /* .back-button 的样式挂在 .prose 下，与 post.php / page.php 保持一致 */ ?>
  <div class="prose">
    <a href="<?php $this->options->siteUrl(); ?>" class="back-button">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2.5 6.5H9.5C11.1569 6.5 12.5 7.84315 12.5 9.5V9.5C12.5 11.1569 11.1569 12.5 9.5 12.5H7.5M2.5 6.5L5.5 9.5M2.5 6.5L5.5 3.5"
              stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>
      <?php chiri_e('index', 'chiri'); ?>
    </a>
  </div>
  <div class="page-title">
    <h1><?php chiri_e('404', 'chiri'); ?></h1>
    <p class="page-desc"><?php chiri_e('这个页面不存在。', 'chiri'); ?></p>
  </div>
</main>
<?php $this->need('footer.php'); ?>
