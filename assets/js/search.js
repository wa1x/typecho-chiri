/**
 * 搜索弹层：点击图标在页面中央弹出搜索框，背景虚化
 *
 * 搜索结果的跳转不走内核的「POST s=xxx → 302 → /search/xxx/」：
 * 那一步依赖响应里的 Location 头，部分服务器/CDN 会丢弃它，浏览器会停在
 * 空白页。这里由 PHP 用内核路由直接拼出结果页地址（chiri_search_url），
 * 前端取关键词后直接跳转；拿不到该地址时才退回原生表单提交。
 */
(function () {
  'use strict';

  var overlay = document.getElementById('search-overlay');
  if (!overlay) {
    return;
  }

  var form = overlay.querySelector('#search-form');
  var input = overlay.querySelector('input[type="search"]');
  var lastFocused = null;

  function openSearch() {
    if (overlay.classList.contains('is-open')) {
      return;
    }
    lastFocused = document.activeElement;
    overlay.classList.remove('is-closing');
    overlay.classList.add('is-open');
    document.documentElement.classList.add('search-open');
    if (input) {
      // 弹层由 display 切换，样式在下一帧才生效，此刻聚焦会被浏览器忽略
      window.requestAnimationFrame(function () {
        input.focus();
        input.select();
      });
    }
  }

  function closeSearch() {
    if (!overlay.classList.contains('is-open')) {
      return;
    }
    overlay.classList.remove('is-open');
    overlay.classList.add('is-closing');
    document.documentElement.classList.remove('search-open');
    // 淡出结束后彻底移出渲染树，避免浏览器继续为全屏 backdrop-filter 付费
    window.setTimeout(function () {
      overlay.classList.remove('is-closing');
    }, 200);
    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  // 触发按钮可能出现在页头导航的不同位置，用事件委托统一处理
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-search-open]');
    if (trigger) {
      event.preventDefault();
      openSearch();
      return;
    }
    // 点击弹层外的遮罩区域关闭
    if (event.target === overlay) {
      closeSearch();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeSearch();
      return;
    }
    // Ctrl/⌘ + K 快速唤起
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      if (overlay.classList.contains('is-open')) {
        closeSearch();
      } else {
        openSearch();
      }
    }
  });

  // 弹层里的搜索框和归档页的内联搜索框都带 data-search-url，
  // 统一拦截：直接跳结果页，绕开内核 302 依赖 Location 头的问题
  var forms = document.querySelectorAll('form[data-search-url]');
  Array.prototype.forEach.call(forms, function (form) {
    form.addEventListener('submit', function (event) {
      var field = form.querySelector('input[name="s"]');
      var keyword = field ? field.value.trim() : '';

      if (!keyword) {
        // 空关键词不提交，留在原地
        event.preventDefault();
        if (field) {
          field.focus();
        }
        return;
      }

      var base = form.getAttribute('data-search-url') || '';

      // 拿不到结果页地址时退回内核的原生提交方式
      if (!base) {
        return;
      }

      event.preventDefault();
      window.location.href = base.replace('__keywords__', encodeURIComponent(keyword));
    });
  });
})();
