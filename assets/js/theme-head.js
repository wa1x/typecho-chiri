/**
 * Chiri 主题首屏防闪烁脚本
 *
 * 本文件需在 <head> 中同步加载（不使用 defer），
 * 在样式表应用前判定主题，避免深色模式下的白屏闪烁。
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'chiri-theme';

  function readStored() {
    try {
      return window.localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      return null;
    }
  }

  function writeStored(theme) {
    try {
      if (theme === 'system' || !theme) {
        window.localStorage.removeItem(STORAGE_KEY);
      } else {
        window.localStorage.setItem(STORAGE_KEY, theme);
      }
    } catch (e) {
      /* 隐私模式下降级为仅本次会话生效 */
    }
  }

  function systemTheme() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function effectiveTheme() {
    var stored = readStored();
    return stored === 'light' || stored === 'dark' ? stored : systemTheme();
  }

  function applyTheme(theme) {
    var root = document.documentElement;
    root.classList.remove('light', 'dark');
    root.classList.add(theme);
  }

  applyTheme(effectiveTheme());

  var systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

  function onSystemChange(e) {
    // 用户已手动选择时不跟随系统
    if (readStored() !== null) return;
    applyTheme(e.matches ? 'dark' : 'light');
  }

  if (systemThemeQuery) {
    if (typeof systemThemeQuery.addEventListener === 'function') {
      systemThemeQuery.addEventListener('change', onSystemChange);
    } else if (typeof systemThemeQuery.addListener === 'function') {
      systemThemeQuery.addListener(onSystemChange);
    }
  }

  window.ThemeManager = {
    STORAGE_KEY: STORAGE_KEY,
    initialized: true,
    getSystemTheme: systemTheme,
    getStoredTheme: readStored,
    setStoredTheme: writeStored,
    getEffectiveTheme: effectiveTheme,
    getAppliedTheme: function () {
      return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    },
    applyTheme: applyTheme,
    toggle: function () {
      var next = effectiveTheme() === 'dark' ? 'light' : 'dark';
      writeStored(next);
      applyTheme(next);
    },
    init: function () {
      applyTheme(effectiveTheme());
    }
  };

  function bindToggle() {
    var btn = document.getElementById('theme-toggle');
    if (!btn || btn.dataset.themeBound) return;
    btn.dataset.themeBound = 'true';
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      window.ThemeManager.toggle();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindToggle);
  } else {
    bindToggle();
  }

  // ---- 页面转场：判断本次导航走「原生跨文档转场」还是「JS 降级」 ----
  //
  //   · 有原生转场 → 什么都不做（⚠ 页面绝不能预隐藏，否则原生转场会拍到空白快照）
  //   · 没有原生转场 → 预隐藏内容，由 page-transition.js 接手淡入
  //
  // 判定必须用「浏览器能力」（CSS.supports 在 head 里同步可用）而不是
  // sessionStorage 标记：直接打开的页面没有上一页，标记必为空，会误判成降级
  // 并与浏览器自己的原生转场叠加，观感就是「页面抖一下」。
  (function initPageTransition() {
    var supportsVt = !!(window.CSS && window.CSS.supports && window.CSS.supports('view-transition-name: none'));

    // 上一页写的标记只当作「确实跑过原生转场」的旁证，不参与判定
    var flagged = false;
    try {
      flagged = window.sessionStorage.getItem('chiri-vt') === '1';
      window.sessionStorage.removeItem('chiri-vt');
    } catch (e) {
      flagged = false;
    }

    var nativeVt = supportsVt || flagged;

    // 供页脚的 page-transition.js 读取 / 更新
    window.__chiriNativeVt = nativeVt;
    window.__chiriVtState = { revealed: false, native: nativeVt };

    // 必须注册在 head 的解析阻塞脚本里：pagereveal 早于首次渲染与快照；
    // 即便已判定为原生路径也保留兜底，绝不在快照前留下预隐藏
    window.addEventListener('pagereveal', function (event) {
      window.__chiriVtState.revealed = true;
      if (event.viewTransition) {
        window.__chiriVtState.native = true;
        window.__chiriNativeVt = true;
        document.documentElement.classList.remove('chiri-pt-pending');
      }
    });

    if (nativeVt) return;

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var root = document.documentElement;
    root.classList.add('chiri-pt-pending');

    // 兜底一：page-transition.js 正常执行时已摘掉 pending 类，这里再摘一次
    // 无副作用；脚本 404 / 报错时能立刻恢复可见
    document.addEventListener('DOMContentLoaded', function () {
      root.classList.remove('chiri-pt-pending');
    });

    // 兜底二：极端情况下也绝不留在不可见状态
    window.setTimeout(function () {
      root.classList.remove('chiri-pt-pending');
    }, 1200);
  })();
})();