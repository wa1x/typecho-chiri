<?php
/**
 * 归档页：分类 / 标签 / 日期归档与搜索结果
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$this->need('header.php');

$chiri_dateOnRight = chiri_config('date.dateOnRight');
$chiri_dividerHtml = chiri_config('general.postListDottedDivider')
    ? '<div class="dotted-divider"></div>'
    : '<div class="divider"></div>';

// archiveTitle() 直接 echo，用输出缓冲捕获为字符串
$chiri_archiveTitle = '';
if (method_exists($this, 'archiveTitle')) {
    ob_start();
    try {
        $this->archiveTitle(array(
            'category' => chiri_t('分类 %s 下的文章', 'chiri'),
            'search'   => chiri_t('包含关键字 %s 的文章', 'chiri'),
            'tag'      => chiri_t('标签 %s 下的文章', 'chiri'),
            'author'   => chiri_t('%s 发布的文章', 'chiri'),
        ), '', '');
        $chiri_archiveTitle = trim((string) ob_get_clean());
    } catch (Exception $e) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        $chiri_archiveTitle = '';
    } catch (Error $e) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        $chiri_archiveTitle = '';
    }
}
?>
<div class="page-content">
<?php chiri_render_gradient_mask(); ?>
<?php chiri_render_site_header(isset($chiri_siteName) ? $chiri_siteName : chiri_config('site.title')); ?>
<main>
  <div class="page-title">
    <h1><?php echo htmlspecialchars($chiri_archiveTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
    <?php if ($this->getDescription()): ?>
      <p class="page-desc"><?php echo htmlspecialchars($this->getDescription(), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
  </div>

  <?php
  $chiri_isSearch = false;
  if (method_exists($this, 'is')) {
      try {
          $chiri_isSearch = (bool) $this->is('search');
      } catch (Exception $e) {
          $chiri_isSearch = false;
      } catch (Error $e) {
          $chiri_isSearch = false;
      }
  }
  ?>
  <?php if ($chiri_isSearch): ?>
  <form class="search-form" method="post" action="<?php $this->options->siteUrl(); ?>" role="search"
        data-search-url="<?php echo htmlspecialchars(chiri_search_url('__keywords__'), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="search" name="s" placeholder="<?php chiri_e('搜索文章…', 'chiri'); ?>"
           value="<?php echo htmlspecialchars($this->getKeywords(), ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit"><?php chiri_e('搜索', 'chiri'); ?></button>
  </form>
  <?php endif; ?>

  <?php if ($this->have()): ?>
  <ul class="post-list">
    <?php while ($this->next()): ?>
      <li>
        <a href="<?php $this->permalink(); ?>">
          <div class="post-item<?php echo $chiri_dateOnRight ? '' : ' date-left'; ?>">
            <?php if (!$chiri_dateOnRight): ?>
              <p class="list-date font-features date-left"><?php echo chiri_date_html($this->date); ?></p>
              <?php echo $chiri_dividerHtml; ?>
            <?php endif; ?>
            <p class="item-title"><?php $this->title(); ?></p>
            <?php if ($chiri_dateOnRight): ?>
              <?php echo $chiri_dividerHtml; ?>
              <p class="list-date font-features"><?php echo chiri_date_html($this->date); ?></p>
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
    <div class="empty-state"><?php chiri_e('没有找到相关内容。', 'chiri'); ?></div>
  <?php endif; ?>
</main>
<?php $this->need('footer.php'); ?>