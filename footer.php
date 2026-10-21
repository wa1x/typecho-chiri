<?php
/**
 * 公共页脚与页尾脚本
 *
 * @package Chiri
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$chiri_author = chiri_config('site.author');
$chiri_year   = date('Y');
$chiri_icp    = trim((string) chiri_config('site.icp'));
$chiri_hasFooter = (bool) chiri_config('general.footer');
?>
    </div>

<?php if ($chiri_hasFooter): ?>
<footer>
  <div class="footer-content">
    <?php // 左列版权 + 备案号（分隔符由 CSS 伪元素生成），右列 Powered by；关闭页脚时整块不渲染 ?>
    <div class="copyright">
      <span class="date">&copy; <?php echo $chiri_year; ?></span>
      <?php echo htmlspecialchars($chiri_author, ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($chiri_icp !== ''): ?>
      <span class="site-beian"><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener nofollow"><?php echo htmlspecialchars($chiri_icp, ENT_QUOTES, 'UTF-8'); ?></a></span>
      <?php endif; ?>
    </div>

    <div class="powered-by">
      Powered by <a href="https://typecho.org">Typecho</a> + <a href="https://github.com/wa1x/typecho-chiri">Chiri</a>
    </div>
  </div>
</footer>
<?php endif; ?>
</div>

<?php if (chiri_config('general.showSearch')): ?>
<?php
// 结果页地址模板（%s 为关键词占位符），前端取关键词后直接跳转，
// 绕开内核 302 依赖 Location 头的问题
$chiri_searchBase = chiri_search_url('__keywords__');
?>
<div class="search-overlay" id="search-overlay">
  <div class="search-panel" role="dialog" aria-modal="true" aria-label="<?php chiri_e('搜索', 'chiri'); ?>">
    <form class="search-form" id="search-form" method="post" action="<?php $this->options->siteUrl(); ?>"
          data-search-url="<?php echo htmlspecialchars($chiri_searchBase, ENT_QUOTES, 'UTF-8'); ?>" role="search">
      <input type="search" name="s" placeholder="<?php chiri_e('搜索文章…', 'chiri'); ?>" autocomplete="off">
      <button type="submit"><?php chiri_e('搜索', 'chiri'); ?></button>
    </form>
    <p class="search-panel__hint"><?php chiri_e('按 Esc 关闭', 'chiri'); ?></p>
  </div>
</div>
<?php endif; ?>

<script>
  window.Chiri = {
    contentWidth: <?php echo chiri_content_width_px(); ?>,
    centered: <?php echo chiri_config('general.centeredLayout') ? 'true' : 'false'; ?>,
    toc: <?php echo chiri_has_toc() ? 'true' : 'false'; ?>,
    copyCode: <?php echo chiri_config('post.copyCode') ? 'true' : 'false'; ?>,
    imageViewer: <?php echo chiri_config('post.imageViewer') ? 'true' : 'false'; ?>
  };
</script>
<script src="<?php $this->options->themeUrl('assets/js/page-transition.js'); ?>"></script>
<script src="<?php $this->options->themeUrl('assets/js/gradient-mask.js'); ?>"></script>
<?php if (chiri_config('general.showSearch')): ?>
<script src="<?php $this->options->themeUrl('assets/js/search.js'); ?>"></script>
<?php endif; ?>
<?php /* 返回按钮定位由 CSS 完成（layout.css 的 1016px 媒体查询），不加载独立脚本 */ ?>
<?php if (chiri_has_toc()): ?>
<script src="<?php $this->options->themeUrl('assets/js/toc.js'); ?>"></script>
<?php endif; ?>
<?php if (chiri_config('post.copyCode')): ?>
<script src="<?php $this->options->themeUrl('assets/js/copy-code.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.9.0/build/highlight.min.js"></script>
<script>
  (function () {
    function chiriHighlight() {
      if (typeof hljs === 'undefined') return;
      var blocks = document.querySelectorAll('.prose pre code');
      for (var i = 0; i < blocks.length; i++) {
        if (!blocks[i].getAttribute('data-highlighted')) {
          try { hljs.highlightElement(blocks[i]); } catch (e) { /* 忽略单块高亮失败 */ }
        }
      }
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', chiriHighlight);
    } else {
      chiriHighlight();
    }
  })();
</script>
<?php endif; ?>
<?php if (chiri_config('post.imageViewer')): ?>
<script src="<?php $this->options->themeUrl('assets/js/image-viewer.js'); ?>"></script>
<?php endif; ?>
<?php if (chiri_config('post.katex')): ?>
<script defer src="<?php $this->options->themeUrl('assets/js/katex-loader.js'); ?>"></script>
<?php endif; ?>
<?php
if (method_exists($this, 'footer')) {
    $this->footer();
}
?>
</body>
</html>
