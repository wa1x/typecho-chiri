/**
 * Chiri 主题管理
 *
 * 移植自 ThemeManager.astro / ThemeToggle.astro。
 * 关键点：在样式表加载前完成首屏主题判定，避免深色模式下的白屏闪烁。
 */
(function () {
  'use strict';

  if (window.ThemeManager && window.ThemeManager.initialized) {
    return;
  }

  var STORAGE_KEY = 'chiri-theme';
  var systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

  function getAppliedTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  var ThemeManager = {
    STORAGE_KEY: STORAGE_KEY,
    initialized: false,

    getSystemTheme: function () {
      return systemThemeQuery.matches ? 'dark' : 'light';
    },

    getStoredTheme: function () {
      try {
        return window.localStorage.getItem(STORAGE_KEY);
      } catch (e) {
        return null;
      }
    },

    setStoredTheme: function (theme) {
      try {
        if (theme === 'system' || !theme) {
          window.localStorage.removeItem(STORAGE_KEY);
        } else {
          window.localStorage.setItem(STORAGE_KEY, theme);
        }
      } catch (e) {
        // 隐私模式下写入失败时静默降级
      }
    },

    getEffectiveTheme: function () {
      var stored = this.getStoredTheme();
      return stored || this.getSystemTheme();
    },

    applyTheme: function (theme) {
      document.documentElement.classList.remove('light', 'dark');
      document.documentElement.classList.add(theme);

      var meta = document.querySelector('meta[name="theme-color"]');
      if (meta) {
        meta.setAttribute('content', theme === 'dark' ? '#1c1c1c' : '#ffffff');
      }
    },

    toggle: function () {
      var next = this.getEffectiveTheme() === 'dark' ? 'light' : 'dark';
      this.setStoredTheme(next);
      this.applyTheme(next);
    },

    init: function () {
      if (this.initialized) return;
      this.applyTheme(this.getEffectiveTheme());
      this.initialized = true;
    }
  };

  window.ThemeManager = ThemeManager;

  ThemeManager.init();

  // 系统主题变化时跟随（仅在用户未手动选择时）
  var onSystemChange = function (e) {
    if (ThemeManager.getStoredTheme() !== null) return;
    ThemeManager.applyTheme(e.matches ? 'dark' : 'light');
  };

  if (typeof systemThemeQuery.addEventListener === 'function') {
    systemThemeQuery.addEventListener('change', onSystemChange);
  } else if (typeof systemThemeQuery.addListener === 'function') {
    systemThemeQuery.addListener(onSystemChange);
  }

  function bindToggle() {
    var btn = document.getElementById('theme-toggle');
    if (!btn || btn.dataset.themeBound) return;
    btn.dataset.themeBound = 'true';
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      ThemeManager.toggle();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindToggle);
  } else {
    bindToggle();
  }
})();