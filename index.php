<?php
/**
 * Chiri —— 移植自 astro-chiri 的 Typecho 单栏写作主题
 *
 * @package Chiri
 * @author 歪叉
 * @version 1.1.0
 * @link https://waix.cc
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
<?php
$chiri_dateOnRight = chiri_config('date.dateOnRight');
$chiri_dividerHtml = chiri_config('general.postListDottedDivider')
    ? '<div class="dotted-divider"></div>'
    : '<div class="divider"></div>';
$chiri_showReading = chiri_config('general.showReadingTime');

$chiri_aboutHtml = '';
if (chiri_config('general.showAbout')) {
    $chiri_aboutRaw = chiri_config('general.aboutContent', '');
    if (is_string($chiri_aboutRaw) && trim($chiri_aboutRaw) !== '') {
        $chiri_aboutHtml = $chiri_aboutRaw;
    }
}

if (trim($chiri_aboutHtml) !== '') {
    echo '<div class="about prose">', $chiri_aboutHtml, '</div>';
}
?>
<?php if ($this->have()): ?>
<ul class="post-list">
  <?php while ($this->next()): ?>
    <li>
      <a href="<?php $this->permalink(); ?>">
        <div class="post-item<?php echo $chiri_dateOnRight ? '' : ' date-left'; ?>">
          <?php if (!$chiri_dateOnRight): ?>
            <p class="list-date font-features date-left"><?php echo chiri_date_html($this->date); ?></p>
            <?php if ($chiri_showReading): ?>
              <span class="meta-sep">&middot;</span>
              <span class="list-reading"><?php echo htmlspecialchars(chiri_reading_time($this->content)['text'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php echo $chiri_dividerHtml; ?>
          <?php endif; ?>
          <p class="item-title"><?php $this->title(); ?></p>
          <?php if ($chiri_dateOnRight): ?>
            <?php echo $chiri_dividerHtml; ?>
            <span class="list-meta">
              <?php if ($chiri_showReading): ?>
                <span class="list-reading"><?php echo htmlspecialchars(chiri_reading_time($this->content)['text'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="meta-sep">&middot;</span>
              <?php endif; ?>
              <span class="list-date font-features"><?php echo chiri_date_html($this->date); ?></span>
            </span>
          <?php endif; ?>
        </div>
      </a>
    </li>
  <?php endwhile; ?>
</ul>
<div class="placeholder"></div>

<nav class="pagination">
  <?php $this->pageNav(chiri_t('上一页', 'chiri'), chiri_t('下一页', 'chiri')); ?>
</nav>

<?php else: ?>
  <div class="empty-state"><?php chiri_e('还没有任何文章。', 'chiri'); ?></div>
<?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>
