/**
 * 页面转场（复刻原版 astro-chiri 的 ClientRouter 动画）
 *
 * 策略：
 *   ① 浏览器支持原生跨文档 View Transition → 完全交给浏览器，本脚本不做 JS 动画；
 *   ② 完全没有该能力（Firefox、老 Safari/Chromium）→ 才走 JS 降级：预隐藏 + 淡入，
 *      并拦截站内链接点击做一次淡出。
 *
 * 必须守住的约束：
 *   1) JS 侧动画只动 opacity：#transition-wrapper 内有 position:fixed 的侧边目录
 *      与返回按钮，给共同祖先加 filter / transform 会改变它们的包含块导致跑位；
 *   2) 原生转场开始前不做预隐藏（否则会拍到空白快照），由 pagereveal 保证。
 */
(function () {
  'use strict';

  var PENDING = 'chiri-pt-pending';
  var ENTER = 'chiri-pt-enter';
  var LEAVE = 'chiri-pt-leave';
  var VT_FLAG = 'chiri-vt';

  var state = window.__chiriVtState || null;

  // 判定只认浏览器能力：sessionStorage 标记在直接打开的页面上必为空，会误判
  var nativeVt = supportsViewTransition() || !!(state && state.native);

  // pagereveal 在新页快照之前触发，此处撤销降级路径的痕迹不会与原生转场叠加
  window.addEventListener('pagereveal', function (event) {
    if (!event.viewTransition) {
      return;
    }
    nativeVt = true;
    if (state) {
      state.native = true;
    }
    cancelFallback();
  });

  // 告知下一页「本次导航有原生转场」，让 head 脚本提前决定是否预隐藏
  window.addEventListener('pageswap', function (event) {
    if (!event.viewTransition) {
      return;
    }

    // 旧页快照前把滚动位置瞬时归零，避免翻页时新旧页错位重影；
    // 后退（traverse）不动，浏览器会恢复滚动位置
    var navType = event.activation ? event.activation.navigationType : '';
    if (navType !== 'traverse') {
      snapToTop();
    }

    try {
      window.sessionStorage.setItem(VT_FLAG, '1');
    } catch (e) {
      /* 存储不可用时：新页面由 pagereveal 兜底 */
    }
  });

  if (reducedMotion()) {
    cancelFallback();
    return;
  }

  window.addEventListener('pagehide', cancelFallback);

  if (nativeVt) {
    // 原生路径交给浏览器；bfcache 恢复（后退）时原生不跑转场，补一次入场
    window.addEventListener('pageshow', function (event) {
      if (event.persisted) {
        playEnter();
      }
    });
    return;
  }

  // 降级路径
  playEnter();

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      playEnter();
    }
  });

  // 只有完全没有 View Transition 能力的浏览器才接管点击，否则会和浏览器自己的转场叠加
  if (!supportsViewTransition()) {
    document.addEventListener('click', onClick);
  }

  function supportsViewTransition() {
    return !!(window.CSS && window.CSS.supports && window.CSS.supports('view-transition-name: none'));
  }

  /* 降级路径：拦截站内链接，先淡出再跳转 */
  function onClick(event) {
    var link = resolveLink(event);
    if (!link) {
      return;
    }

    event.preventDefault();
    playLeave(link.href, function () {
      window.location.href = link.href;
    });
  }

  function resolveLink(event) {
    if (event.defaultPrevented || event.button !== 0) {
      return null;
    }
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return null;
    }

    var target = event.target;
    var link = target && target.closest ? target.closest('a[href]') : null;
    if (!link) {
      return null;
    }
    if (link.target && link.target !== '_self') {
      return null;
    }
    if (link.hasAttribute('download')) {
      return null;
    }

    var href = link.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#') {
      return null;
    }
    if (/^(mailto:|tel:|javascript:|data:)/i.test(href)) {
      return null;
    }

    var url;
    try {
      url = new URL(link.href, window.location.href);
    } catch (e) {
      return null;
    }

    if (url.origin !== window.location.origin) {
      return null;
    }
    // 同页锚点跳转不动画
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
      return null;
    }

    return link;
  }

  /* 瞬时滚到顶部（html 有 scroll-behavior: smooth，先压成 auto 避免被动画化） */
  function snapToTop() {
    var root = document.documentElement;
    var prev = root.style.scrollBehavior;

    root.style.scrollBehavior = 'auto';
    window.scrollTo(0, 0);
    root.style.scrollBehavior = prev;
  }

  /* 动画（只动 opacity） */
  function playEnter() {
    var wrapper = document.getElementById('transition-wrapper');

    // 同一帧先挂淡入类、再摘 pending，不会闪一下全亮
    if (wrapper) {
      wrapper.classList.remove(LEAVE);
      wrapper.classList.add(ENTER);
    }

    releasePending();

    window.setTimeout(cancelFallback, 1000);
  }

  function playLeave(href, go) {
    var wrapper = document.getElementById('transition-wrapper');
    var jumped = false;

    function jump() {
      if (jumped) {
        return;
      }
      jumped = true;
      go();
    }

    if (!wrapper) {
      jump();
      return;
    }

    wrapper.classList.remove(ENTER);
    wrapper.classList.add(LEAVE);

    var done = function (e) {
      if (e && (e.target !== wrapper || e.animationName !== 'chiri-pt-fade-out')) {
        return;
      }
      wrapper.removeEventListener('animationend', done);
      jump();
    };

    wrapper.addEventListener('animationend', done);
    // 兜底：动画事件丢失时也要跳转（略长于 0.25s 的出场动画）
    window.setTimeout(jump, 400);
  }

  /* 收尾：清掉降级路径留下的状态（原生转场接管时也会调用） */
  function cancelFallback() {
    var wrapper = document.getElementById('transition-wrapper');
    if (wrapper) {
      wrapper.classList.remove(ENTER);
      wrapper.classList.remove(LEAVE);
    }
    releasePending();
  }

  function releasePending() {
    document.documentElement.classList.remove(PENDING);
  }

  function reducedMotion() {
    return !!(
      window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
  }
})();
