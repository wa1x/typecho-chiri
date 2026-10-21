/**
 * 返回按钮定位
 *
 * 移植自 BackButton.astro：窗口足够宽时将「index」返回按钮固定到内容左侧，
 * 窄屏时回归文档流，避免与正文重叠。
 */
(function () {
  'use strict';

  var cfg = window.Chiri || {};

  function adjust() {
    var btn = document.querySelector('.back-button');
    if (!btn) return;

    if (!cfg.centered) {
      btn.classList.remove('fixed-position');
      btn.style.left = '';
      return;
    }

    var pageWidth = window.innerWidth;
    var contentWidth = cfg.contentWidth || 560;
    var margin = (pageWidth - contentWidth) / 2;
    var baseMinSpace = 176;
    var minSpace = cfg.toc ? baseMinSpace + 52 : baseMinSpace + 12;

    if (margin >= minSpace) {
      btn.classList.add('fixed-position');
      var left = margin - baseMinSpace;
      if (cfg.toc) left -= 40;
      btn.style.left = left + 'px';
    } else {
      btn.classList.remove('fixed-position');
      btn.style.left = '';
    }
  }

  var ticking = false;
  function onResize() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(function () {
      adjust();
      ticking = false;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', adjust);
  } else {
    adjust();
  }

  window.addEventListener('resize', onResize);
})();