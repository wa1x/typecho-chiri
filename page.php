<?php
/**
 * 独立页面
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

$chiri_pageContent = $this->content;

if (chiri_config('post.copyCode')) {
    $chiri_pageContent = chiri_decorate_code_blocks($chiri_pageContent);
}

$chiri_pageContent = chiri_decorate_images($chiri_pageContent);

$chiri_toc = array();
if (chiri_has_toc()) {
    $chiri_tocResult  = chiri_extract_toc($chiri_pageContent, (int) chiri_config('post.tocDepth', 3));
    $chiri_toc        = $chiri_tocResult['list'];
    $chiri_pageContent = $chiri_tocResult['html'];
}

$chiri_showToc = chiri_has_toc() && !empty($chiri_toc);
?>
<div class="post-container">
<main>
  <div class="prose">
    <?php chiri_render_gradient_mask(); ?>

    <a href="<?php $this->options->siteUrl(); ?>" class="back-button">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2.5 6.5H9.5C11.1569 6.5 12.5 7.84315 12.5 9.5V9.5C12.5 11.1569 11.1569 12.5 9.5 12.5H7.5M2.5 6.5L5.5 9.5M2.5 6.5L5.5 3.5"
              stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"></path>
      </svg>
      <?php chiri_e('index', 'chiri'); ?>
    </a>

    <?php if ($chiri_showToc): ?>
    <div class="toc-container" id="toc">
      <nav class="toc-nav">
        <ul class="toc-list">
          <li class="toc-item toc-level-0">
            <a href="#" class="toc-link toc-title" title="Back to top" data-text="Back to top">&nbsp;Back to top&nbsp;</a>
          </li>
          <?php foreach ($chiri_toc as $chiri_node): ?>
            <li class="toc-item toc-level-<?php echo (int) $chiri_node['level']; ?>">
              <a href="#<?php echo htmlspecialchars($chiri_node['id'], ENT_QUOTES, 'UTF-8'); ?>"
                 class="toc-link"
                 title="<?php echo htmlspecialchars($chiri_node['text'], ENT_QUOTES, 'UTF-8'); ?>"
                 data-text="<?php echo htmlspecialchars($chiri_node['text'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($chiri_node['text'], ENT_QUOTES, 'UTF-8'); ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
    <?php endif; ?>

    <div class="title">
      <h1><?php $this->title(); ?></h1>
      <?php
      $chiri_pageDate = null;
      try {
          if (isset($this->date) && $this->date) {
              $chiri_pageDate = $this->date;
          }
      } catch (Exception $e) {
          $chiri_pageDate = null;
      } catch (Error $e) {
          $chiri_pageDate = null;
      }
      ?>
      <?php if ($chiri_pageDate): ?>
        <div class="date"><?php echo chiri_date_html($chiri_pageDate); ?></div>
      <?php endif; ?>
    </div>

    <article class="content">
      <?php echo $chiri_pageContent; ?>
    </article>

    <?php if (!chiri_has_comments()): ?>
    <?php // 提示须留在 .prose 内：main 是 flex:1 纵向容器，放外面会被拉伸到视口底部 ?>
    <p class="comments-disabled"><?php chiri_e('评论功能已关闭', 'chiri'); ?></p>
    <?php endif; ?>
  </div>

  <?php if (chiri_has_comments()): ?>
  <?php // 同理，评论区留在 main 内部，避免正文与它之间出现拉伸出的大片空白 ?>
  <section class="comments">
    <?php $this->need('comments.php'); ?>
  </section>
  <?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>