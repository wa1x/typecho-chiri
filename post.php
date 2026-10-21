<?php
/**
 * 文章详情页（对应源主题 PostLayout.astro）
 *
 * 与原版一致：文章页无顶部站点导航、无上一篇/下一篇导航。
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

// $this->content 已是转换后的 HTML，后处理只做装饰，不再走插件链
$chiri_postContent = (string) $this->content;

if (chiri_config('post.copyCode')) {
    $chiri_postContent = chiri_decorate_code_blocks($chiri_postContent);
}

$chiri_postContent = chiri_decorate_images($chiri_postContent);

$chiri_toc = array();
if (chiri_has_toc()) {
    $chiri_tocResult = chiri_extract_toc($chiri_postContent, (int) chiri_config('post.tocDepth', 3));
    $chiri_toc        = $chiri_tocResult['list'];
    $chiri_postContent = $chiri_tocResult['html'];
}

$chiri_reading = chiri_config('post.readingTime') ? chiri_reading_time($chiri_postContent) : null;

$chiri_showToc = chiri_has_toc() && !empty($chiri_toc);

/**
 * 后台编辑入口：仅对「能真正编辑本文」的 logged-in 用户展示，权限口径与核心一致：
 * Edit::execute() 要求 contributor 及以上（var/Widget/Contents/Post/Edit.php），
 * PrepareEditTrait::allow('edit') 要求 editor 及以上或本文作者。低权组不渲染按钮，避免死链。
 */
$chiri_editUrl = '';
if ($this->user->hasLogin()
    && $this->user->pass('contributor', true)
    && ($this->user->pass('editor', true) || (int) $this->user->uid === (int) $this->authorId)) {
    $chiri_editUrl = Typecho\Common::url('write-post.php?cid=' . (int) $this->cid, $this->options->adminUrl);
}
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
        <ul class="toc-list" id="toc-list">
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
      <div class="date">
        <?php echo chiri_date_html($this->date); ?>
        <?php if ($chiri_reading): ?>
          <span class="reading-time">
            <span class="separator">·</span>
            <?php echo htmlspecialchars($chiri_reading['text'], ENT_QUOTES, 'UTF-8'); ?>
          </span>
        <?php endif; ?>
        <?php if (chiri_config('post.showCategory') && !empty($this->categories)): ?>
          <span class="post-category">
            <span class="separator">·</span>
            <?php
            // 手动循环以统一 .separator 节奏并转义名称（核心 category() 输出未转义且裸文本分隔）
            foreach ($this->categories as $chiri_catIndex => $chiri_cat): ?>
              <?php if ($chiri_catIndex > 0): ?><span class="separator">·</span><?php endif; ?><a href="<?php echo htmlspecialchars($chiri_cat['permalink'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($chiri_cat['name'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
          </span>
        <?php endif; ?>
        <?php if ($chiri_editUrl !== ''): ?>
          <?php // 极简编辑入口：隐式文本风格，与日期 / 阅读时长同一行节奏（样式见 post.css） ?>
          <span class="edit-entry">
            <span class="separator">·</span>
            <a href="<?php echo htmlspecialchars($chiri_editUrl, ENT_QUOTES, 'UTF-8'); ?>"
               title="<?php chiri_e('在后台编辑这篇文章', 'chiri'); ?>"><?php chiri_e('编辑', 'chiri'); ?></a>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <article class="content">
      <?php echo $chiri_postContent; ?>
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

<?php /* 仅文章页：右下角返回顶部按钮（fixed 定位，back-to-top.js 滚动后显示；样式在 post.css） */
?>
<button type="button" id="back-to-top" class="back-to-top"
        aria-label="<?php chiri_e('回到顶部', 'chiri'); ?>"
        title="<?php chiri_e('回到顶部', 'chiri'); ?>">
  <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8 12.5V3.5M8 3.5L4.5 7M8 3.5L11.5 7"
          stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"></path>
  </svg>
</button>
<script src="<?php $this->options->themeUrl('assets/js/back-to-top.js'); ?>"></script>

<?php $this->need('footer.php'); ?>
