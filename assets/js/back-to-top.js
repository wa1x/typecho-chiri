/**
 * 返回顶部按钮（仅文章页，post.php 输出）
 *
 * 滚动超过约六成视口高度后淡入显示，点击回到顶部；
 * 页面本身不足一屏时不初始化。
 * 点击时按 prefers-reduced-motion 决定平滑滚动还是直接跳转
 * （「减少动态」偏好可能在会话中途改变，因此在点击时实时读取）。
 */
(function () {
  'use strict';

  function init() {
    var btn = document.getElementById('back-to-top');
    if (!btn) return;

    // 页面不足一屏时没有「顶部」可回，保持隐藏即可
    if (document.documentElement.scrollHeight <= window.innerHeight + 2) return;

    // 阈值：滚过约六成视口（至少 320px），避免短距离滚动时按钮闪烁
    function threshold() {
      return Math.max(320, Math.round(window.innerHeight * 0.6));
    }

    var ticking = false;

    function update() {
      btn.classList.toggle('is-visible', window.scrollY >= threshold());
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        update();
        ticking = false;
      });
    }

    btn.addEventListener('click', function () {
      var reduceMotion = Boolean(
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
      );
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
