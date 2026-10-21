/**
 * 图片查看器
 *
 * 移植自 ImageViewer.astro：点击正文图片放大查看，支持 Esc / 点击遮罩关闭。
 */
(function () {
  'use strict';

  var cfg = window.Chiri || {};

  if (!cfg.imageViewer) return;

  function init() {
    var content = document.querySelector('.prose .content, .prose');
    if (!content) return;

    document.body.setAttribute('data-image-viewer', 'on');

    var viewer = document.createElement('div');
    viewer.className = 'image-viewer';
    viewer.setAttribute('role', 'dialog');
    viewer.setAttribute('aria-modal', 'true');
    viewer.innerHTML =
      '<button class="image-viewer__close" type="button" aria-label="Close">&times;</button><img alt="" />';
    document.body.appendChild(viewer);

    var viewerImg = viewer.querySelector('img');
    var closeBtn = viewer.querySelector('.image-viewer__close');
    var lastFocused = null;

    function open(src, alt) {
      viewerImg.src = src;
      viewerImg.alt = alt || '';
      lastFocused = document.activeElement;

      // 显隐由 .active 类驱动（与原版 CSS 的 visibility 过渡机制一致）
      viewer.classList.add('active');
      document.body.classList.add('image-viewer-open');
      closeBtn.focus();
    }

    function close() {
      viewer.classList.remove('active');
      document.body.classList.remove('image-viewer-open');

      window.setTimeout(function () {
        viewerImg.src = '';
      }, 200);

      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
    }

    content.addEventListener('click', function (e) {
      var img = e.target;
      if (!img || img.tagName !== 'IMG') return;

      // 仅处理标记为可预览的图片（与原版 data-preview 约定一致）
      if (img.getAttribute('data-preview') !== 'true') return;

      var full = img.getAttribute('data-full') || img.currentSrc || img.src;
      if (!full) return;

      e.preventDefault();
      open(full, img.getAttribute('alt'));
    });

    closeBtn.addEventListener('click', close);

    viewer.addEventListener('click', function (e) {
      if (e.target === viewer) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && viewer.classList.contains('active')) {
        close();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();