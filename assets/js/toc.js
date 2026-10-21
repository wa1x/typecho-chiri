/**
 * 文章目录
 *
 * 移植自 TableOfContents.astro：
 * 仅在页面宽度足够时固定定位到内容左侧，跟随滚动高亮当前章节。
 */
(function () {
  'use strict';

  var cfg = window.Chiri || {};

  if (!cfg.toc) return;

  var container = document.querySelector('.toc-container');
  if (!container) return;

  var state = {
    linkList: [],
    headingList: [],
    titleLink: null,
    headingMap: {},
    scrollFrame: null,
    activeLink: null,
    activeId: null,
    hasContent: false
  };

  function initElements() {
    state.linkList = Array.prototype.slice.call(document.querySelectorAll('.toc-link'));
    state.titleLink = document.querySelector('.toc-link.toc-title');

    // 排除文章标题（首个 h1）与其余无 id 的标题
    var headings = Array.prototype.slice.call(document.querySelectorAll('.content h1, .content h2, .content h3, .content h4'));
    state.headingList = headings.filter(function (h, index) {
      return !(index === 0 && h.tagName === 'H1') && h.id;
    });

    state.headingMap = {};
    state.linkList.forEach(function (link) {
      var href = link.getAttribute('href');
      if (href && href.charAt(0) === '#') {
        state.headingMap[href.substring(1)] = link;
      }
    });

    return true;
  }

  function checkContent() {
    var items = state.linkList.filter(function (link) {
      return !link.classList.contains('toc-title');
    });
    state.hasContent = items.length > 0;

    if (!state.hasContent) {
      container.style.display = 'none';
    }
  }

  // 定位由 CSS 媒体查询完成（components.css 的 1016px 断点），此处仅控制内容为空时的隐藏
  function adjustPosition() {
    if (!state.hasContent) {
      container.style.display = 'none';
    }
  }

  function handleClick(e) {
    var link = e.target.closest('.toc-link');
    if (!link) return;

    e.preventDefault();

    if (link.classList.contains('toc-title')) {
      window.scrollTo({ top: 0, behavior: 'smooth' });
      history.pushState(null, null, '#');
      return;
    }

    var href = link.getAttribute('href');
    if (!href || href.charAt(0) !== '#') return;

    var target = document.getElementById(href.substring(1));
    if (!target) return;

    var rect = target.getBoundingClientRect();
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    window.scrollTo({ top: rect.top + scrollTop - 96, behavior: 'smooth' });
    history.pushState(null, null, href);
  }

  function setActive(nextId) {
    var nextLink = nextId && state.headingMap[nextId] ? state.headingMap[nextId] : state.titleLink;

    if (state.activeId === nextId && state.activeLink === nextLink) return;

    if (state.activeLink) state.activeLink.classList.remove('active');
    if (nextLink) nextLink.classList.add('active');

    state.activeId = nextId;
    state.activeLink = nextLink;
  }

  function refreshActive() {
    if (!state.headingList.length) {
      setActive(null);
      return;
    }

    var offset = 128;

    if (window.pageYOffset < 16 || state.headingList[0].getBoundingClientRect().top > offset) {
      setActive(null);
      return;
    }

    for (var i = state.headingList.length - 1; i >= 0; i--) {
      if (state.headingList[i].getBoundingClientRect().top <= offset) {
        setActive(state.headingList[i].id);
        break;
      }
    }
  }

  function scheduleRefresh() {
    if (state.scrollFrame) return;
    state.scrollFrame = window.requestAnimationFrame(function () {
      state.scrollFrame = null;
      refreshActive();
    });
  }

  function handleResize() {
    window.requestAnimationFrame(function () {
      adjustPosition();
      refreshActive();
    });
  }

  function init() {
    initElements();
    checkContent();
    adjustPosition();
    refreshActive();
  }

  container.addEventListener('click', handleClick);
  window.addEventListener('scroll', scheduleRefresh, { passive: true });
  window.addEventListener('resize', handleResize);

  init();
})();