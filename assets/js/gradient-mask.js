/**
 * 顶部渐变遮罩
 *
 * 移植自 GradientMask.astro：滚动超过阈值后淡入顶部遮罩，
 * 避免正文滚到标题下方时文字与背景混在一起。
 * 移动端 Safari 下禁用（其 backdrop-filter 表现不稳定）。
 */
(function () {
  'use strict';

  function isMobileSafari() {
    var ua = navigator.userAgent;
    var platform = navigator.platform;

    var isIOS = /iP(ad|hone|od)/.test(ua) || (platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (!isIOS) return false;

    var isSafari = /Safari\//.test(ua) && !/(CriOS|FxiOS|EdgiOS|OPiOS|DuckDuckGo)/.test(ua);
    if (!isSafari) return false;

    var isMobileLike =
      (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) ||
      (window.matchMedia && window.matchMedia('(max-width: 768px)').matches);

    return Boolean(isMobileLike);
  }

  function init() {
    var mask = document.querySelector('.gradient-mask');
    if (!mask) return;

    if (isMobileSafari()) {
      mask.style.display = 'none';
      return;
    }

    var threshold = 64;
    var ticking = false;

    function update() {
      mask.style.opacity = window.scrollY >= threshold ? '1' : '0';
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        update();
        ticking = false;
      });
    }

    update();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();